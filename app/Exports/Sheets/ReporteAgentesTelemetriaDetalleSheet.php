<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de Detalle de Telemetría, Backups y Agentes Hospitalarios.
 *
 * Flujo del proceso:
 * 1. Muestra todas las sedes hospitalarias con su ID y nombre.
 * 2. Detalla el último archivo de respaldo, fecha, peso, horas transcurridas y estado.
 * 3. Detalla la versión de agente dosimetría, último latido, cola de espera y equipos emisores.
 * 4. Aplica estilos institucionales Salud Tolima Slate/Dark.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteAgentesTelemetriaDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected Collection $rows
    ) {}

    /**
     * Título de la pestaña.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function title(): string
    {
        return "Detalle Agentes";
    }

    /**
     * Retorna la colección de filas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * Encabezados de columnas.
     *
     * @return array<string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
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
     * Mapeo de columnas.
     *
     * @param  mixed  $row
     * @return array<mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
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
     * Celda inicial.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function startCell(): string
    {
        return "A6";
    }

    /**
     * Anchos de columna.
     *
     * @return array<string, int>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
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
     * Estilos de la hoja de detalle.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
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
