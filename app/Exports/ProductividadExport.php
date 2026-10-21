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
 * Exportación a Excel del Reporte de Productividad Médica y Honorarios por corte mensual.
 *
 * Flujo del proceso:
 * 1. Recibe la sede y el periodo (YYYY-MM) a liquidar.
 * 2. Consulta la producción congelada mediante LiquidacionService::productividad().
 * 3. Aplana las líneas de producción y lecturas excluidas organizadas por médico.
 * 4. Aplica diseño institucional Salud Tolima con formatos numéricos y subtotales.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class ProductividadExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected string $nomSede = "";

    protected float $granTotal = 0.0;

    protected array $filasResaltadas = [];

    /**
     * Inicializa el exportador con los filtros de sede y periodo.
     *
     * @param  int  $sedeId  Identificador de la sede.
     * @param  string  $periodo  Periodo en formato YYYY-MM.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function __construct(
        protected int $sedeId,
        protected string $periodo
    ) {
        $sede = ConfigSede::find($this->sedeId);
        $this->nomSede = $sede ? $sede->nom_sede : "Sede #{$this->sedeId}";
    }

    /**
     * Construye la colección de datos a exportar, aplanando lecturas y subtotales.
     *
     * @returns \Illuminate\Support\Collection<int, array>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function collection()
    {
        $result = app(LiquidacionService::class)->productividad($this->sedeId, $this->periodo);
        $medicos = $result["data"]["medicos"] ?? [];
        $this->granTotal = (float) ($result["data"]["gran_total"] ?? 0.0);

        $filas = [];
        $numeroFila = 5; // A4 es encabezado, datos empiezan en fila 5

        foreach ($medicos as $m) {
            $nombreMedico = $m["medico_name"] ?: "Sin médico asignado";

            // Líneas liquidadas
            foreach ($m["lineas"] as $l) {
                $fecha = $l->fecha_lectura ? substr((string) $l->fecha_lectura, 0, 10) : "";
                $filas[] = [
                    "medico" => $nombreMedico,
                    "fecha" => $fecha,
                    "cod_cups" => $l->producto->cod_cups ?? "",
                    "nom_produc" => $l->producto->nom_produc ?? "",
                    "clase" => $l->clase === "urgencias" ? "Urgencias" : "Consulta",
                    "valor" => (float) $l->valor,
                    "observacion" => "Liquidado",
                ];
                $numeroFila++;
            }

            // Líneas excluidas
            foreach ($m["excluidas_detalle"] as $x) {
                $fecha = $x->fecha_lectura ? substr((string) $x->fecha_lectura, 0, 10) : "";
                $filas[] = [
                    "medico" => $nombreMedico,
                    "fecha" => $fecha,
                    "cod_cups" => $x->producto->cod_cups ?? "",
                    "nom_produc" => $x->producto->nom_produc ?? "",
                    "clase" => "Excluida",
                    "valor" => 0.0,
                    "observacion" => "Excluido: ".($x->exclusion ?? "No parametrizado"),
                ];
                $numeroFila++;
            }

            // Fila de subtotal por médico
            $filas[] = [
                "medico" => "SUBTOTAL: {$nombreMedico}",
                "fecha" => "",
                "cod_cups" => "",
                "nom_produc" => count($m["lineas"])." liquidables | ".count($m["excluidas_detalle"])." excluidas",
                "clase" => "Subtotal",
                "valor" => (float) $m["total"],
                "observacion" => "Consulta: $".number_format($m["total_consulta"], 0, ",", ".")." | Urg: $".number_format($m["total_urgencias"], 0, ",", "."),
            ];
            $this->filasResaltadas[] = $numeroFila;
            $numeroFila++;
        }

        // Fila de gran total
        if (! empty($filas)) {
            $filas[] = [
                "medico" => "GRAN TOTAL PERIODO {$this->periodo}",
                "fecha" => "",
                "cod_cups" => "",
                "nom_produc" => "Total general acumulado",
                "clase" => "Total",
                "valor" => $this->granTotal,
                "observacion" => "Sede: {$this->nomSede}",
            ];
            $this->filasResaltadas[] = $numeroFila;
        }

        return collect($filas);
    }

    /**
     * Celda inicial donde se ubicarán los encabezados de los datos.
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
     * Encabezados de la tabla.
     *
     * @returns array<int, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
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
     * @date 2026-10-21
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
     * @returns array<string, float>
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
            "C" => 14,
            "D" => 45,
            "E" => 18,
            "F" => 18,
            "G" => 35,
        ];
    }

    /**
     * Formatos de columna numéricos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function columnFormats(): array
    {
        return [
            "F" => '"$"#,##0',
        ];
    }

    /**
     * Estilos visuales institucionales.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function styles(Worksheet $sheet): array
    {
        // Título institucional superior
        $sheet->setCellValue("A1", "Salud Tolima - Reporte de Productividad Médica y Honorarios");
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

        // Subtítulo con sede y periodo
        $sheet->setCellValue("A2", "Sede: {$this->nomSede}  |  Periodo de Corte: {$this->periodo}");
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

        // Encabezados (fila 4)
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
            $sheet->getStyle("A4:G{$highestRow}")->applyFromArray([
                "borders" => [
                    "allBorders" => [
                        "borderStyle" => Border::BORDER_THIN,
                        "color" => ["rgb" => "CCCCCC"],
                    ],
                ],
            ]);

            // Resaltar filas de subtotales y gran total
            foreach ($this->filasResaltadas as $filaNum) {
                if ($filaNum <= $highestRow) {
                    $sheet->getStyle("A{$filaNum}:G{$filaNum}")->applyFromArray([
                        "font" => [
                            "bold" => true,
                        ],
                        "fill" => [
                            "fillType" => Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "E8F4FD"],
                        ],
                    ]);
                }
            }
        }

        return [
            4 => ["font" => ["bold" => true]],
        ];
    }
}
