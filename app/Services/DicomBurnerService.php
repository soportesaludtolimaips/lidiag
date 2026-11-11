<?php

namespace App\Services;

use App\Helpers\DicomHelper;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DicomBurnerService
{
    /**
     * Prepara la quema de estudios DICOM descargando las imágenes y registrando el proceso.
     *
     * @param  array  $data  Datos de la quema: study_instance_uids, patient_id, user_id, device_letter, sede_id
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function prepareBurn(array $data): array
    {
        if (! isset($data['study_instance_uids'])) {
            throw new \InvalidArgumentException('Missing required data for burn preparation.');
        }

        $studyUids = is_array($data['study_instance_uids'])
            ? $data['study_instance_uids']
            : explode(',', $data['study_instance_uids']);

        $studyUids = array_map(fn ($uid) => mb_convert_encoding($uid, 'UTF-8', 'UTF-8'), $studyUids);

        $burnId = DB::table('cd_burn_logs')->insertGetId([
            'patient_id' => $data['patient_id'] ?? null,
            'study_instance_uids' => json_encode($studyUids),
            'user_id' => $data['user_id'] ?? null,
            'device_letter' => isset($data['device_letter']) ? strtoupper($data['device_letter']) : null,
            'status' => 'processing',
            'error_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $folder = "burns/{$burnId}";
        $dicomFolder = "{$folder}/dicom";
        Storage::makeDirectory($dicomFolder, 0755, true);

        try {
            $sedeId = $data['sede_id'] ?? session('ST-sede');
            $sede = ConfigSede::find($sedeId);
            if (! $sede) {
                throw new \RuntimeException('Sede no encontrada.');
            }

            $totalFiles = 0;
            $failedDownloads = 0;
            foreach ($studyUids as $studyUid) {
                $result = DicomHelper::downloadStudyImages(
                    $sedeId,
                    $studyUid,
                    'application/dicom',
                    $dicomFolder
                );

                $totalFiles += $result['success'];
                $failedDownloads += $result['failed'];

                $this->updateProgress($burnId, min(90, 10 + (int) (30 * ($totalFiles + $failedDownloads) / max(count($studyUids), 1))));
            }

            if ($totalFiles === 0) {
                $this->updateProgress($burnId, 0, 'failed');
                $this->updateError($burnId, 'No se encontraron archivos DICOM para los estudios seleccionados.');
                throw new \RuntimeException('No se encontraron archivos DICOM para los estudios seleccionados.');
            }

            $this->updateProgress($burnId, 45, 'completed');

            return [
                'burn_id' => $burnId,
                'download_url' => Storage::url("{$folder}/package.zip"),
                'total_files' => $totalFiles,
                'failed_downloads' => $failedDownloads,
            ];
        } catch (\Throwable $e) {
            if ($e->getMessage() !== 'No se encontraron archivos DICOM para los estudios seleccionados.') {
                $this->updateProgress($burnId, 0, 'failed');
                $this->updateError($burnId, $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Crea un archivo ZIP a partir del contenido de una carpeta especificada.
     *
     * @param  string  $sourceDir  Ruta de la carpeta origen a comprimir
     * @param  string  $zipPath  Ruta destino del archivo ZIP a crear
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function createZipFromFolder(string $sourceDir, string $zipPath): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Failed to create ZIP file at {$zipPath}");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }

    /**
     * Actualiza el estado y el mensaje de error de un registro de quema en la base de datos.
     *
     * @param  int  $burnId  Identificador del registro de quema
     * @param  string  $status  Nuevo estado a asignar
     * @param  string|null  $errorMessage  Mensaje de error asociado, si existe
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function updateStatus(int $burnId, string $status, ?string $errorMessage = null): void
    {
        DB::table('cd_burn_logs')
            ->where('id', $burnId)
            ->update([
                'status' => $status,
                'error_message' => $errorMessage,
                'updated_at' => now(),
            ]);
    }

    /**
     * Actualiza el porcentaje de progreso de un registro de quema en la base de datos.
     *
     * @param  int  $burnId  Identificador del registro de quema
     * @param  int  $progress  Porcentaje de progreso a registrar
     * @param  string|null  $status  Estado a asignar junto con el progreso, si se requiere
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

    /**
     * Registra un mensaje de error en un registro de quema de la base de datos.
     *
     * @param  int  $burnId  Identificador del registro de quema
     * @param  string  $errorMessage  Mensaje de error a registrar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function updateError(int $burnId, string $errorMessage): void
    {
        DB::table('cd_burn_logs')->where('id', $burnId)->update([
            'error_message' => $errorMessage,
            'updated_at' => now(),
        ]);
    }

    /**
     * Obtiene el registro de quema correspondiente al identificador indicado.
     *
     * @param  int  $burnId  Identificador del registro de quema
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function getBurnStatus(int $burnId): ?object
    {
        return DB::table('cd_burn_logs')->find($burnId);
    }

    /**
     * Elimina los registros de quema antiguos y sus carpetas asociadas del almacenamiento.
     *
     * @param  int  $daysOld  Edad en días a partir de la cual se consideran antiguos los registros
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public static function cleanupOldBurns(int $daysOld = 7): int
    {
        $cutoff = now()->subDays($daysOld);
        $oldBurns = DB::table('cd_burn_logs')
            ->where('created_at', '<', $cutoff)
            ->whereIn('status', ['burned', 'failed'])
            ->pluck('id');

        $deleted = 0;
        foreach ($oldBurns as $burnId) {
            $folder = storage_path("app/burns/{$burnId}");
            if (is_dir($folder)) {
                self::deleteDirectory($folder);
                $deleted++;
            }
            DB::table('cd_burn_logs')->where('id', $burnId)->delete();
        }

        return $deleted;
    }

    /**
     * Elimina de forma recursiva una carpeta y todo su contenido del sistema de archivos.
     *
     * @param  string  $dir  Ruta de la carpeta a eliminar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private static function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }
        rmdir($dir);
    }
}
