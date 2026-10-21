<?php

namespace App\Exports;

use App\Models\Configuracion\ConfigSede;
use App\Services\Liquidacion\LiquidacionService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
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
 * Exportación de la liquidación de honorarios médicos a Excel.
 *
 * Flujo del proceso:
 * 1. Recibe sede y rango de fechas por constructor.
 * 2. Delega el cálculo a LiquidacionService::calcular().
 * 3. Expone las líneas liquidables con encabezados y diseño corporativo Salud Tolima.
 * 4. Aplica formato de moneda y fila de total acumulado.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class LiquidacionExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected string $nomSede = "";

    protected float $granTotal = 0.0;

    protected int $totalLineas = 0;

    /**
     * Guarda los filtros del periodo a liquidar y resuelve la sede.
     *
     * @param  int  $sedeId  Sede liquidada.
     * @param  string  $fechaInicio  Inicio en Y-m-d.
     * @param  string  $fechaFin  Fin en Y-m-d.
     * @param  int|null  $contratoId  Contrato opcional para acotar la vista.
     * @param  int|null  $medicoId  Médico opcional para acotar la vista.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function __construct(
        protected int $sedeId,
        protected string $fechaInicio,
        protected string $fechaFin,
        protected ?int $contratoId = null,
        protected ?int $medicoId = null
    ) {
        $sede = ConfigSede::find($this->sedeId);
        $this->nomSede = $sede ? $sede->nom_sede : "Sede #{$this->sedeId}";
    }

    /**
     * Entrega las líneas de la liquidación como colección, agregando la fila de total.
     *
     * @returns \Illuminate\Support\Collection<int, array> Líneas de la liquidación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function collection()
    {
        $result = app(LiquidacionService::class)->calcular(
            $this->sedeId,
            $this->fechaInicio,
            $this->fechaFin,
            $this->contratoId,
            $this->medicoId
        );

        $lineas = $result["data"]["lineas"] ?? [];
        $this->totalLineas = count($lineas);
        $this->granTotal = (float) ($result["data"]["totales"]["gran_total"] ?? 0.0);

        $coleccion = collect($lineas);

        if ($this->totalLineas > 0) {
            $coleccion->push([
                "es_total" => true,
                "medico_name" => "GRAN TOTAL LIQUIDADO",
                "cod_cups" => "",
                "nom_produc" => "Total acumulado de honorarios médicos",
                "clase" => "{$this->totalLineas} lecturas",
                "valor" => $this->granTotal,
                "fecha_lectura" => "{$this->fechaInicio} a {$this->fechaFin}",
                "contrato_id" => "",
            ]);
        }

        return $coleccion;
    }

    /**
     * Define la celda inicial donde se ubicarán los encabezados de los datos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function startCell(): string
    {
        return "A4";
    }

    /**
     * Entrega los encabezados de la tabla en español.
     *
     * @returns array<int, string> Encabezados de columnas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function headings(): array
    {
        return ["Médico", "CUPS", "Producto", "Clase", "Valor", "Fecha lectura", "Contrato"];
    }

    /**
     * Mapea una línea de la liquidación a una fila de Excel.
     *
     * @param  array  $linea  Registro de liquidación.
     * @return array<int, mixed> Fila estructurada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
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
     * Define los anchos de columna de la hoja.
     *
     * @returns array<string, float> Anchos por columna.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
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
     * Formatos numéricos para las celdas (moneda en columna E).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function columnFormats(): array
    {
        return [
            "E" => '"$"#,##0',
        ];
    }

    /**
     * Aplica diseño institucional Salud Tolima con cabecera y bordes.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo.
     * @return array Estilos por fila.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
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
