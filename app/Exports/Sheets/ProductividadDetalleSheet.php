<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
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
 * Hoja de Detalle de Productividad Médica, Estudios Liquidados y Excluidos.
 *
 * Flujo del proceso:
 * 1. Mapea todas las lecturas de los radiólogos con CUPS, procedimiento, modalidad y valor.
 * 2. Intercala subtotales de honorarios por médico y fila de gran total general.
 * 3. Aplica estilos corporativos Salud Tolima Azul Zafiro.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ProductividadDetalleSheet implements FromCollection, WithColumnFormatting, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  array<int, int>  $filasResaltadas
     */
    public function __construct(
        protected Collection $filas,
        protected string $nomSede,
        protected string $periodo,
        protected array $filasResaltadas = []
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
        return "Detalle Productividad";
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
        return $this->filas;
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
     * Encabezados de la tabla.
     *
     * @return array<int, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function headings(): array
    {
        return [
            "Médico Radiólogo",
            "Fecha Lectura",
            "CUPS",
            "Procedimiento",
            "Clase / Modalidad",
            "Tarifa Liquidada",
            "Estado / Observación",
        ];
    }

    /**
     * Mapeo de una fila a columnas de Excel.
     *
     * @param  array  $fila
     * @return array<int, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($fila): array
    {
        return [
            $fila["medico"],
            $fila["fecha"],
            $fila["cod_cups"],
            $fila["nom_produc"],
            $fila["clase"],
            $fila["valor"],
            $fila["observacion"],
        ];
    }

    /**
     * Anchos de columna optimizados.
     *
     * @return array<string, float>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function columnWidths(): array
    {
        return [
            "A" => 35,
            "B" => 15,
            "C" => 15,
            "D" => 45,
            "E" => 18,
            "F" => 18,
            "G" => 35,
        ];
    }

    /**
     * Formatos de columna.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function columnFormats(): array
    {
        return [
            "F" => '"$"#,##0',
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
        // Membrete principal
        $sheet->mergeCells("A1:G1");
        $sheet->setCellValue("A1", "SALUD TOLIMA — REPORTE DE PRODUCTIVIDAD Y HONORARIOS MÉDICOS");
        $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("A1:G1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1E3A8A");
        $sheet->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells("A2:G2");
        $sheet->setCellValue("A2", "Sede: {$this->nomSede} | Periodo Liquidado: {$this->periodo}");
        $sheet->getStyle("A2")->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("A2:G2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF2563EB");
        $sheet->getStyle("A2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Encabezados tabla (Fila 4)
        $sheet->getStyle("A4:G4")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("A4:G4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1E3A8A");
        $sheet->getStyle("A4:G4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(24);

        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 5) {
            $sheet->getStyle("A4:G{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFBFDBFE");
            $sheet->getStyle("B5:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E5:E{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Resaltar subtotales y total
            foreach ($this->filasResaltadas as $rf) {
                if ($rf <= $highestRow) {
                    $sheet->getStyle("A{$rf}:G{$rf}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$rf}:G{$rf}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FFDBEAFE");
                }
            }
        }

        return [];
    }
}
