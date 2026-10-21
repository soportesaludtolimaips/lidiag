<?php

namespace App\Services\Backup;

use App\Models\Configuracion\ConfigSede;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class BackupMonitorService extends BaseService
{
    /**
     * Servicio de backups del sistema principal.
     */
    protected BackupService $backupService;

    /**
     * Constructor del servicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Obtiene el estado consolidado de salud de los backups de toda la red LIDiag.
     *
     * Flujo del proceso:
     * 1. Consulta las métricas de almacenamiento del disco local y destino de backups.
     * 2. Evalúa los respaldos centrales de la base de datos principal MySQL y archivos de storage.
     * 3. Evalúa el estado de sincronización y recepción de backups DCM de cada sede hospitalaria activa.
     * 4. Extrae las últimas líneas de la bitácora de eventos de backups.
     * 5. Retorna la estructura estandarizada con indicadores y semáforos de salud.
     *
     * @return array Estructura estandarizada con métricas y estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function obtenerEstadoGeneral(): array
    {
        $directorioDestino = config("backup.destination_path", "C:\\Backups_Lidiag");
        $directorioDcm = config("backup.dcm_destination_path", "C:\\Backups_Lidiag\\dcm_sedes");

        $almacenamiento = $this->obtenerMetricasDisco($directorioDestino);
        $backupsCentrales = $this->obtenerBackupsCentrales($directorioDestino);
        $sedesDcm = $this->obtenerEstadoSedesDcm($directorioDcm);
        $logsRecientes = $this->obtenerLogsRecientes(30);

        $totalSedes = count($sedesDcm);
        $sedesAlDia = count(array_filter($sedesDcm, fn ($s) => $s["semaforo"] === "verde"));
        $sedesAdvertencia = count(array_filter($sedesDcm, fn ($s) => $s["semaforo"] === "amarillo"));
        $sedesCriticas = count(array_filter($sedesDcm, fn ($s) => $s["semaforo"] === "rojo"));

        return $this->success([
            "almacenamiento" => $almacenamiento,
            "centrales" => $backupsCentrales,
            "resumen_sedes" => [
                "total" => $totalSedes,
                "al_dia" => $sedesAlDia,
                "advertencia" => $sedesAdvertencia,
                "critico" => $sedesCriticas,
                "salud_porcentaje" => $totalSedes > 0 ? round(($sedesAlDia / $totalSedes) * 100, 1) : 100,
            ],
            "sedes" => $sedesDcm,
            "logs" => $logsRecientes,
            "fecha_consulta" => now()->format("Y-m-d H:i:s"),
        ]);
    }

    /**
     * Consulta el espacio disponible, usado y total del disco que almacena los backups.
     *
     * @param  string  $directorioRuta  Ruta del directorio de backups
     * @return array Métricas de almacenamiento
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function obtenerMetricasDisco(string $directorioRuta): array
    {
        $driveLetter = "C:";
        if (preg_match('/^([A-Za-z]:)/', $directorioRuta, $matches)) {
            $driveLetter = strtoupper($matches[1]);
        }

        $espacioLibreBytes = @disk_free_space($driveLetter) ?: 0;
        $espacioTotalBytes = @disk_total_space($driveLetter) ?: 0;
        $espacioUsadoBytes = max(0, $espacioTotalBytes - $espacioLibreBytes);

        $porcentajeUsado = $espacioTotalBytes > 0
            ? round(($espacioUsadoBytes / $espacioTotalBytes) * 100, 1)
            : 0;

        return [
            "unidad" => $driveLetter,
            "directorio" => $directorioRuta,
            "existe_directorio" => File::exists($directorioRuta),
            "espacio_total_bytes" => $espacioTotalBytes,
            "espacio_total_humano" => $this->formatearBytes($espacioTotalBytes),
            "espacio_libre_bytes" => $espacioLibreBytes,
            "espacio_libre_humano" => $this->formatearBytes($espacioLibreBytes),
            "espacio_usado_bytes" => $espacioUsadoBytes,
            "espacio_usado_humano" => $this->formatearBytes($espacioUsadoBytes),
            "porcentaje_usado" => $porcentajeUsado,
            "porcentaje_libre" => round(100 - $porcentajeUsado, 1),
            "estado_espacio" => $porcentajeUsado >= 90 ? "critico" : ($porcentajeUsado >= 80 ? "advertencia" : "optimo"),
        ];
    }

    /**
     * Inspecciona los archivos de backup generados del sistema central.
     *
     * @param  string  $directorioDestino  Directorio de backups centrales
     * @return array Detalle de backups de BD y storage
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    protected function obtenerBackupsCentrales(string $directorioDestino): array
    {
        if (! File::exists($directorioDestino)) {
            return [
                "ultimo_db" => null,
                "ultimo_storage" => null,
                "archivos_totales" => 0,
            ];
        }

        $archivosDb = File::glob(rtrim($directorioDestino, "\\/").DIRECTORY_SEPARATOR."backup_lidiag_db_*.zip");
        $archivosStorage = File::glob(rtrim($directorioDestino, "\\/").DIRECTORY_SEPARATOR."backup_lidiag_storage_*.zip");

        $ultimoDb = null;
        if (! empty($archivosDb)) {
            usort($archivosDb, fn ($a, $b) => filemtime($b) - filemtime($a));
            $rutaDb = $archivosDb[0];
            $mtimeDb = filemtime($rutaDb);
            $tamanoDb = filesize($rutaDb);
            $shaPath = $rutaDb.".sha256";

            $ultimoDb = [
                "archivo" => basename($rutaDb),
                "ruta" => $rutaDb,
                "tamano_bytes" => $tamanoDb,
                "tamano_humano" => $this->formatearBytes($tamanoDb),
                "fecha" => date("Y-m-d H:i:s", $mtimeDb),
                "hace" => Carbon::createFromTimestamp($mtimeDb)->diffForHumans(),
                "tiene_sha256" => File::exists($shaPath),
                "horas_antiguedad" => round((time() - $mtimeDb) / 3600, 1),
                "estado" => (time() - $mtimeDb) <= 86400 ? "al_dia" : "desactualizado",
            ];
        }

        $ultimoStorage = null;
        if (! empty($archivosStorage)) {
            usort($archivosStorage, fn ($a, $b) => filemtime($b) - filemtime($a));
            $rutaSt = $archivosStorage[0];
            $mtimeSt = filemtime($rutaSt);
            $tamanoSt = filesize($rutaSt);

            $ultimoStorage = [
                "archivo" => basename($rutaSt),
                "ruta" => $rutaSt,
                "tamano_bytes" => $tamanoSt,
                "tamano_humano" => $this->formatearBytes($tamanoSt),
                "fecha" => date("Y-m-d H:i:s", $mtimeSt),
                "hace" => Carbon::createFromTimestamp($mtimeSt)->diffForHumans(),
                "horas_antiguedad" => round((time() - $mtimeSt) / 3600, 1),
            ];
        }

        return [
            "ultimo_db" => $ultimoDb,
            "ultimo_storage" => $ultimoStorage,
            "total_archivos_db" => count($archivosDb),
            "total_archivos_storage" => count($archivosStorage),
        ];
    }

    /**
     * Evalúa el estado de sincronización y recepción de backups DCM de cada sede hospitalaria.
     *
     * @param  string  $directorioDcm  Carpeta contenedora de zips de sedes
     * @return array Listado de sedes con estado de backup
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    protected function obtenerEstadoSedesDcm(string $directorioDcm): array
    {
        $sedes = ConfigSede::where("estado", true)->orderBy("id")->get();
        $resultado = [];

        $archivosDcm = File::exists($directorioDcm)
            ? File::files($directorioDcm)
            : [];

        foreach ($sedes as $sede) {
            $sedeId = $sede->id;
            $patron = "backup_dcm_sede_{$sedeId}_";

            $archivosSede = array_filter(
                $archivosDcm,
                fn ($f) => str_starts_with($f->getFilename(), $patron) && str_ends_with($f->getFilename(), ".zip")
            );

            if (! empty($archivosSede)) {
                usort($archivosSede, fn ($a, $b) => $b->getMTime() - $a->getMTime());
                $ultimoArchivo = reset($archivosSede);
                $mtime = $ultimoArchivo->getMTime();
                $tamano = $ultimoArchivo->getSize();
                $horas = (time() - $mtime) / 3600;

                $semaforo = "verde";
                $estadoTexto = "Al día";

                if ($horas > 48) {
                    $semaforo = "rojo";
                    $estadoTexto = "Crítico (>48h)";
                } elseif ($horas > 24) {
                    $semaforo = "amarillo";
                    $estadoTexto = "Advertencia (>24h)";
                }

                $resultado[] = [
                    "sede_id" => $sedeId,
                    "nombre_sede" => $sede->nom_sede,
                    "tiene_backup" => true,
                    "archivo" => $ultimoArchivo->getFilename(),
                    "tamano_bytes" => $tamano,
                    "tamano_humano" => $this->formatearBytes($tamano),
                    "fecha" => date("Y-m-d H:i:s", $mtime),
                    "hace" => Carbon::createFromTimestamp($mtime)->diffForHumans(),
                    "horas_antiguedad" => round($horas, 1),
                    "semaforo" => $semaforo,
                    "estado_texto" => $estadoTexto,
                    "total_archivos" => count($archivosSede),
                ];
            } else {
                $resultado[] = [
                    "sede_id" => $sedeId,
                    "nombre_sede" => $sede->nom_sede,
                    "tiene_backup" => false,
                    "archivo" => null,
                    "tamano_bytes" => 0,
                    "tamano_humano" => "0 B",
                    "fecha" => null,
                    "hace" => "Sin registros",
                    "horas_antiguedad" => 9999,
                    "semaforo" => "rojo",
                    "estado_texto" => "Sin backups",
                    "total_archivos" => 0,
                ];
            }
        }

        return $resultado;
    }

    /**
     * Lee y formatea las líneas más recientes del archivo de log de backups.
     *
     * @param  int  $limiteLineas  Cantidad máxima de líneas a recuperar
     * @return array Líneas del log estructuradas
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    protected function obtenerLogsRecientes(int $limiteLineas = 30): array
    {
        $logPath = storage_path("logs/backups.log");
        if (! File::exists($logPath)) {
            return [];
        }

        $contenido = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! is_array($contenido) || empty($contenido)) {
            return [];
        }

        $ultimas = array_slice($contenido, -$limiteLineas);
        $lineasFormateadas = [];

        foreach (array_reverse($ultimas) as $linea) {
            $tipo = "info";
            if (str_contains($linea, "ERROR")) {
                $tipo = "error";
            } elseif (str_contains($linea, "WARNING")) {
                $tipo = "warning";
            } elseif (str_contains($linea, "SUCCESS") || str_contains($linea, "completado exitosamente")) {
                $tipo = "success";
            }

            $lineasFormateadas[] = [
                "texto" => $linea,
                "tipo" => $tipo,
            ];
        }

        return $lineasFormateadas;
    }

    /**
     * Formatea una cantidad de bytes a una unidad legible (B, KB, MB, GB, TB).
     *
     * @param  int|float  $bytes  Bytes a convertir
     * @param  int  $precision  Decimales deseados
     * @return string Cadena legible
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function formatearBytes(int|float $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return "0 B";
        }

        $unidades = ["B", "KB", "MB", "GB", "TB"];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($unidades) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision)." ".$unidades[$pow];
    }
}
