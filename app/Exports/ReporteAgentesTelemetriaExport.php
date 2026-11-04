<?php

namespace App\Exports;

use App\Exports\Sheets\ReporteAgentesTelemetriaDetalleSheet;
use App\Exports\Sheets\ReporteAgentesTelemetriaEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use App\Services\Backup\BackupMonitorService;
use App\Services\Backup\BackupService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el reporte de Salud y Telemetría de Agentes Periféricos.
 *
 * Flujo del proceso:
 * 1. Consulta las sedes activas hospitalarias.
 * 2. Cruza el estado de recepción de backups de bases de datos DCM mediante BackupMonitorService.
 * 3. Cruza los latidos (heartbeats) de telemetría de los agentes Edge de dosimetría y equipos emisores.
 * 4. Genera un libro Excel con 2 hojas: Estadísticas Ejecutivas y Detalle de Agentes.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
class ReporteAgentesTelemetriaExport implements WithMultipleSheets
{
    protected ?int $sedeId;

    /**
     * Cache de datos de telemetría.
     */
    protected ?Collection $cachedData = null;

    /**
     * Constructor del reporte de telemetría de agentes periféricos.
     *
     * @param  int|null  $sedeId  Identificador de sede o null para todas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function __construct(?int $sedeId = null)
    {
        $this->sedeId = $sedeId;
    }

    /**
     * Retorna las hojas del libro de cálculo.
     *
     * @return array<int, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function sheets(): array
    {
        $data = $this->getData();

        return [
            new ReporteAgentesTelemetriaEstadisticasSheet($data),
            new ReporteAgentesTelemetriaDetalleSheet($data),
        ];
    }

    /**
     * Consolida la colección de telemetría de agentes por sede hospitalaria.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function getData(): Collection
    {
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }

        $monitorService = new BackupMonitorService(new BackupService);
        $directorioDcm = config("backup.dcm_destination_path", "C:\\Backups_Lidiag\\dcm_sedes");

        $query = ConfigSede::where("estado", true);
        if ($this->sedeId) {
            $query->where("id", $this->sedeId);
        }
        $sedes = $query->orderBy("id")->get();

        $filas = [];

        foreach ($sedes as $sede) {
            $sedeId = $sede->id;

            // 1. Estado del agente de backups (MySQL / PACS)
            $patron = "backup_dcm_sede_{$sedeId}_";
            $archivosDcm = File::exists($directorioDcm)
                ? File::files($directorioDcm)
                : [];

            $archivosSede = array_filter(
                $archivosDcm,
                fn ($f) => str_starts_with($f->getFilename(), $patron) && str_ends_with($f->getFilename(), ".zip")
            );

            $ultimoBackupNombre = "Sin backups";
            $ultimoBackupFecha = "N/A";
            $ultimoBackupTamano = "0 B";
            $ultimoBackupHoras = "N/A";
            $ultimoBackupEstado = "Sin registros";

            if (! empty($archivosSede)) {
                usort($archivosSede, fn ($a, $b) => $b->getMTime() - $a->getMTime());
                $ultimoArchivo = reset($archivosSede);
                $mtime = $ultimoArchivo->getMTime();
                $tamano = $ultimoArchivo->getSize();
                $horas = round((time() - $mtime) / 3600, 1);

                $ultimoBackupNombre = $ultimoArchivo->getFilename();
                $ultimoBackupFecha = date("Y-m-d H:i:s", $mtime);
                $ultimoBackupTamano = $monitorService->formatearBytes($tamano);
                $ultimoBackupHoras = $horas." h";

                if ($horas <= 24) {
                    $ultimoBackupEstado = "Al día (<= 24h)";
                } elseif ($horas <= 48) {
                    $ultimoBackupEstado = "Advertencia (> 24h)";
                } else {
                    $ultimoBackupEstado = "Crítico (> 48h)";
                }
            }

            // 2. Estado del agente de dosimetría edge (Heartbeat en memoria)
            $heartbeatKey = "dosimetria_heartbeat_{$sedeId}";
            $heartbeat = Cache::get($heartbeatKey);

            $dosimetriaVersion = "N/A";
            $dosimetriaUltimoContacto = "Sin contacto";
            $dosimetriaEstado = "Offline";
            $dosimetriaPendientes = 0;

            if ($heartbeat && isset($heartbeat["timestamp"])) {
                $minutos = Carbon::parse($heartbeat["timestamp"])->diffInMinutes(Carbon::now());
                $dosimetriaVersion = $heartbeat["version"] ?? "1.0.0";
                $dosimetriaUltimoContacto = $heartbeat["ultimo_contacto"] ?? Carbon::parse($heartbeat["timestamp"])->format("Y-m-d H:i:s");
                $dosimetriaPendientes = (int) ($heartbeat["pendientes"] ?? 0);

                if ($minutos <= 15) {
                    $dosimetriaEstado = "Online (Activo)";
                } elseif ($minutos <= 30) {
                    $dosimetriaEstado = "Retrasado (16-30 min)";
                } else {
                    $dosimetriaEstado = "Offline (> 30 min)";
                }
            }

            // 3. Conteo de equipos emisores de radiación
            $equiposConteo = $sede->equiposEmisores()->count();

            $filas[] = (object) [
                "sede_id" => $sedeId,
                "nombre_sede" => $sede->nom_sede,
                "backup_archivo" => $ultimoBackupNombre,
                "backup_fecha" => $ultimoBackupFecha,
                "backup_tamano" => $ultimoBackupTamano,
                "backup_antiguedad" => $ultimoBackupHoras,
                "backup_estado" => $ultimoBackupEstado,
                "dosimetria_version" => $dosimetriaVersion,
                "dosimetria_contacto" => $dosimetriaUltimoContacto,
                "dosimetria_estado" => $dosimetriaEstado,
                "dosimetria_pendientes" => $dosimetriaPendientes,
                "equipos_emisores" => $equiposConteo,
            ];
        }

        $this->cachedData = collect($filas);

        return $this->cachedData;
    }

    /**
     * Compatibilidad con colecciones directas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function collection(): Collection
    {
        return $this->getData();
    }
}
