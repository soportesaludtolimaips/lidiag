<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de Estadísticas Ejecutivas, Totales de Honorarios y Gráficos Nativos de Liquidación Médica.
 *
 * Flujo del proceso:
 * 1. Calcula indicadores de honorarios: Gran total liquidado, procedimientos, promedio por estudio y médicos.
 * 2. Agrupa el total facturado y volumen por médico radiólogo para ranking de honorarios.
 * 3. Agrupa por clase / modalidad asistencial (Consulta vs Urgencias).
 * 4. Genera gráficos nativos de Columnas y Torta para la gerencia financiera y médica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class LiquidacionEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected float $granTotal = 0.0;

    protected int $totalEstudios = 0;

    protected float $promedioEstudio = 0.0;

    protected int $totalMedicos = 0;

    /**
     * @var array<int, array{ranking: int, medico: string, estudios: int, total: float, porcentaje: float}>
     */
    protected array $resumenMedicos = [];

    /**
     * @var array<int, array{clase: string, estudios: int, total: float, porcentaje: float}>
     */
    protected array $resumenClases = [];

    /**
     * Constructor con inyección de datos calculados.
     *
     * @param  array<int, array<string, mixed>>  $lineas
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected array $lineas,
        protected string $nomSede,
        protected string $fechaInicio,
        protected string $fechaFin
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Procesa y agrupa las líneas de honorarios.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function calcularEstadisticas(): void
    {
        $this->totalEstudios = count($this->lineas);
        if ($this->totalEstudios === 0) {
            return;
        }

        $medicosData = [];
        $clasesData = [];

        foreach ($this->lineas as $l) {
            $val = (float) ($l["valor"] ?? 0.0);
            $this->granTotal += $val;

            $med = (string) ($l["medico_name"] ?? "Sin médico");
            if (! isset($medicosData[$med])) {
                $medicosData[$med] = ["estudios" => 0, "total" => 0.0];
            }
            $medicosData[$med]["estudios"]++;
            $medicosData[$med]["total"] += $val;

            $clase = (string) ($l["clase"] ?? "Normal");
            $claseNorm = ucfirst(strtolower($clase));
            if (! isset($clasesData[$claseNorm])) {
                $clasesData[$claseNorm] = ["estudios" => 0, "total" => 0.0];
            }
            $clasesData[$claseNorm]["estudios"]++;
            $clasesData[$claseNorm]["total"] += $val;
        }

        $this->totalMedicos = count($medicosData);
        $this->promedioEstudio = round($this->granTotal / $this->totalEstudios, 2);

        // Ranking Médicos
        uasort($medicosData, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $rank = 1;
        foreach (array_slice($medicosData, 0, 10, true) as $med => $info) {
            $this->resumenMedicos[] = [
                "ranking" => $rank++,
                "medico" => $med,
                "estudios" => $info["estudios"],
                "total" => $info["total"],
                "porcentaje" => $this->granTotal > 0 ? round($info["total"] / $this->granTotal, 4) : 0.0,
            ];
        }

        // Resumen Clases
        uasort($clasesData, fn ($a, $b) => $b["total"] <=> $a["total"]);
        foreach ($clasesData as $clase => $info) {
            $this->resumenClases[] = [
                "clase" => $clase,
                "estudios" => $info["estudios"],
                "total" => $info["total"],
                "porcentaje" => $this->granTotal > 0 ? round($info["total"] / $this->granTotal, 4) : 0.0,
            ];
        }
    }

    /**
     * Título de la pestaña.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function title(): string
    {
        return "Estadísticas Liquidación";
    }

    /**
     * Array vacío.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function array(): array
    {
        return [];
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
            "A" => 4,
            "B" => 6,
            "C" => 36,
            "D" => 16,
            "E" => 20,
            "F" => 16,
            "G" => 4,
            "H" => 22,
            "I" => 14,
            "J" => 18,
            "K" => 16,
            "L" => 4,
            "M" => 18,
            "N" => 18,
            "O" => 18,
        ];
    }

    /**
     * Construcción de la hoja con estilos corporativos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->setShowGridLines(true);

        // Membrete principal
        $sheet->mergeCells("B2:K2");
        $sheet->setCellValue("B2", "SALUD TOLIMA — RESUMEN EJECUTIVO Y ESTADÍSTICAS DE LIQUIDACIÓN");
        $sheet->getStyle("B2")->getFont()->setBold(true)->setSize(15)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B2:K2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1A2274");
        $sheet->getStyle("B2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(32);

        $sheet->mergeCells("B3:K3");
        $sheet->setCellValue("B3", "Sede: {$this->nomSede} | Periodo: {$this->fechaInicio} al {$this->fechaFin} | Generado: ".Carbon::now()->format("d/m/Y H:i"));
        $sheet->getStyle("B3")->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B3:K3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF283593");
        $sheet->getStyle("B3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Tarjetas KPI (Fila 5 y 6)
        $kpis = [
            ["cols" => "B5:D5", "val_col" => "B6:D6", "vcell" => "B6", "titulo" => "TOTAL HONORARIOS LIQUIDADOS", "valor" => "$".number_format($this->granTotal, 0, ",", "."), "bg" => "FF1A2274", "fcolor" => "FFFFFFFF"],
            ["cols" => "E5:F5", "val_col" => "E6:F6", "vcell" => "E6", "titulo" => "ESTUDIOS LIQUIDADOS", "valor" => number_format($this->totalEstudios), "bg" => "FF0D47A1", "fcolor" => "FFFFFFFF"],
            ["cols" => "H5:I5", "val_col" => "H6:I6", "vcell" => "H6", "titulo" => "VALOR PROMEDIO POR ESTUDIO", "valor" => "$".number_format($this->promedioEstudio, 0, ",", "."), "bg" => "FF00695C", "fcolor" => "FFFFFFFF"],
            ["cols" => "J5:K5", "val_col" => "J6:K6", "vcell" => "J6", "titulo" => "MÉDICOS RADIÓLOGOS", "valor" => $this->totalMedicos, "bg" => "FF37474F", "fcolor" => "FFFFFFFF"],
        ];

        foreach ($kpis as $kpi) {
            $sheet->mergeCells($kpi["cols"]);
            $sheet->mergeCells($kpi["val_col"]);
            $cTop = explode(":", $kpi["cols"])[0];
            $sheet->setCellValue($cTop, $kpi["titulo"]);
            $sheet->setCellValue($kpi["vcell"], $kpi["valor"]);

            $sheet->getStyle($kpi["cols"])->getFont()->setBold(true)->setSize(9)->getColor()->setARGB($kpi["fcolor"]);
            $sheet->getStyle($kpi["cols"])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($kpi["bg"]);
            $sheet->getStyle($kpi["cols"])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle($kpi["val_col"])->getFont()->setBold(true)->setSize(14)->getColor()->setARGB($kpi["fcolor"]);
            $sheet->getStyle($kpi["val_col"])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($kpi["bg"]);
            $sheet->getStyle($kpi["val_col"])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getRowDimension(5)->setRowHeight(20);
        $sheet->getRowDimension(6)->setRowHeight(28);

        // ==========================================
        // TABLA 1: TOP MÉDICOS RADIÓLOGOS (Filas 9..)
        // ==========================================
        $sheet->mergeCells("B9:F9");
        $sheet->setCellValue("B9", "TOP MÉDICOS RADIÓLOGOS POR HONORARIOS LIQUIDADOS");
        $sheet->getStyle("B9")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B9:F9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1A2274");
        $sheet->getStyle("B9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("B10", "#");
        $sheet->setCellValue("C10", "Médico Radiólogo");
        $sheet->setCellValue("D10", "Estudios");
        $sheet->setCellValue("E10", "Honorarios ($)");
        $sheet->setCellValue("F10", "% Total");
        $sheet->getStyle("B10:F10")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B10:F10")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF283593");
        $sheet->getStyle("B10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D10:F10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowMed = 11;
        foreach ($this->resumenMedicos as $item) {
            $sheet->setCellValue("B{$rowMed}", $item["ranking"]);
            $sheet->setCellValue("C{$rowMed}", $item["medico"]);
            $sheet->setCellValue("D{$rowMed}", $item["estudios"]);
            $sheet->setCellValue("E{$rowMed}", $item["total"]);
            $sheet->setCellValue("F{$rowMed}", $item["porcentaje"]);

            $sheet->getStyle("B{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("D{$rowMed}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("E{$rowMed}")->getNumberFormat()->setFormatCode('"$"#,##0');
            $sheet->getStyle("F{$rowMed}")->getNumberFormat()->setFormatCode("0.0%");
            $rowMed++;
        }
        $finMed = max(11, $rowMed - 1);
        $sheet->getStyle("B10:F{$finMed}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFC5CAE9");

        // ==========================================
        // TABLA 2: DISTRIBUCIÓN POR MODALIDAD / CLASE (Filas 9..)
        // ==========================================
        $sheet->mergeCells("H9:K9");
        $sheet->setCellValue("H9", "DISTRIBUCIÓN POR CLASE / MODALIDAD ASISTENCIAL");
        $sheet->getStyle("H9")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("H9:K9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0D47A1");
        $sheet->getStyle("H9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("H10", "Clase / Servicio");
        $sheet->setCellValue("I10", "Estudios");
        $sheet->setCellValue("J10", "Honorarios ($)");
        $sheet->setCellValue("K10", "% Total");
        $sheet->getStyle("H10:K10")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("H10:K10")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1976D2");
        $sheet->getStyle("I10:K10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowClase = 11;
        foreach ($this->resumenClases as $item) {
            $sheet->setCellValue("H{$rowClase}", $item["clase"]);
            $sheet->setCellValue("I{$rowClase}", $item["estudios"]);
            $sheet->setCellValue("J{$rowClase}", $item["total"]);
            $sheet->setCellValue("K{$rowClase}", $item["porcentaje"]);

            $sheet->getStyle("I{$rowClase}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rowClase}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rowClase}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("I{$rowClase}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("J{$rowClase}")->getNumberFormat()->setFormatCode('"$"#,##0');
            $sheet->getStyle("K{$rowClase}")->getNumberFormat()->setFormatCode("0.0%");
            $rowClase++;
        }
        $finClase = max(11, $rowClase - 1);
        $sheet->getStyle("H10:K{$finClase}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFBBDEFB");

        return [];
    }

    /**
     * Gráficos nativos de PhpSpreadsheet.
     *
     * @return array<int, Chart>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function charts(): array
    {
        if ($this->totalEstudios === 0 || empty($this->resumenMedicos)) {
            return [];
        }

        $charts = [];
        $finMed = 10 + count($this->resumenMedicos);

        // 1. Gráfico de Barras: Honorarios por Médico
        $categories1 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Estadísticas Liquidación'!\$C\$11:\$C\${$finMed}", null, count($this->resumenMedicos))];
        $values1 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Estadísticas Liquidación'!\$E\$11:\$E\${$finMed}", null, count($this->resumenMedicos))];

        $series1 = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values1) - 1),
            [],
            $categories1,
            $values1
        );
        $series1->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea1 = new PlotArea(null, [$series1]);
        $title1 = new Title("Honorarios Liquidados por Radiólogo ($)");

        $chart1 = new Chart(
            "chart_honorarios_medicos",
            $title1,
            null,
            $plotArea1,
            true,
            DataSeries::EMPTY_AS_GAP,
            null,
            null
        );
        $chart1->setTopLeftPosition("B".($finMed + 3));
        $chart1->setBottomRightPosition("F".($finMed + 19));
        $charts[] = $chart1;

        // 2. Gráfico de Torta: Distribución por Clase
        if (! empty($this->resumenClases)) {
            $finClase = 10 + count($this->resumenClases);
            $categories2 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Estadísticas Liquidación'!\$H\$11:\$H\${$finClase}", null, count($this->resumenClases))];
            $values2 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Estadísticas Liquidación'!\$J\$11:\$J\${$finClase}", null, count($this->resumenClases))];

            $series2 = new DataSeries(
                DataSeries::TYPE_PIECHART,
                null,
                range(0, count($values2) - 1),
                [],
                $categories2,
                $values2
            );

            $plotArea2 = new PlotArea(null, [$series2]);
            $legend2 = new Legend(Legend::POSITION_RIGHT, null, false);
            $title2 = new Title("Distribución de Honorarios por Clase");

            $chart2 = new Chart(
                "chart_clases_honorarios",
                $title2,
                $legend2,
                $plotArea2,
                true,
                DataSeries::EMPTY_AS_GAP,
                null,
                null
            );
            $chart2->setTopLeftPosition("H".($finMed + 3));
            $chart2->setBottomRightPosition("K".($finMed + 19));
            $charts[] = $chart2;
        }

        return $charts;
    }
}
