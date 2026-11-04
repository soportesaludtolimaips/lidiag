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
 * Hoja de Detalle de Líneas de Liquidación de Honorarios Médicos.
 *
 * Flujo del proceso:
 * 1. Mapea cada estudio liquidado con código CUPS, descripción, clase, valor y médico.
 * 2. Agrega la fila de total acumulado al final.
 * 3. Aplica estilos corporativos Salud Tolima Azul Oscuro.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class LiquidacionDetalleSheet implements FromCollection, WithColumnFormatting, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected Collection $lineas,
        protected string $nomSede,
        protected string $fechaInicio,
        protected string $fechaFin,
        protected float $granTotal,
        protected int $totalLineas
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
        return "Detalle Liquidación";
    }

    /**
     * Retorna la colección de líneas incluyendo la fila de total.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function collection(): Collection
    {
        return $this->lineas;
    }

    /**
     * Celda de inicio.
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
     * Encabezados.
     *
     * @return array<int, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function headings(): array
    {
        return ["Médico", "CUPS", "Producto", "Clase", "Valor", "Fecha lectura", "Contrato"];
    }

    /**
     * Mapeo de columnas.
     *
     * @param  array  $linea
     * @return array<int, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($linea): array
    {
        return [
            $linea["medico_name"],
            $linea["cod_cups"],
            $linea["nom_produc"],
            $linea["clase"],
            $linea["valor"],
            $linea["fecha_lectura"],
            $linea["contrato_id"] ? "#{$linea['contrato_id']}" : "",
        ];
    }

    /**
     * Anchos de columna.
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
            "C" => 50,
            "D" => 16,
            "E" => 18,
            "F" => 18,
            "G" => 14,
        ];
    }

    /**
     * Formatos numéricos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function columnFormats(): array
    {
        return [
            "E" => '"$"#,##0',
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
        // Título institucional superior
        $sheet->setCellValue("A1", "Salud Tolima - Reporte de Liquidación de Honorarios Médicos");
        $sheet->mergeCells("A1:G1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "FFFFFF"],
                "size" => 13,
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A2274"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Subtítulo con sede y rango
        $sheet->setCellValue("A2", "Sede: {$this->nomSede}  |  Periodo: {$this->fechaInicio} al {$this->fechaFin}");
        $sheet->mergeCells("A2:G2");
        $sheet->getStyle("A2")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "333333"],
                "size" => 10,
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "E9ECEF"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Encabezado de la tabla de datos (fila 4)
        $sheet->getStyle("A4:G4")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "FFFFFF"],
                "size" => 10,
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A2274"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(22);

        $highestRow = $sheet->getHighestRow();

        if ($highestRow >= 5) {
            // Bordes en todas las celdas de datos
            $sheet->getStyle("A4:G{$highestRow}")->applyFromArray([
                "borders" => [
                    "allBorders" => [
                        "borderStyle" => Border::BORDER_THIN,
                        "color" => ["rgb" => "CCCCCC"],
                    ],
                ],
            ]);

            // Si hay fila de gran total al final, resaltarla
            if ($this->totalLineas > 0) {
                $sheet->getStyle("A{$highestRow}:G{$highestRow}")->applyFromArray([
                    "font" => [
                        "bold" => true,
                        "size" => 11,
                    ],
                    "fill" => [
                        "fillType" => Fill::FILL_SOLID,
                        "startColor" => ["rgb" => "E8F4FD"],
                    ],
                ]);
            }
        }

        return [
            4 => ["font" => ["bold" => true]],
        ];
    }
}
