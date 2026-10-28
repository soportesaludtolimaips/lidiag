<?php

namespace App\Http\Controllers;

use App\Helpers\DicomHelper;
use App\Services\DicomBurnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DicomBurnerController extends Controller
{
    protected $burnerService;

    /**
     * Inyecta el servicio de quemado de discos DICOM.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(DicomBurnerService $burnerService)
    {
        $this->burnerService = $burnerService;
    }

    /**
     * Prepara el paquete DICOM de uno o más estudios para su grabación en disco.
     *
     * @param  Request  $request  Solicitud con study_instance_uids, user_id, device_letter y sede_id.
     *
     * @returns JsonResponse Respuesta JSON con el burn_id y la URL de descarga del paquete.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function generateIso(Request $request)
    {
        Log::debug('DicomBurnerController.generateIso request data', $request->all());

        $validated = $request->validate([
            'study_instance_uids' => 'required|array|min:1',
            'study_instance_uids.*' => 'string',
            'user_id' => 'nullable|integer',
            'device_letter' => 'nullable|string|max:2',
            'sede_id' => 'required|integer',
        ]);

        $validated['sede_id'] = (int) $validated['sede_id'];

        try {
            $result = $this->burnerService->prepareBurn($validated);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'burn_id' => $result['burn_id'],
                    'download_url' => Storage::url("burns/{$result['burn_id']}/package.zip"),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('DicomBurnerController.generateIso failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $msg = mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8');

            return response()->json([
                'status' => 'error',
                'message' => $msg,
            ], 500);
        }
    }

    /**
     * Consulta el estado y el progreso de una tarea de quemado de disco.
     *
     * @param  mixed  $id  Identificador de la tarea en cd_burn_logs.
     *
     * @returns JsonResponse Respuesta JSON con el estado, progreso y mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function status($id)
    {
        $burnLog = DB::table('cd_burn_logs')->find($id);

        if (! $burnLog) {
            return response()->json([
                'status' => 'error',
                'message' => 'Burn job not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $burnLog->id,
                'status' => $burnLog->status,
                'progress' => $burnLog->progress ?? 0,
                'error_message' => $burnLog->error_message,
            ],
        ]);
    }

    /**
     * Detecta las unidades de grabación de discos disponibles en el servidor.
     *
     * Flujo del proceso:
     * 1. Consulta el parámetro 'refresh' para limpiar la caché de unidades si es requerido.
     * 2. Ejecuta la detección multi-nivel en Windows: IMAPI2 COM -> .NET DriveInfo -> Fallback WMI.
     * 3. Normaliza las letras de unidad, estado de bandeja/disco y nombre del dispositivo.
     * 4. Retorna la colección formateada en JSON.
     *
     * @param  Request  $request  Solicitud HTTP con parámetro opcional 'refresh'.
     * @return JsonResponse Respuesta JSON con la lista de unidades, tipo de disco y estado del medio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-28
     */
    public function listDrives(Request $request)
    {
        if ($request->boolean("refresh")) {
            Cache::forget("dicom.drives");
        }

        $drives = Cache::remember("dicom.drives", 5, function () {
            $drives = [];

            if (PHP_OS_FAMILY === "Windows") {
                $scriptPath = base_path("resources/burner/detect_drives.ps1");
                if (file_exists($scriptPath)) {
                    $cmd = "powershell -NoProfile -ExecutionPolicy Bypass -File ".escapeshellarg($scriptPath)." 2>&1";
                } else {
                    $cmd = 'powershell -NoProfile -Command "[System.IO.DriveInfo]::GetDrives() | Where-Object { $_.DriveType -eq [System.IO.DriveType]::CDRom } | ForEach-Object { [PSCustomObject]@{ drive = $_.Name.Substring(0, 1).ToUpper(); disc_type = \"CD\"; media_loaded = $_.IsReady; recorder_name = (\"Unidad de CD/DVD (\" + $_.Name + \")\") } } | ConvertTo-Json -Compress" 2>&1';
                }

                $descriptors = [
                    0 => ["pipe", "r"],
                    1 => ["pipe", "w"],
                    2 => ["pipe", "w"],
                ];
                $process = proc_open($cmd, $descriptors, $pipes);

                if (is_resource($process)) {
                    fclose($pipes[0]);
                    stream_set_timeout($pipes[1], 10);
                    $output = stream_get_contents($pipes[1]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    proc_close($process);

                    $trimmed = trim((string) $output);
                    if ($trimmed !== "") {
                        $decoded = json_decode($trimmed, true);
                        if (is_array($decoded)) {
                            if (isset($decoded["drive"])) {
                                $decoded = [$decoded];
                            }
                            foreach ($decoded as $item) {
                                if (is_array($item) && ! empty($item["drive"])) {
                                    $drives[] = [
                                        "drive" => strtoupper(trim(trim($item["drive"]), ":")),
                                        "disc_type" => $item["disc_type"] ?? null,
                                        "media_loaded" => (bool) ($item["media_loaded"] ?? false),
                                        "recorder_name" => $item["recorder_name"] ?? null,
                                    ];
                                }
                            }
                        }
                    }
                }
            } else {
                $possibleDevices = ["/dev/cdrom", "/dev/dvd", "/dev/sr0", "/dev/sr1"];
                foreach ($possibleDevices as $device) {
                    if (file_exists($device)) {
                        $drives[] = [
                            "drive" => $device,
                            "disc_type" => null,
                            "media_loaded" => false,
                            "recorder_name" => null,
                        ];
                    }
                }
            }

            return $drives;
        });

        return response()->json([
            "status" => "success",
            "data" => $drives,
        ]);
    }

    /**
     * Graba el paquete DICOM preparado en la unidad de disco indicada.
     *
     * @param  Request  $request  Solicitud con burn_id y device_letter.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la operación de quemado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function burn(Request $request)
    {
        $validated = $request->validate([
            'burn_id' => 'required|integer|exists:cd_burn_logs,id',
            'device_letter' => 'required|string|max:10',
        ]);

        $burnId = $validated['burn_id'];
        $deviceLetter = strtoupper(trim($validated['device_letter'], ':'));

        try {
            $burnLog = DB::table('cd_burn_logs')->where('id', $burnId)->first();
            if ($burnLog->status !== 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El paquete DICOM no está listo para quemar. Estado actual: '.$burnLog->status,
                ], 400);
            }

            $burnFolder = storage_path("app/burns/{$burnId}");
            $dicomFolder = "{$burnFolder}/dicom";

            if (! is_dir($dicomFolder)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se encontró la carpeta de archivos DICOM.',
                ], 404);
            }

            // Build burn folder: copy MicroDicom viewer + place DICOM files inside DICOM/
            $this->updateProgress($burnId, 5, 'Preparando visor DICOM...');
            $cdFolder = $this->prepareCdFolder($burnId, $dicomFolder);

            // Validar espacio suficiente en el disco del servidor para staging (mínimo 1 GB)
            $burnStaging = storage_path("app/burns");
            $freeSpaceBytes = @disk_free_space($burnStaging);
            $minRequired = 1024 * 1024 * 1024; // 1 GB mínimo en disco de almacenamiento

            if ($freeSpaceBytes !== false && $freeSpaceBytes < $minRequired) {
                $this->removeDirectory($cdFolder);
                throw new \RuntimeException(
                    "Espacio insuficiente en el disco del servidor para preparar el paquete DICOM. ".
                    "Disponible: ".number_format($freeSpaceBytes / 1024 / 1024, 1)." MB, ".
                    "mínimo requerido: 1024 MB."
                );
            }

            $this->updateProgress($burnId, 15, 'Preparando unidad de disco...');
            $this->burnDicomFolder($cdFolder, $deviceLetter, $burnId);

            // Cleanup temp CD folder
            $this->removeDirectory($cdFolder);

            $this->updateProgress($burnId, 100, 'Disco quemado exitosamente');

            DB::table('cd_burn_logs')->where('id', $burnId)->update([
                'status' => 'burned',
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Disco quemado exitosamente.',
                'data' => ['burn_id' => $burnId],
            ]);
        } catch (\Throwable $e) {
            Log::error('DicomBurnerController.burn failed', ['error' => $e->getMessage()]);
            $msg = mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8');
            DB::table('cd_burn_logs')->where('id', $burnId)->update([
                'status' => 'failed',
                'error_message' => $msg,
                'updated_at' => now(),
            ]);

            // Cleanup temp folders on failure
            $cdFolder = storage_path("app/burns/{$burnId}/cd");
            if (is_dir($cdFolder)) {
                $this->removeDirectory($cdFolder);
            }

            return response()->json([
                'status' => 'error',
                'message' => $msg,
            ], 500);
        }
    }

    /**
     * Prepara la carpeta temporal del CD con el visor MicroDicom y los archivos DICOM.
     *
     * @param  int  $burnId  Identificador de la tarea de quemado.
     * @param  string  $dicomFolder  Ruta de la carpeta con los archivos DICOM.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function prepareCdFolder(int $burnId, string $dicomFolder): string
    {
        // Visor portable en storage/app_lidiag (hogar de apps empaquetadas,
        // ignorado por git)
        $viewerSource = storage_path('app_lidiag/microdicom');
        $cdFolder = storage_path("app/burns/{$burnId}/cd");

        if (! is_dir($viewerSource)) {
            throw new \RuntimeException('Carpeta del visor MicroDicom no encontrada: '.$viewerSource);
        }

        // Copy entire MicroDicom folder structure
        $this->copyDirectory($viewerSource, $cdFolder);

        // Copy DICOM files into cd/DICOM/
        $cdDicomFolder = "{$cdFolder}/DICOM";
        if (! is_dir($cdDicomFolder)) {
            mkdir($cdDicomFolder, 0755, true);
        }

        $files = glob("{$dicomFolder}/*");
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    copy($file, "{$cdDicomFolder}/".basename($file));
                }
            }
        }

        Log::debug('prepareCdFolder completed', [
            'cdFolder' => $cdFolder,
            'dicomFiles' => count(glob("{$cdDicomFolder}/*")),
        ]);

        return $cdFolder;
    }

    /**
     * Copia de forma recursiva una carpeta y su contenido a un destino.
     *
     * @param  string  $source  Ruta de origen.
     * @param  string  $dest  Ruta de destino.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function copyDirectory(string $source, string $dest): void
    {
        if (! is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath = "{$source}/{$item}";
            $dstPath = "{$dest}/{$item}";

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }

    /**
     * Elimina de forma recursiva una carpeta y todo su contenido.
     *
     * @param  string  $dir  Ruta de la carpeta a eliminar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = "{$dir}/{$item}";
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Calcula el tamaño total en bytes de una carpeta y su contenido.
     *
     * @param  string  $dir  Ruta de la carpeta.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function getDirectorySize(string $dir): int
    {
        $size = 0;
        if (! is_dir($dir)) {
            return $size;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($items as $item) {
            if ($item->isFile()) {
                $size += $item->getSize();
            }
        }

        return $size;
    }

    /**
     * Ejecuta burner.exe para grabar la carpeta preparada en la unidad de disco indicada.
     *
     * @param  string  $dicomFolder  Ruta de la carpeta con el paquete a grabar.
     * @param  string  $deviceLetter  Letra de la unidad de destino.
     * @param  int  $burnId  Identificador de la tarea de quemado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function burnDicomFolder(string $dicomFolder, string $deviceLetter, int $burnId): void
    {
        $burnerExe = base_path('resources/burner/burner.exe');

        if (! file_exists($burnerExe)) {
            throw new \RuntimeException(' burner.exe no encontrado en: '.$burnerExe);
        }

        // Run burner.exe: accepts folder path or ISO file
        $cmd = sprintf(
            '"%s" %s "%s" 2>&1',
            $burnerExe,
            escapeshellarg($deviceLetter),
            escapeshellarg($dicomFolder)
        );

        Log::debug('DicomBurnerController.burnDicomFolder executing', ['cmd' => $cmd]);

        $output = [];
        $exitCode = 0;
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptors, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            while (! feof($pipes[1])) {
                $line = fgets($pipes[1]);
                if ($line !== false) {
                    $line = trim($line);
                    if (preg_match('/^\[(\d+)%\]\s*(.*)$/', $line, $m)) {
                        $pct = min(100, max(0, (int) $m[1]));
                        $msg = $m[2];
                        $this->updateProgress($burnId, $pct, $msg);
                    }
                    $output[] = $line;
                }
            }
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
        }

        $outputText = implode("\n", $output);
        Log::debug('DicomBurnerController.burnDicomFolder output', ['exitCode' => $exitCode, 'output' => $outputText]);

        if ($exitCode !== 0) {
            // Parse error messages from burner.exe
            if (str_contains($outputText, 'medios compatibles')) {
                throw new \RuntimeException('El disco no es compatible con la grabadora. Inserte un disco CD-R o DVD-R en blanco.');
            }
            if (str_contains($outputText, 'no esta listo') || str_contains($outputText, '0x80070015')) {
                throw new \RuntimeException('La unidad de disco no esta lista. Verifique que haya un disco insertado.');
            }
            if (str_contains($outputText, '0x80070001')) {
                throw new \RuntimeException('Error de acceso a la unidad de disco. Verifique que la unidad este disponible.');
            }
            if (str_contains($outputText, '0x80040154')) {
                throw new \RuntimeException('Componente de quemado no disponible en este sistema.');
            }
            if (str_contains($outputText, 'No se encontro grabadora')) {
                throw new \RuntimeException('No se encontro una grabadora de discos en la unidad '.$deviceLetter.'.');
            }
            throw new \RuntimeException('Error al quemar disco: '.$outputText);
        }
    }

    /**
     * Genera un ZIP descargable con el visor MicroDicom y las imágenes DICOM de un estudio.
     *
     * @param  Request  $request  Solicitud con study_instance_uid, sede_id y patient_name.
     *
     * @returns JsonResponse Respuesta JSON con el nombre del archivo y el total de archivos incluidos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function generateZip(Request $request)
    {
        $validated = $request->validate([
            'study_instance_uid' => 'required|string',
            'sede_id' => 'required|integer',
            'patient_name' => 'nullable|string|max:255',
        ]);

        $studyUid = $validated['study_instance_uid'];
        $sedeId = (int) $validated['sede_id'];
        $patientName = $validated['patient_name'] ?? 'estudio';
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $patientName);

        $zipId = uniqid('zip_', true);
        $tempDir = storage_path("app/zips/{$zipId}");
        $dicomDir = "{$tempDir}/DICOM";
        $zipPath = storage_path("app/zips/{$safeName}_{$studyUid}.zip");

        try {
            // Create directories
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            if (! is_dir($dicomDir)) {
                mkdir($dicomDir, 0755, true);
            }

            // Store progress file
            $progressFile = storage_path("app/zips/{$zipId}_progress.json");
            file_put_contents($progressFile, json_encode(['progress' => 0, 'status' => 'Descargando imágenes...']));

            // Copy MicroDicom viewer
            $viewerSource = storage_path('app_lidiag/microdicom');
            if (! is_dir($viewerSource)) {
                throw new \RuntimeException('Carpeta del visor MicroDicom no encontrada: '.$viewerSource);
            }
            $this->copyDirectory($viewerSource, $tempDir);

            // Download DICOM images
            $result = DicomHelper::downloadStudyImages(
                $sedeId,
                $studyUid,
                'application/dicom',
                "zips/{$zipId}/DICOM"
            );

            if (isset($result['error'])) {
                throw new \RuntimeException($result['error']);
            }

            if ($result['success'] === 0) {
                throw new \RuntimeException('No se encontraron imágenes DICOM para el estudio.');
            }

            // Update progress: downloading complete
            file_put_contents($progressFile, json_encode(['progress' => 60, 'status' => 'Comprimiendo archivos...']));

            // Create ZIP
            $zip = new \ZipArchive;
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Error al crear el archivo ZIP.');
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (! $file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($tempDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            // Update progress: complete
            file_put_contents($progressFile, json_encode([
                'progress' => 100,
                'status' => 'Listo',
                'download_url' => "/api/dicom/download-zip/{$safeName}_{$studyUid}.zip",
                'filename' => "{$safeName}_{$studyUid}.zip",
            ]));

            // Cleanup temp directory
            $this->removeDirectory($tempDir);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'zip_id' => $zipId,
                    'filename' => "{$safeName}_{$studyUid}.zip",
                    'total_files' => $result['success'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('DicomBurnerController.generateZip failed', ['error' => $e->getMessage()]);
            $this->removeDirectory($tempDir);
            @unlink($zipPath);

            return response()->json([
                'status' => 'error',
                'message' => mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8'),
            ], 500);
        }
    }

    /**
     * Consulta el progreso de la generación de un archivo ZIP.
     *
     * @param  mixed  $id  Identificador único de la tarea de compresión.
     *
     * @returns JsonResponse Respuesta JSON con el progreso y estado de la tarea.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function zipStatus($id)
    {
        $progressFile = storage_path("app/zips/{$id}_progress.json");

        if (! file_exists($progressFile)) {
            return response()->json([
                'status' => 'error',
                'message' => 'ZIP job not found.',
            ], 404);
        }

        $data = json_decode(file_get_contents($progressFile), true);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Descarga un archivo ZIP generado y lo elimina después del envío.
     *
     * @param  mixed  $filename  Nombre del archivo ZIP a descargar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function downloadZip($filename)
    {
        $safe = basename($filename);
        if ($safe !== $filename) {
            abort(400, 'Nombre de archivo inválido');
        }

        $filePath = storage_path("app/zips/{$safe}");

        if (! file_exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Archivo ZIP no encontrado.',
            ], 404);
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

    /**
     * Elimina los registros de quemado antiguos (mayores a 7 días).
     *
     * @returns JsonResponse Respuesta JSON con la cantidad de registros limpiados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function cleanup()
    {
        $deleted = DicomBurnerService::cleanupOldBurns(7);

        return response()->json([
            'status' => 'success',
            'message' => "Se limpiaron {$deleted} quemados antiguos.",
        ]);
    }

    /**
     * Actualiza el progreso y el estado de una tarea de quemado en cd_burn_logs.
     *
     * @param  int  $burnId  Identificador de la tarea de quemado.
     * @param  int  $progress  Porcentaje de avance (0-100).
     * @param  string|null  $status  Mensaje de estado opcional.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function updateProgress(int $burnId, int $progress, ?string $status = null): void
    {
        $update = ['progress' => $progress, 'updated_at' => now()];
        if ($status !== null) {
            $update['status'] = $status;
        }
        DB::table('cd_burn_logs')->where('id', $burnId)->update($update);
    }
}
