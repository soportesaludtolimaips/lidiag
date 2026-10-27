<?php

namespace App\Exports;

use App\Models\Configuracion\ConfigSede;
use App\Services\Backup\BackupMonitorService;
use App\Services\Backup\BackupService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportador institucional de Excel para el reporte de Salud y Telemetría de Agentes Periféricos.
 *
 * Flujo del proceso:
 * 1. Consulta las sedes activas hospitalarias.
 * 2. Cruza el estado de recepción de backups de bases de datos DCM mediante BackupMonitorService.
 * 3. Cruza los latidos (heartbeats) de telemetría de los agentes Edge de dosimetría y equipos emisores.
 * 4. Mapea la salud integral de la red de agentes con formato institucional Salud Tolima.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
class ReporteAgentesTelemetriaExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected ?int $sedeId;

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
     * Consolida la colección de telemetría de agentes por sede hospitalaria.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function collection(): Collection
    {
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
            $equiposConteo = method_exists($sede, "equiposEmisores") ? $sede->equiposEmisores()->count() : 0;

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

        return collect($filas);
    }

    /**
     * Encabezados de columnas del reporte de agentes.
     *
     * @return array<string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function headings(): array
    {
        return [
            "ID Sede",
            "Sede Hospitalaria",
            "Último Archivo de Respaldo",
            "Fecha/Hora Respaldo",
            "Tamaño Archivo",
            "Antigüedad Respaldo",
            "Estado Agente Backups",
            "Versión Agente Dosis",
            "Último Latido (Heartbeat)",
            "Estado Agente Dosis",
            "Cola Dosis Pendiente",
            "Equipos Emisores Registrados",
        ];
    }

    /**
     * Mapeo de datos para cada fila del reporte Excel.
     *
     * @param  mixed  $row
     * @return array<mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function map($row): array
    {
        return [
            $row->sede_id,
            $row->nombre_sede,
            $row->backup_archivo,
            $row->backup_fecha,
            $row->backup_tamano,
            $row->backup_antiguedad,
            $row->backup_estado,
            $row->dosimetria_version,
            $row->dosimetria_contacto,
            $row->dosimetria_estado,
            $row->dosimetria_pendientes,
            $row->equipos_emisores,
        ];
    }

    /**
     * Anchos de columna.
     *
     * @return array<string, int>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function columnWidths(): array
    {
        return [
            "A" => 10,
            "B" => 28,
            "C" => 36,
            "D" => 20,
            "E" => 16,
            "F" => 18,
            "G" => 22,
            "H" => 18,
            "I" => 22,
            "J" => 22,
            "K" => 18,
            "L" => 24,
        ];
    }

    /**
     * Celda inicial para los datos.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function startCell(): string
    {
        return "A6";
    }

    /**
     * Aplica los estilos institucionales de Salud Tolima.
     *
     * @return array<mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->setShowGridLines(true);

        // Membrete institucional
        $sheet->mergeCells("A2:L2");
        $sheet->setCellValue("A2", "SALUD TOLIMA S.A.S. — TELEMETRÍA Y ESTADO DE AGENTES HOSPITALARIOS");
        $sheet->getStyle("A2")->getFont()->setBold(true)->setSize(14)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("A2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A2:L2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0F172A");

        $sheet->mergeCells("A3:L3");
        $sheet->setCellValue("A3", "Consolidado de Salud Periférica: Respaldos de Base de Datos, Imágenes PACS y Agentes Edge de Dosimetría");
        $sheet->getStyle("A3")->getFont()->setItalic(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("A3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A3:L3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF334155");

        $sheet->mergeCells("A4:L4");
        $sheet->setCellValue("A4", "Generado el: ".date("Y-m-d H:i:s")." | Sistema LIDiag — Auditoría de Infraestructura y Agentes");
        $sheet->getStyle("A4")->getFont()->setSize(9)->getColor()->setARGB("FF475569");
        $sheet->getStyle("A4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Encabezados de tabla
        $sheet->getStyle("A6:L6")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("A6:L6")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1E293B");
        $sheet->getStyle("A6:L6")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A6:L6")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(6)->setRowHeight(28);

        // Bordes y alineación de datos
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 7) {
            $sheet->getStyle("A6:L{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFE2E8F0");
            $sheet->getStyle("A7:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D7:G{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H7:L{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
