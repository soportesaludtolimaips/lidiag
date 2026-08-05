<?php

namespace App\Helpers;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DicomHelper
{
    private static ?string $originalDefault = null;

    /**
     * Obtener sede por ID.
     */
    public static function getSede(int $sedeId): ?ConfigSede
    {
        return ConfigSede::find($sedeId);
    }

    /**
     * Conectar a la base de datos del PACS de una sede.
     */
    public static function connectPacs(ConfigSede $sede): void
    {
        self::$originalDefault = Config::get('database.default');

        Config::set('database.connections.mysql_sucursal', [
            'driver' => 'mysql',
            'host' => $sede->ip_dcm,
            'port' => $sede->puerto_dcm,
            'database' => $sede->bd_dcm,
            'username' => $sede->usuario_dcm,
            'password' => $sede->password_dcm,
            'charset' => 'latin1',
            'collation' => 'latin1_swedish_ci',
        ]);

        Config::set('database.default', 'mysql_sucursal');
    }

    /**
     * Restaurar la conexión de base de datos original.
     */
    public static function disconnectPacs(): void
    {
        if (self::$originalDefault !== null) {
            Config::set('database.default', self::$originalDefault);
            self::$originalDefault = null;
        }
    }

    /**
     * Obtener todas las instancias de un estudio desde el PACS.
     */
    public static function getStudyInstances(ConfigSede $sede, string $studyUid): array
    {
        self::connectPacs($sede);

        try {
            DB::purge('mysql_sucursal');

            $results = DB::connection('mysql_sucursal')
                ->table('instance')
                ->join('series', 'instance.series_fk', '=', 'series.pk')
                ->join('study', 'series.study_fk', '=', 'study.pk')
                ->where('study.study_iuid', $studyUid)
                ->select('series.series_iuid', 'instance.sop_iuid', 'study.study_iuid')
                ->get()
                ->toArray();

            return array_map(fn ($row) => [
                'study_iuid' => $row->study_iuid,
                'series_iuid' => $row->series_iuid,
                'sop_iuid' => $row->sop_iuid,
            ], $results);
        } finally {
            self::disconnectPacs();
        }
    }

    /**
     * Descargar un archivo individual vía WADO.
     *
     * @param  string  $contentType  'image/jpeg' para imágenes, 'application/dicom' para archivos DICOM
     */
    public static function downloadViaWado(
        string $ipDcm,
        string $studyUid,
        string $seriesUid,
        string $objectUid,
        string $contentType = 'application/dicom'
    ): ?string {
        $wadoUrl = "http://{$ipDcm}:8080/wado"
            ."?requestType=WADO"
            ."&studyUID={$studyUid}"
            ."&seriesUID={$seriesUid}"
            ."&objectUID={$objectUid}"
            ."&contentType={$contentType}";

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Accept' => $contentType])
                ->get($wadoUrl);

            if ($response->successful()) {
                return $response->body();
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("WADO download failed for {$objectUid}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Descargar todas las imágenes de un estudio.
     *
     * @param  int  $sedeId  ID de la sede
     * @param  string  $studyUid  UID del estudio
     * @param  string  $contentType  'image/jpeg' o 'application/dicom'
     * @param  string  $outputDir  Directorio relativo en storage (ej: 'img_dicom/miuid')
     * @param  int  $limit  Límite de imágenes (0 = sin límite)
     * @return array ['success' => int, 'failed' => int, 'files' => array]
     */
    public static function downloadStudyImages(
        int $sedeId,
        string $studyUid,
        string $contentType = 'application/dicom',
        string $outputDir = '',
        int $limit = 0
    ): array {
        $sede = self::getSede($sedeId);
        if (! $sede) {
            return ['success' => 0, 'failed' => 0, 'files' => [], 'error' => 'Sede no encontrada'];
        }

        $instances = self::getStudyInstances($sede, $studyUid);
        if (empty($instances)) {
            return ['success' => 0, 'failed' => 0, 'files' => [], 'error' => 'No se encontraron instancias'];
        }

        if ($limit > 0) {
            $instances = array_slice($instances, 0, $limit);
        }

        $ipDcm = $sede->ip_dcm;
        $success = 0;
        $failed = 0;
        $files = [];

        foreach ($instances as $inst) {
            $ext = $contentType === 'image/jpeg' ? 'jpg' : 'dcm';
            $filename = "{$studyUid}_{$inst['series_iuid']}_{$inst['sop_iuid']}.{$ext}";

            $data = self::downloadViaWado(
                $ipDcm,
                $inst['study_iuid'],
                $inst['series_iuid'],
                $inst['sop_iuid'],
                $contentType
            );

            if ($data) {
                $path = "{$outputDir}/{$filename}";
                Storage::put($path, $data);
                $files[] = $path;
                $success++;
            } else {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed, 'files' => $files];
    }

    /**
     * Eliminar archivos temporales de imágenes descargadas.
     */
    public static function cleanupTempImages(string $directory): int
    {
        $deleted = 0;

        if (! Storage::exists($directory)) {
            return $deleted;
        }

        $files = Storage::files($directory);
        foreach ($files as $file) {
            Storage::delete($file);
            $deleted++;
        }

        Storage::deleteDirectory($directory);

        return $deleted;
    }
}
