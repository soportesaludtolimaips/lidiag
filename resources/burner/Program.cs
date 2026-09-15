using System;
using System.IO;
using System.Runtime.InteropServices;
using System.Runtime.InteropServices.ComTypes;

namespace LidiagBurner
{
    class Program
    {
        [STAThread]
        static int Main(string[] args)
        {
            if (args.Length < 2)
            {
                Console.Error.WriteLine("Usage: burner.exe <drive_letter> <iso_or_folder_path>");
                return 1;
            }

            string driveLetter = args[0].TrimEnd('\\', ':');
            string sourcePath = args[1];

            if (!File.Exists(sourcePath) && !Directory.Exists(sourcePath))
            {
                Console.Error.WriteLine("Source not found: " + sourcePath);
                return 1;
            }

            try
            {
                BurnDisc(driveLetter, sourcePath);
                Console.WriteLine("[100%] OK");
                return 0;
            }
            catch (Exception ex)
            {
                Console.Error.WriteLine("[ERROR] " + ex.Message);
                if (ex.InnerException != null)
                    Console.Error.WriteLine("[ERROR] Inner: " + ex.InnerException.Message);
                return 1;
            }
        }

        static void BurnDisc(string driveLetter, string sourcePath)
        {
            // 1) Find recorder
            Console.WriteLine("[0%] Buscando grabadora...");
            MsftDiscMaster2Class discMasterObj = new MsftDiscMaster2Class();
            IDiscMaster2 discMaster = (IDiscMaster2)discMasterObj;

            if (!discMaster.IsSupportedEnvironment)
                throw new Exception("Entorno no soportado para grabacion.");

            int count = discMaster.Count;
            Console.WriteLine("  " + count + " grabadora(s) encontrada(s)");

            string recorderId = null;
            string targetDrive = driveLetter.ToUpper() + ":\\";

            for (int i = 0; i < count; i++)
            {
                string id = discMaster[i];
                MsftDiscRecorder2Class recObj = new MsftDiscRecorder2Class();
                IDiscRecorder2 rec = (IDiscRecorder2)recObj;
                rec.InitializeDiscRecorder(id);

                object[] paths = rec.VolumePathNames;
                if (paths != null)
                {
                    foreach (object p in paths)
                    {
                        string path = p.ToString();
                        if (path.StartsWith(targetDrive, StringComparison.OrdinalIgnoreCase))
                        {
                            recorderId = id;
                            break;
                        }
                    }
                }

                Marshal.ReleaseComObject(recObj);
                if (recorderId != null) break;
            }

            Marshal.ReleaseComObject(discMasterObj);

            if (recorderId == null)
                throw new Exception("No se encontro grabadora en unidad " + driveLetter);

            Console.WriteLine("  Recorder: " + recorderId);

            // 2) Initialize recorder
            Console.WriteLine("[5%] Inicializando...");
            MsftDiscRecorder2Class recorderObj = new MsftDiscRecorder2Class();
            IDiscRecorder2 recorderWrap = (IDiscRecorder2)recorderObj;
            recorderWrap.InitializeDiscRecorder(recorderId);

            Console.WriteLine("  " + recorderWrap.VendorId + " " + recorderWrap.ProductId);

            // 3) Detect media type BEFORE creating image
            Console.WriteLine("[10%] Detectando medio...");
            MsftDiscFormat2DataClass burnFormatObj = new MsftDiscFormat2DataClass();
            IDiscFormat2Data burnFormat = (IDiscFormat2Data)burnFormatObj;
            burnFormat.Recorder = (IDiscRecorder2)recorderWrap;
            burnFormat.ClientName = "LidiagBurner";
            burnFormat.ForceMediaToBeClosed = true;

            int mediaType = 0; // 0 = unknown
            try
            {
                object physType = burnFormat.CurrentPhysicalMediaType;
                try { mediaType = Convert.ToInt32(physType); } catch { }
            }
            catch { }

            // IMAPI2 media types:
            // 1=CDROM, 2=CDR, 3=CDRW  → CD (700 MB)
            // 4=DVDROM, 5=DVDRAM, 6=DVDPLUSR, 7=DVDPLUSRW, 8=DVDPLUSR_DL,
            // 9=DVDMINUSR, 10=DVDMINUSRW, 11=DVDMINUSR_DL, 12=DVDRAW,
            // 13=DVDPLUSRW_DL  → DVD (4.7 GB+)
            bool isCD = (mediaType >= 1 && mediaType <= 3);
            bool isDVD = (mediaType >= 4 && mediaType <= 13);
            string mediaName = mediaType == 0 ? "desconocido" :
                               isCD ? "CD" :
                               isDVD ? "DVD" :
                               "tipo " + mediaType;
            Console.WriteLine("  Medio detectado: " + mediaName + " (tipo IMAPI2: " + mediaType + ")");

            // 4) Create stream from source
            Console.WriteLine("[15%] Preparando imagen...");
            IStream imageStream;

            if (File.Exists(sourcePath) && sourcePath.EndsWith(".iso", StringComparison.OrdinalIgnoreCase))
            {
                // ISO file - read directly
                byte[] isoBytes = File.ReadAllBytes(sourcePath);
                long totalBytes = isoBytes.Length;
                Console.WriteLine("  ISO: " + (totalBytes / 1024 / 1024) + " MB");

                int hr = CreateStreamOnHGlobal(IntPtr.Zero, true, out imageStream);
                if (hr != 0 || imageStream == null)
                    throw new Exception("CreateStreamOnHGlobal failed: 0x" + hr.ToString("X8"));

                const int chunkSize = 1024 * 1024;
                for (long offset = 0; offset < totalBytes; offset += chunkSize)
                {
                    int len = (int)Math.Min(chunkSize, totalBytes - offset);
                    byte[] chunk = new byte[len];
                    Array.Copy(isoBytes, offset, chunk, 0, len);
                    imageStream.Write(chunk, len, IntPtr.Zero);
                }
                imageStream.Seek(0, 0, IntPtr.Zero);
            }
            else
            {
                // Folder - create filesystem image
                Console.WriteLine("  Creando imagen desde carpeta...");
                MsftFileSystemImageClass fsImageObj = new MsftFileSystemImageClass();
                IFileSystemImage fsImage = (IFileSystemImage)fsImageObj;

                // Use correct media type for image sizing
                int imageMediaType = isCD ? 2 : 12; // 2=CDR, 12=DVDRAW
                if (!isCD && !isDVD) imageMediaType = 12; // default to DVD for unknown
                fsImage.ChooseImageDefaultsForMediaType(imageMediaType);
                fsImage.FileSystemsToCreate = 4; // FsiFileSystemJoliet
                fsImage.VolumeName = "DICOM_STUDY";

                IFsiDirectoryItem root = fsImage.Root;
                root.AddTree(sourcePath, false);

                // Check if image fits on detected media
                if (isCD)
                {
                    long usedBytes = (long)fsImage.UsedBlocks * 2048;
                    long cdCapacity = 700L * 1024 * 1024; // ~700 MB
                    Console.WriteLine("  Imagen: " + (usedBytes / 1024 / 1024) + " MB, capacidad CD: ~700 MB");
                    if (usedBytes > cdCapacity)
                        throw new Exception("La imagen (" + (usedBytes / 1024 / 1024) + " MB) excede la capacidad de un CD-R (~700 MB). Inserte un disco DVD.");
                }

                IFileSystemImageResult result = fsImage.CreateResultImage();
                imageStream = result.ImageStream;

                if (imageStream == null)
                    throw new Exception("No se pudo crear la imagen.");

                Marshal.ReleaseComObject(fsImageObj);
            }

            Console.WriteLine("[40%] Stream listo");

            // 5) Check media - retry up to 3 times (USB drives need time)
            Console.WriteLine("[55%] Verificando medio...");
            bool mediaReady = false;
            string lastError = null;
            for (int attempt = 1; attempt <= 3; attempt++)
            {
                try
                {
                    bool physBlank = burnFormat.MediaPhysicallyBlank;
                    bool heurBlank = burnFormat.MediaHeuristicallyBlank;
                    bool mediaSupported = burnFormat.IsCurrentMediaSupported((IDiscRecorder2)recorderWrap);

                    Console.WriteLine("  Intento " + attempt + ": enBlanco=" + physBlank + " heuristico=" + heurBlank + " soportado=" + mediaSupported);

                    if (mediaSupported && (physBlank || heurBlank))
                    {
                        mediaReady = true;
                        break;
                    }

                    if (!mediaSupported)
                        lastError = "El disco no es compatible con esta grabadora.";
                }
                catch (COMException ex)
                {
                    lastError = "Error detectando disco: 0x" + ex.ErrorCode.ToString("X8");
                    Console.WriteLine("  Intento " + attempt + ": " + lastError);
                }

                if (attempt < 3)
                {
                    Console.WriteLine("  Esperando deteccion del disco...");
                    System.Threading.Thread.Sleep(3000);
                }
            }

            if (!mediaReady)
            {
                // Check if disc is erasable (CD-RW/DVD-RW/DVD+RW)
                bool isErasable = false;
                try
                {
                    object physType = burnFormat.CurrentPhysicalMediaType;
                    int eraseMediaType = 0;
                    try { eraseMediaType = Convert.ToInt32(physType); } catch { }
                    Console.WriteLine("  PhysicalMediaType: " + eraseMediaType);

                    // 3=CD-RW, 5=DVD-RAM, 7=DVD+RW, 10=DVD-RW, 13=DVD+RW Dual Layer
                    isErasable = (eraseMediaType == 3 || eraseMediaType == 5 || eraseMediaType == 7 || eraseMediaType == 10 || eraseMediaType == 13);
                }
                catch { }

                if (isErasable)
                {
                    Console.WriteLine("[56%] Disco reescribible detectado. Borrando...");
                    try
                    {
                        MsftDiscFormat2EraseClass eraseObj = new MsftDiscFormat2EraseClass();
                        IDiscFormat2Erase erase = (IDiscFormat2Erase)eraseObj;
                        erase.Recorder = (IDiscRecorder2)recorderWrap;
                        erase.ClientName = "LidiagBurner";
                        erase.FullErase = false; // Quick erase
                        erase.Erase();
                        Marshal.ReleaseComObject(eraseObj);
                        Console.WriteLine("  Disco borrado correctamente.");

                        // Re-check media after erase
                        System.Threading.Thread.Sleep(2000);
                        bool physBlank = burnFormat.MediaPhysicallyBlank;
                        bool heurBlank = burnFormat.MediaHeuristicallyBlank;
                        if (physBlank || heurBlank)
                        {
                            mediaReady = true;
                        }
                    }
                    catch (COMException ex)
                    {
                        throw new Exception("Error al borrar disco: 0x" + ex.ErrorCode.ToString("X8"));
                    }
                }
            }

            if (!mediaReady)
            {
                if (lastError != null)
                    throw new Exception(lastError);
                throw new Exception("No se detecto disco en blanco. Inserte un disco CD-R o DVD-R nuevo.");
            }

            Console.WriteLine("[60%] Grabando...");

            // Force a conservative write speed to avoid "velocidad no compatible" errors.
            // IMAPI2 auto-negotiation fails when media and recorder speed ranges don't overlap.
            // Using the lowest supported speed maximizes compatibility.
            try
            {
                object[] supportedSpeeds = burnFormat.SupportedWriteSpeeds;
                if (supportedSpeeds != null && supportedSpeeds.Length > 0)
                {
                    // Sort and pick the lowest speed (sectors/sec)
                    int lowest = int.MaxValue;
                    foreach (object s in supportedSpeeds)
                    {
                        int val = 0;
                        try { val = Convert.ToInt32(s); } catch { }
                        if (val > 0 && val < lowest) lowest = val;
                    }
                    if (lowest < int.MaxValue)
                    {
                        Console.WriteLine("  Velocidad de escritura: " + lowest + " sectores/s");
                        burnFormat.SetWriteSpeed(lowest, false);
                    }
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine("  No se pudo configurar velocidad: " + ex.Message);
            }

            burnFormat.Write(imageStream);

            Console.WriteLine("[95%] Grabacion completada.");

            Marshal.ReleaseComObject(burnFormatObj);
            Marshal.ReleaseComObject(recorderObj);
            if (imageStream != null) Marshal.ReleaseComObject(imageStream);
        }

        [DllImport("ole32.dll")]
        static extern int CreateStreamOnHGlobal(IntPtr hGlobal, bool fDeleteOnRelease, out IStream ppstm);
    }

    // ---- IDiscMaster2 ----
    [ComImport, Guid("27354130-7F64-5B0F-8F00-5D77AFBE261E")]
    [InterfaceType(ComInterfaceType.InterfaceIsIDispatch)]
    interface IDiscMaster2
    {
        System.Collections.IEnumerator GetEnumerator();
        string this[int index] { get; }
        int Count { get; }
        bool IsSupportedEnvironment { get; }
    }

    // ---- IDiscRecorder2 ----
    [ComImport, Guid("27354133-7F64-5B0F-8F00-5D77AFBE261E")]
    [InterfaceType(ComInterfaceType.InterfaceIsIDispatch)]
    interface IDiscRecorder2
    {
        void EjectMedia();
        void CloseTray();
        void AcquireExclusiveAccess(bool force, string clientName);
        void ReleaseExclusiveAccess();
        void DisableMcn();
        void EnableMcn();
        void InitializeDiscRecorder(string recorderUniqueId);
        string ActiveDiscRecorder { get; }
        string VendorId { get; }
        string ProductId { get; }
        string ProductRevision { get; }
        string VolumeName { get; }
        object[] VolumePathNames { get; }
        bool DeviceCanLoadMedia { get; }
        int LegacyDeviceNumber { get; }
        object[] SupportedFeaturePages { get; }
        object[] CurrentFeaturePages { get; }
        object[] SupportedProfiles { get; }
        object[] CurrentProfiles { get; }
        object[] SupportedModePages { get; }
        string ExclusiveAccessOwner { get; }
    }

    // ---- IDiscFormat2Data ----
    [ComImport, Guid("27354153-9F64-5B0F-8F00-5D77AFBE261E")]
    [InterfaceType(ComInterfaceType.InterfaceIsIDispatch)]
    interface IDiscFormat2Data
    {
        long IsRecorderSupported(IDiscRecorder2 Recorder);
        bool IsCurrentMediaSupported(IDiscRecorder2 Recorder);
        bool MediaPhysicallyBlank { get; }
        bool MediaHeuristicallyBlank { get; }
        object[] SupportedMediaTypes { get; }
        IDiscRecorder2 Recorder { set; get; }
        bool BufferUnderrunFreeDisabled { set; get; }
        bool PostgapAlreadyInImage { set; get; }
        object CurrentMediaStatus { get; }
        object WriteProtectStatus { get; }
        int TotalSectorsOnMedia { get; }
        int FreeSectorsOnMedia { get; }
        int NextWritableAddress { get; }
        int StartAddressOfPreviousSession { get; }
        int LastWrittenAddressOfPreviousSession { get; }
        bool ForceMediaToBeClosed { set; get; }
        bool DisableConsumerDvdCompatibilityMode { set; get; }
        object CurrentPhysicalMediaType { get; }
        string ClientName { set; get; }
        int RequestedWriteSpeed { get; }
        bool RequestedRotationTypeIsPureCAV { get; }
        int CurrentWriteSpeed { get; }
        bool CurrentRotationTypeIsPureCAV { get; }
        object[] SupportedWriteSpeeds { get; }
        object[] SupportedWriteSpeedDescriptors { get; }
        bool ForceOverwrite { set; get; }
        object[] MultisessionInterfaces { get; }
        void Write(IStream data);
        void CancelWrite();
        void SetWriteSpeed(int RequestedSectorsPerSecond, bool RotationTypeIsPureCAV);
    }

    // ---- IFileSystemImage ----
    [ComImport, Guid("2C941FE1-975B-59BE-A960-9A2A262853A5")]
    [InterfaceType(ComInterfaceType.InterfaceIsIDispatch)]
    interface IFileSystemImage
    {
        IFsiDirectoryItem Root { get; }
        int SessionStartBlock { get; set; }
        int FreeMediaBlocks { get; set; }
        void SetMaxMediaBlocksFromDevice(IDiscRecorder2 discRecorder);
        int UsedBlocks { get; }
        string VolumeName { get; set; }
        string ImportedVolumeName { get; }
        object BootImageOptions { get; set; }
        int FileCount { get; }
        int DirectoryCount { get; }
        string WorkingDirectory { get; set; }
        int ChangePoint { get; }
        bool StrictFileSystemCompliance { get; set; }
        bool UseRestrictedCharacterSet { get; set; }
        object FileSystemsToCreate { get; set; }
        object FileSystemsSupported { get; }
        void ChooseImageDefaults(IDiscRecorder2 discRecorder);
        void ChooseImageDefaultsForMediaType(int value);
        int ISO9660InterchangeLevel { set; get; }
        object[] ISO9660InterchangeLevelsSupported { get; }
        IFileSystemImageResult CreateResultImage();
        int Exists(string FullPath);
        string CalculateDiscIdentifier();
        int IdentifyFileSystemsOnDisc(IDiscRecorder2 discRecorder);
        int GetDefaultFileSystemForImport(int fileSystems);
        int ImportFileSystem();
        void ImportSpecificFileSystem(int fileSystemToUse);
        void RollbackToChangePoint(int ChangePoint);
        void LockInChangePoint();
        IFsiDirectoryItem CreateDirectoryItem(string Name);
        object CreateFileItem(string Name);
        string VolumeNameUDF { get; }
        string VolumeNameJoliet { get; }
        string VolumeNameISO9660 { get; }
        bool StageFiles { get; set; }
        object[] MultisessionInterfaces { get; set; }
    }

    // ---- IFileSystemImageResult ----
    [ComImport, Guid("2C941FD8-975B-59BE-A960-9A2A262853A5")]
    [InterfaceType(ComInterfaceType.InterfaceIsIDispatch)]
    interface IFileSystemImageResult
    {
        IStream ImageStream { get; }
        object ProgressItems { get; }
        int TotalBlocks { get; }
        int BlockSize { get; }
        string DiscId { get; }
    }

    // ---- IFsiDirectoryItem ----
    [ComImport, Guid("2C941FDC-975B-59BE-A960-9A2A262853A5")]
    [InterfaceType(ComInterfaceType.InterfaceIsIDispatch)]
    interface IFsiDirectoryItem
    {
        string Name { get; }
        string FullPath { get; }
        void AddDirectory(string path);
        void AddFile(string path, IStream fileData);
        void AddTree(string sourceDirectory, bool includeBaseDirectory);
        void Add(object Item);
        void Remove(string path);
        void RemoveTree(string path);
    }

    // ---- COM Classes ----
    [ComImport, Guid("2735412D-7F64-5B0F-8F00-5D77AFBE261E")]
    [ClassInterface(ClassInterfaceType.None)]
    class MsftDiscRecorder2Class { }

    [ComImport, Guid("2735412A-7F64-5B0F-8F00-5D77AFBE261E")]
    [ClassInterface(ClassInterfaceType.None)]
    class MsftDiscFormat2DataClass { }

    [ComImport, Guid("2735412B-7F64-5B0F-8F00-5D77AFBE261E")]
    [ClassInterface(ClassInterfaceType.None)]
    class MsftDiscFormat2EraseClass { }

    [ComImport, Guid("2735412E-7F64-5B0F-8F00-5D77AFBE261E")]
    [ClassInterface(ClassInterfaceType.None)]
    class MsftDiscMaster2Class { }

    [ComImport, Guid("2C941FC5-975B-59BE-A960-9A2A262853A5")]
    [ClassInterface(ClassInterfaceType.None)]
    class MsftFileSystemImageClass { }

    // ---- IDiscFormat2Erase ----
    [ComImport, Guid("27354156-8F64-5B0F-8F00-5D77AFBE261E")]
    [InterfaceType(ComInterfaceType.InterfaceIsIDispatch)]
    interface IDiscFormat2Erase
    {
        IDiscRecorder2 Recorder { set; get; }
        string ClientName { set; get; }
        bool FullErase { set; get; }
        void Erase();
    }
}
