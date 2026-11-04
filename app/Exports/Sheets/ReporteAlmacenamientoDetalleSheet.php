<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
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
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de Detalle de Almacenamiento, Servidores y Nodos Edge PACS.
 *
 * Flujo del proceso:
 * 1. Lista exhaustiva del Servidor Central y todos los nodos / sedes conectadas.
 * 2. Mapea capacidades de almacenamiento, estado del token, telemetría de backup y sincronización.
 * 3. Formatea con cabeceras institucionales Blue Grey y bordes limpios.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteAlmacenamientoDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return "Detalle Servidores";
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
     * Mapeo de columnas.
     *
     * @param  mixed  $row
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($row): array
    {
        return [
            $row->tipo,
            $row->nombre,
            $row->ip,
            $row->almacenamiento,
            $row->estado_disco,
            $row->backup_estado,
            $row->backup_tiempo,
            $row->sync_estado,
            $row->dosimetria_estado,
            $row->observacion,
        ];
    }

    /**
     * Encabezados de columnas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function headings(): array
    {
        return [
            "Tipo Nodo",
            "Servidor / Sede",
            "Dirección IP / Host",
            "Almacenamiento / Capacidad",
            "Estado Disco / Token",
            "Agente Backup DB",
            "Último Respaldo Recibido",
            "Agente Sync PACS",
            "Agente Dosimetría",
            "Diagnóstico / Observación",
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
        return "A4";
    }

    /**
     * Anchos de columna.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function columnWidths(): array
    {
        return [
            "A" => 20, // Tipo
            "B" => 30, // Servidor / Sede
            "C" => 24, // IP
            "D" => 40, // Almacenamiento
            "E" => 24, // Estado Disco
            "F" => 20, // Backup DB
            "G" => 30, // Último Respaldo
            "H" => 20, // Sync PACS
            "I" => 20, // Dosimetría
            "J" => 35, // Observación
        ];
    }

    /**
     * Estilos de la hoja.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        // Banner institucional
        $sheet->mergeCells("A1:J1");
        $sheet->setCellValue("A1", "SALUD TOLIMA / LIDIAG — MONITOR DE ALMACENAMIENTO, DISCOS Y SERVIDORES PACS");
        $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14)->setColor(new Color("FFFFFFFF"));
        $sheet->getStyle("A1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF263238"); // Blue Grey
        $sheet->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Subtítulo
        $sheet->mergeCells("A2:J2");
        $sheet->setCellValue("A2", "Corte en tiempo real: ".Carbon::now()->format("d/m/Y H:i:s")." | Umbrales: Advertencia <15% (50GB), Crítico <5% (20GB)");
        $sheet->getStyle("A2")->getFont()->setItalic(true)->setSize(10)->setColor(new Color("FF333333"));
        $sheet->getStyle("A2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Encabezados tabla (Fila 4)
        $sheet->getStyle("A4:J4")->getFont()->setBold(true)->setColor(new Color("FFFFFFFF"));
        $sheet->getStyle("A4:J4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF455A64");
        $sheet->getStyle("A4:J4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Bordes
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 5) {
            $sheet->getStyle("A4:J{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFCCCCCC");
            $sheet->getStyle("A5:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C5:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E5:I{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
