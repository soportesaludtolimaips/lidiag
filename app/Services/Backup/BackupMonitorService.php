<?php

namespace App\Services\Backup;

use App\Models\Configuracion\ConfigSede;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
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
        $driveLetter = DIRECTORY_SEPARATOR === '\\' ? 'C:' : '/';
        if (preg_match('/^([A-Za-z]:)/', $directorioRuta, $matches)) {
            $driveLetter = strtoupper($matches[1]);
        }

        $targetPath = File::exists($directorioRuta)
            ? $directorioRuta
            : (DIRECTORY_SEPARATOR === '\\' ? $driveLetter : base_path());

        $espacioLibreBytes = @disk_free_space($targetPath) ?: (@disk_free_space(base_path()) ?: 0);
        $espacioTotalBytes = @disk_total_space($targetPath) ?: (@disk_total_space(base_path()) ?: 0);
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

            // 1. Archivos físicos en disco central (si se transmiten zips completos)
            $archivosSede = array_filter(
                $archivosDcm,
                fn ($f) => str_starts_with($f->getFilename(), $patron) && str_ends_with($f->getFilename(), ".zip")
            );

            $ultimoArchivo = null;
            $mtimeArchivo = 0;
            if (! empty($archivosSede)) {
                usort($archivosSede, fn ($a, $b) => $b->getMTime() - $a->getMTime());
                $ultimoArchivo = reset($archivosSede);
                $mtimeArchivo = $ultimoArchivo->getMTime();
            }

            // 2. Telemetría reportada por el agente de la sede (Caché, persistencia JSON y auto-recuperación de log)
            $telemetriaDb = $this->obtenerTelemetriaSede($sedeId, "backup_db");
            $telemetriaSync = $this->obtenerTelemetriaSede($sedeId, "sync_imagenes");
            $telemetriaDisco = $this->obtenerTelemetriaSede($sedeId, "disco_imagenes");

            $discoImagenes = $this->resolverDiscoImagenesSede($telemetriaDisco, $telemetriaSync, $telemetriaDb);

            $mtimeTelemetria = 0;
            if ($telemetriaDb && ! empty($telemetriaDb["fecha"])) {
                $mtimeTelemetria = strtotime($telemetriaDb["fecha"]);
            }

            // 3. Evaluar la fuente más reciente (Telemetría de sede vs Archivo físico en Central)
            if ($mtimeTelemetria > 0 && $mtimeTelemetria >= $mtimeArchivo) {
                $fechaCarbon = Carbon::parse($telemetriaDb["fecha"]);
                $horas = (time() - $mtimeTelemetria) / 3600;
                $estadoAgente = strtoupper($telemetriaDb["estado"] ?? "OK");

                $semaforo = "verde";
                $estadoTexto = "Al día";

                if ($horas > 48 || $estadoAgente === "ERROR") {
                    $semaforo = "rojo";
                    $estadoTexto = $estadoAgente === "ERROR" ? "Error en Sede" : "Crítico (>48h)";
                } elseif ($horas > 24 || $estadoAgente === "ADVERTENCIA") {
                    $semaforo = "amarillo";
                    $estadoTexto = $estadoAgente === "ADVERTENCIA" ? "Advertencia" : "Advertencia (>24h)";
                }

                $detalles = $telemetriaDb["detalles"] ?? [];
                $esPrueba = ($detalles["modo"] ?? "") === "TEST_TELEMETRIA";
                $archivoNombre = $detalles["archivo_zip"] ?? $detalles["archivo"] ?? ($esPrueba ? "Telemetría OK (Latido Sede)" : "backup_dcm_sede_{$sedeId}.zip");
                $tamanoBytes = (int) ($detalles["tamano_zip_bytes"] ?? $detalles["tamano_bytes"] ?? 0);
                $tamanoHumano = $detalles["tamano_zip_humano"] ?? $detalles["tamano_humano"] ?? ($tamanoBytes > 0 ? $this->formatearBytes($tamanoBytes) : ($esPrueba ? "Enlace OK" : "OK"));
                $qnapDestino = $detalles["qnap_destino"] ?? $detalles["qnap_transfer"]["qnap_destino"] ?? null;

                $qnapTransfer = $detalles["qnap_transfer"] ?? null;
                $centralTransfer = $detalles["central_transfer"] ?? null;

                $qnapOk = is_array($qnapTransfer) && ($qnapTransfer["estado"] ?? "") === "SUCCESS";
                $centralOk = is_array($centralTransfer) && ($centralTransfer["estado"] ?? "") === "SUCCESS";
                $qnapError = is_array($qnapTransfer) && ($qnapTransfer["estado"] ?? "") === "ERROR";
                $centralError = is_array($centralTransfer) && ($centralTransfer["estado"] ?? "") === "ERROR";

                $transferido = $qnapOk || $centralOk || ($esPrueba && ! empty($qnapDestino));
                $estadoTransferencia = $transferido ? "SUCCESS" : (($qnapError || $centralError) ? "ERROR" : "PENDIENTE");

                $resultado[] = [
                    "sede_id" => $sedeId,
                    "nombre_sede" => $sede->nom_sede,
                    "tiene_backup" => true,
                    "archivo" => $archivoNombre,
                    "tamano_bytes" => $tamanoBytes,
                    "tamano_humano" => $tamanoHumano,
                    "fecha" => $fechaCarbon->format("Y-m-d H:i:s"),
                    "hace" => $fechaCarbon->diffForHumans(),
                    "horas_antiguedad" => round($horas, 1),
                    "semaforo" => $semaforo,
                    "estado_texto" => $estadoTexto,
                    "total_archivos" => max(count($archivosSede), 1),
                    "origen" => "telemetria_sede",
                    "qnap_destino" => $qnapDestino,
                    "transferencia" => [
                        "transferido" => $transferido,
                        "estado" => $estadoTransferencia,
                        "destino_tipo" => $qnapOk ? "QNAP NAS" : ($centralOk ? "Central LIDiag" : ($esPrueba ? "QNAP Conectado" : "Local")),
                        "destino" => $qnapDestino ?: ($centralOk ? "Central LIDiag" : null),
                        "tasa_mbs" => $qnapTransfer["mb_s"] ?? null,
                        "segundos" => $qnapTransfer["segundos"] ?? null,
                        "error" => $qnapTransfer["error"] ?? ($centralError ? ($centralTransfer["error"] ?? null) : null),
                    ],
                    "disco_imagenes" => $discoImagenes,
                    "sync_imagenes" => $telemetriaSync ? [
                        "estado" => $telemetriaSync["estado"] ?? "OK",
                        "hace" => ! empty($telemetriaSync["fecha"]) ? Carbon::parse($telemetriaSync["fecha"])->diffForHumans() : null,
                        "copiados" => $telemetriaSync["detalles"]["archivos_copiados"] ?? 0,
                    ] : null,
                ];
            } elseif ($ultimoArchivo) {
                // Caso tradicional: Archivo físico en disco central
                $tamano = $ultimoArchivo->getSize();
                $horas = (time() - $mtimeArchivo) / 3600;

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
                    "fecha" => date("Y-m-d H:i:s", $mtimeArchivo),
                    "hace" => Carbon::createFromTimestamp($mtimeArchivo)->diffForHumans(),
                    "horas_antiguedad" => round($horas, 1),
                    "semaforo" => $semaforo,
                    "estado_texto" => $estadoTexto,
                    "total_archivos" => count($archivosSede),
                    "origen" => "archivo_central",
                    "qnap_destino" => null,
                    "transferencia" => [
                        "transferido" => true,
                        "estado" => "SUCCESS",
                        "destino_tipo" => "Central",
                        "destino" => $directorioDcm,
                        "tasa_mbs" => null,
                        "segundos" => null,
                        "error" => null,
                    ],
                    "disco_imagenes" => $discoImagenes,
                    "sync_imagenes" => $telemetriaSync ? [
                        "estado" => $telemetriaSync["estado"] ?? "OK",
                        "hace" => ! empty($telemetriaSync["fecha"]) ? Carbon::parse($telemetriaSync["fecha"])->diffForHumans() : null,
                        "copiados" => $telemetriaSync["detalles"]["archivos_copiados"] ?? 0,
                    ] : null,
                ];
            } else {
                // Sede sin archivo físico y sin telemetría
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
                    "origen" => "ninguno",
                    "qnap_destino" => null,
                    "transferencia" => [
                        "transferido" => false,
                        "estado" => "SIN_DATOS",
                        "destino_tipo" => null,
                        "destino" => null,
                        "tasa_mbs" => null,
                        "segundos" => null,
                        "error" => null,
                    ],
                    "disco_imagenes" => $discoImagenes,
                    "sync_imagenes" => $telemetriaSync ? [
                        "estado" => $telemetriaSync["estado"] ?? "OK",
                        "hace" => ! empty($telemetriaSync["fecha"]) ? Carbon::parse($telemetriaSync["fecha"])->diffForHumans() : null,
                        "copiados" => $telemetriaSync["detalles"]["archivos_copiados"] ?? 0,
                    ] : null,
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

    /**
     * Resuelve las métricas de almacenamiento y salud del disco del repositorio de imágenes PACS.
     *
     * @param  array|null  $telemetriaDisco  Telemetría directa de dcm_disk_monitor
     * @param  array|null  $telemetriaSync  Telemetría de sync_dcm_images
     * @param  array|null  $telemetriaDb  Telemetría de backup_dcm_agent
     * @return array|null Información de almacenamiento estructurada
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    protected function resolverDiscoImagenesSede(?array $telemetriaDisco, ?array $telemetriaSync, ?array $telemetriaDb): ?array
    {
        // 1. Fuente primaria: dcm_disk_monitor.py (Monitoreo exhaustivo de discos)
        if ($telemetriaDisco && ! empty($telemetriaDisco["detalles"])) {
            $detallesDisco = $telemetriaDisco["detalles"];
            $dp = $detallesDisco["disco_principal"] ?? (isset($detallesDisco["discos"][0]) ? $detallesDisco["discos"][0] : null);
            if ($dp) {
                $libreGb = isset($dp["free_gb"]) ? (float) $dp["free_gb"] : null;
                $totalGb = isset($dp["total_gb"]) ? (float) $dp["total_gb"] : null;
                $usedGb = isset($dp["used_gb"]) ? (float) $dp["used_gb"] : null;
                $pctUsed = isset($dp["used_pct"]) ? (float) $dp["used_pct"] : (isset($dp["porcentaje_usado"]) ? (float) $dp["porcentaje_usado"] : null);
                $pctFree = isset($dp["free_pct"]) ? (float) $dp["free_pct"] : (isset($dp["porcentaje_libre"]) ? (float) $dp["porcentaje_libre"] : null);
                $estado = strtoupper($dp["estado"] ?? "OK");

                $unidad = "D:";
                if (! empty($dp["unidad"])) {
                    $unidad = $dp["unidad"];
                } elseif (! empty($dp["ruta"]) && preg_match('/^([A-Za-z]:)/', $dp["ruta"], $m)) {
                    $unidad = $m[1];
                }

                $totalTxt = $dp["total_formato"] ?? ($totalGb !== null ? "{$totalGb} GB" : "N/D");
                $libreTxt = $dp["free_formato"] ?? ($libreGb !== null ? "{$libreGb} GB" : "N/D");

                $discosLista = [];
                if (! empty($detallesDisco["discos"]) && is_array($detallesDisco["discos"])) {
                    foreach ($detallesDisco["discos"] as $item) {
                        if (($item["existe"] ?? true) === false) {
                            continue;
                        }
                        $u = "D:";
                        if (! empty($item["unidad"])) {
                            $u = $item["unidad"];
                        } elseif (! empty($item["ruta"]) && preg_match('/^([A-Za-z]:)/', $item["ruta"], $m)) {
                            $u = strtoupper($m[1]);
                        }
                        $iLibreGb = isset($item["free_gb"]) ? (float) $item["free_gb"] : null;
                        $iTotalGb = isset($item["total_gb"]) ? (float) $item["total_gb"] : null;
                        $iUsedGb = isset($item["used_gb"]) ? (float) $item["used_gb"] : null;
                        $iPctUsed = isset($item["used_pct"]) ? (float) $item["used_pct"] : (isset($item["porcentaje_usado"]) ? (float) $item["porcentaje_usado"] : null);
                        $iPctFree = isset($item["free_pct"]) ? (float) $item["free_pct"] : (isset($item["porcentaje_libre"]) ? (float) $item["porcentaje_libre"] : null);
                        $iEstado = strtoupper($item["estado"] ?? "OK");

                        $discosLista[] = [
                            "nombre" => $item["nombre"] ?? "Disco",
                            "unidad" => $u,
                            "ruta" => $item["ruta"] ?? $u,
                            "total_gb" => $iTotalGb,
                            "usado_gb" => $iUsedGb,
                            "libre_gb" => $iLibreGb,
                            "porcentaje_usado" => $iPctUsed,
                            "porcentaje_libre" => $iPctFree,
                            "estado" => $iEstado,
                        ];
                    }
                }

                return [
                    "origen" => "dcm_disk_monitor",
                    "unidad" => $unidad,
                    "nombre" => $dp["nombre"] ?? "Repositorio Imágenes DICOM",
                    "ruta" => $dp["ruta"] ?? "{$unidad}\\img_dcm",
                    "total_gb" => $totalGb,
                    "usado_gb" => $usedGb,
                    "libre_gb" => $libreGb,
                    "porcentaje_usado" => $pctUsed,
                    "porcentaje_libre" => $pctFree,
                    "estado" => $estado,
                    "discos" => $discosLista,
                    "texto_tooltip" => "{$dp['nombre']}: {$libreTxt} libres de {$totalTxt}".($pctUsed !== null ? " ({$pctUsed}% usado)" : ""),
                    "hace" => ! empty($telemetriaDisco["fecha"]) ? Carbon::parse($telemetriaDisco["fecha"])->diffForHumans() : "Reciente",
                ];
            }
        }

        // 2. Fuente secundaria: sync_dcm_images.py (Sincronizador continuo de imágenes)
        if ($telemetriaSync && ! empty($telemetriaSync["detalles"])) {
            $detallesSync = $telemetriaSync["detalles"];
            $discoLocal = $detallesSync["disco_local"] ?? null;
            if (is_array($discoLocal) && isset($discoLocal["libre_gb"])) {
                $libreGb = (float) $discoLocal["libre_gb"];
                $totalGb = isset($discoLocal["total_gb"]) ? (float) $discoLocal["total_gb"] : null;
                $usedGb = isset($discoLocal["usado_gb"]) ? (float) $discoLocal["usado_gb"] : null;
                $pctUsed = isset($discoLocal["porcentaje_usado"]) ? (float) $discoLocal["porcentaje_usado"] : null;
                $pctFree = isset($discoLocal["porcentaje_libre"]) ? (float) $discoLocal["porcentaje_libre"] : null;
                $estado = strtoupper($discoLocal["estado"] ?? "OK");
                $unidad = $discoLocal["unidad"] ?? "D:";

                return [
                    "origen" => "sync_imagenes",
                    "unidad" => $unidad,
                    "nombre" => "Repositorio Imágenes PACS",
                    "ruta" => $discoLocal["ruta"] ?? "{$unidad}\\img_dicom",
                    "total_gb" => $totalGb,
                    "usado_gb" => $usedGb,
                    "libre_gb" => $libreGb,
                    "porcentaje_usado" => $pctUsed,
                    "porcentaje_libre" => $pctFree,
                    "estado" => $estado,
                    "texto_tooltip" => "Disco {$unidad}: {$libreGb} GB libres de ".($totalGb ? "{$totalGb} GB" : "N/D").($pctUsed !== null ? " ({$pctUsed}% usado)" : ""),
                    "hace" => ! empty($telemetriaSync["fecha"]) ? Carbon::parse($telemetriaSync["fecha"])->diffForHumans() : "Reciente",
                ];
            } elseif (isset($detallesSync["espacio_libre_local_gb"])) {
                $libre = (float) $detallesSync["espacio_libre_local_gb"];

                return [
                    "origen" => "sync_imagenes",
                    "unidad" => "D:",
                    "nombre" => "Repositorio Imágenes PACS",
                    "ruta" => "Repositorio PACS",
                    "total_gb" => null,
                    "usado_gb" => null,
                    "libre_gb" => $libre,
                    "porcentaje_usado" => null,
                    "porcentaje_libre" => null,
                    "estado" => $libre < 20 ? "CRITICO" : ($libre < 50 ? "ADVERTENCIA" : "OK"),
                    "texto_tooltip" => "Espacio libre en repositorio: {$libre} GB",
                    "hace" => ! empty($telemetriaSync["fecha"]) ? Carbon::parse($telemetriaSync["fecha"])->diffForHumans() : "Reciente",
                ];
            }
        }

        // 3. Fallback terciario: backup_dcm_agent.py
        if ($telemetriaDb && ! empty($telemetriaDb["detalles"])) {
            $detallesDb = $telemetriaDb["detalles"];
            if (isset($detallesDb["espacio_libre_gb"])) {
                $libre = (float) $detallesDb["espacio_libre_gb"];

                return [
                    "origen" => "backup_db",
                    "unidad" => "C:",
                    "nombre" => "Almacenamiento Sede",
                    "ruta" => "Almacenamiento Local Sede",
                    "total_gb" => null,
                    "usado_gb" => null,
                    "libre_gb" => $libre,
                    "porcentaje_usado" => null,
                    "porcentaje_libre" => null,
                    "estado" => $libre < 20 ? "CRITICO" : ($libre < 50 ? "ADVERTENCIA" : "OK"),
                    "texto_tooltip" => "Espacio libre en servidor de sede: {$libre} GB",
                    "hace" => ! empty($telemetriaDb["fecha"]) ? Carbon::parse($telemetriaDb["fecha"])->diffForHumans() : "Reciente",
                ];
            }
        }

        return null;
    }

    /**
     * Construye un archivo ZIP con el paquete completo y limpio de agentes de sede DCM para su despliegue asistencial.
     *
     * Flujo del proceso:
     * 1. Valida la existencia del directorio fuente en storage/app_lidiag/agente_backups_dcm.
     * 2. Inicializa un archivo ZIP temporal mediante la clase nativa ZipArchive.
     * 3. Itera recursivamente todos los archivos del paquete excluyendo elementos transitorios y sensibles (__pycache__, venv, config.json con contraseñas locales activas).
     * 4. Asegura la inclusión de un directorio vacío logs/ con .gitkeep para preparar el entorno de logging en el servidor de destino.
     * 5. Cierra el archivo y retorna la ruta del archivo generado y metadatos para la descarga.
     *
     * @return array Resultado estructurado con ruta temporal del zip o mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function generarZipAgentesDcm(): array
    {
        $origen = storage_path("app_lidiag/agente_backups_dcm");
        if (! File::isDirectory($origen)) {
            return $this->error("El directorio del paquete de agentes no existe en el servidor central: {$origen}", 404);
        }

        $tmpDir = sys_get_temp_dir();
        $zipNombre = "agentes_dcm_lidiag_".date("Ymd_His").".zip";
        $zipPath = $tmpDir.DIRECTORY_SEPARATOR.$zipNombre;

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return $this->error("No se pudo inicializar el archivo ZIP temporal en {$zipPath}.", 500);
        }

        // Exclusiones de seguridad y archivos transitorios
        $excluidos = [
            "config.json", // Se incluye config.json.example en su lugar
            "cola_dosimetria.db",
            "cola_dosimetria.db-journal",
            "dcm_monitor.log",
            "last_alert_state.json",
        ];

        $archivos = File::allFiles($origen);
        foreach ($archivos as $archivo) {
            $nombreArchivo = $archivo->getFilename();
            $rutaRelativa = $archivo->getRelativePathname();

            // Omitir directorios transitorios (__pycache__, logs, venv, .git)
            if (str_contains($rutaRelativa, "__pycache__") ||
                str_contains($rutaRelativa, ".venv") ||
                str_contains($rutaRelativa, "venv") ||
                str_contains($rutaRelativa, ".git") ||
                str_starts_with($rutaRelativa, "logs".DIRECTORY_SEPARATOR) ||
                str_starts_with($rutaRelativa, "logs/")
            ) {
                continue;
            }

            if (in_array($nombreArchivo, $excluidos, true)) {
                continue;
            }

            // Normalizar separadores a '/' para compatibilidad multiplataforma dentro del zip
            $zipEntryPath = str_replace(DIRECTORY_SEPARATOR, "/", $rutaRelativa);
            $zip->addFile($archivo->getRealPath(), $zipEntryPath);
        }

        // Crear directorio logs/ con .gitkeep para preparar el entorno de bitácoras
        $zip->addFromString("logs/.gitkeep", "# Directorio de bitácoras de los agentes DCM de LIDiag\n");

        $zip->close();

        if (! File::exists($zipPath)) {
            return $this->error("No se pudo verificar la existencia del archivo ZIP generado.", 500);
        }

        return $this->success([
            "path" => $zipPath,
            "nombre" => "agente_backups_dcm.zip",
            "tamano_bytes" => File::size($zipPath),
        ], "Paquete de agentes DCM generado exitosamente.");
    }

    /**
     * Obtiene la telemetría más reciente de una sede implementando una estrategia de resiliencia de tres niveles:
     * 1. Caché rápida en memoria/driver (Cache::get)
     * 2. Persistencia en archivo JSON dedicado (storage/app/telemetria/)
     * 3. Auto-recuperación escaneando bitácoras de logs (backups.log y laravel.log)
     *
     * @param  int  $sedeId  ID de la sede
     * @param  string  $servicio  Nombre del servicio (backup_db, sync_imagenes o disco_imagenes)
     * @return array|null Estructura de telemetría normalizada o null si no existe
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function obtenerTelemetriaSede(int $sedeId, string $servicio): ?array
    {
        // Nivel 1: Caché
        $telemetria = Cache::get("telemetria_sede_{$sedeId}_{$servicio}");
        if (is_array($telemetria) && ! empty($telemetria)) {
            return $telemetria;
        }

        // Nivel 2: Persistencia JSON dedicada
        $rutaArchivo = storage_path("app/telemetria/telemetria_sede_{$sedeId}_{$servicio}.json");
        if (File::exists($rutaArchivo)) {
            $contenido = @file_get_contents($rutaArchivo);
            if (! empty($contenido)) {
                $json = json_decode($contenido, true);
                if (is_array($json) && ! empty($json)) {
                    Cache::put("telemetria_sede_{$sedeId}_{$servicio}", $json, now()->addDays(30));

                    return $json;
                }
            }
        }

        // Nivel 3: Recuperación resiliente desde archivos de log
        $recuperado = $this->recuperarUltimaTelemetriaDeLog($sedeId, $servicio);
        if ($recuperado) {
            try {
                $dirTelemetria = storage_path("app/telemetria");
                if (! File::isDirectory($dirTelemetria)) {
                    File::makeDirectory($dirTelemetria, 0755, true, true);
                }
                File::put($rutaArchivo, json_encode($recuperado, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } catch (\Throwable) {
            }

            return $recuperado;
        }

        return null;
    }

    /**
     * Recupera la telemetría más reciente de una sede desde los logs como mecanismo de auto-recuperación resiliente.
     *
     * @param  int  $sedeId  ID de la sede
     * @param  string  $servicio  Nombre del servicio (backup_db, sync_imagenes o disco_imagenes)
     * @return array|null Estructura de telemetría o null si no se encuentra en el log
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    protected function recuperarUltimaTelemetriaDeLog(int $sedeId, string $servicio): ?array
    {
        $rutasLogs = [
            storage_path("logs/backups.log"),
            storage_path("logs/laravel.log"),
            storage_path("logs/laravel-".date("Y-m-d").".log"),
        ];

        foreach ($rutasLogs as $logPath) {
            if (! File::exists($logPath)) {
                continue;
            }

            $lineas = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (! is_array($lineas) || empty($lineas)) {
                continue;
            }

            // Inspeccionar las líneas más recientes del log desde el final
            $total = count($lineas);
            $limite = max(0, $total - 300);

            for ($i = $total - 1; $i >= $limite; $i--) {
                $linea = $lineas[$i];
                if (str_contains($linea, "[Telemetría {$servicio}]") &&
                    (str_contains($linea, "\"sede_id\":{$sedeId}") || str_contains($linea, "\"sede_id\": {$sedeId}"))
                ) {
                    $fecha = null;
                    if (preg_match('/^\[(?P<fecha>[^\]]+)\]/', $linea, $mFecha)) {
                        $fecha = $mFecha["fecha"];
                    }

                    // Extraer tolerante el JSON entre la primera { y la última }
                    $posInicio = strpos($linea, "{");
                    $posFin = strrpos($linea, "}");

                    if ($posInicio !== false && $posFin !== false && $posFin > $posInicio) {
                        $jsonStr = substr($linea, $posInicio, $posFin - $posInicio + 1);
                        $json = json_decode($jsonStr, true);
                        if (is_array($json) && ($json["sede_id"] ?? null) == $sedeId) {
                            $telemetria = [
                                "servicio" => $servicio,
                                "estado" => strtoupper($json["estado"] ?? "OK"),
                                "mensaje" => $json["mensaje"] ?? "Recuperado de log",
                                "detalles" => $json["detalles"] ?? [],
                                "fecha" => $fecha ? Carbon::parse($fecha)->toIso8601String() : now()->toIso8601String(),
                            ];
                            // Auto-sanar caché para peticiones subsiguientes
                            Cache::put("telemetria_sede_{$sedeId}_{$servicio}", $telemetria, now()->addDays(30));

                            return $telemetria;
                        }
                    }
                }
            }
        }

        return null;
    }
}
