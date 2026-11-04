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
 * Hoja de Estadísticas Ejecutivas, Productividad de Radiólogos y Gráficos Nativos.
 *
 * Flujo del proceso:
 * 1. Mide el volumen general de producción: Gran total liquidado, estudios evaluados, liquidados y excluidos.
 * 2. Consolida la productividad comparativa por médico radiólogo (estudios vs honorarios).
 * 3. Segmenta los honorarios entre Consulta Externa y Urgencias.
 * 4. Genera gráficos nativos de Columnas y Torta para la gerencia médica y financiera.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ProductividadEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected float $granTotal = 0.0;

    protected int $totalEstudios = 0;

    protected int $totalLiquidados = 0;

    protected int $totalExcluidos = 0;

    protected float $totalConsulta = 0.0;

    protected float $totalUrgencias = 0.0;

    /**
     * @var array<int, array{ranking: int, medico: string, liquidados: int, excluidos: int, total: float, porcentaje: float}>
     */
    protected array $resumenMedicos = [];

    /**
     * @var array<int, array{tipo: string, total: float, porcentaje: float}>
     */
    protected array $resumenTipo = [];

    /**
     * @param  array<int, mixed>  $medicosData
     */
    public function __construct(
        protected array $medicosData,
        protected string $nomSede,
        protected string $periodo,
        float $granTotal = 0.0
    ) {
        $this->granTotal = $granTotal;
        $this->calcularEstadisticas();
    }

    /**
     * Calcula métricas y agrupaciones de productividad.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function calcularEstadisticas(): void
    {
        if (empty($this->medicosData)) {
            return;
        }

        $medicosList = [];

        foreach ($this->medicosData as $m) {
            $nombreMedico = (string) ($m["medico_name"] ?: "Sin médico asignado");
            $cantLiq = count($m["lineas"] ?? []);
            $cantExc = count($m["excluidas_detalle"] ?? []);
            $totalMed = (float) ($m["total"] ?? 0.0);
            $consMed = (float) ($m["total_consulta"] ?? 0.0);
            $urgMed = (float) ($m["total_urgencias"] ?? 0.0);

            $this->totalLiquidados += $cantLiq;
            $this->totalExcluidos += $cantExc;
            $this->totalConsulta += $consMed;
            $this->totalUrgencias += $urgMed;

            $medicosList[] = [
                "medico" => $nombreMedico,
                "liquidados" => $cantLiq,
                "excluidos" => $cantExc,
                "total" => $totalMed,
            ];
        }

        $this->totalEstudios = $this->totalLiquidados + $this->totalExcluidos;
        if ($this->granTotal <= 0) {
            $this->granTotal = $this->totalConsulta + $this->totalUrgencias;
        }

        // Ordenar por honorarios
        usort($medicosList, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $rank = 1;
        foreach ($medicosList as $item) {
            $this->resumenMedicos[] = [
                "ranking" => $rank++,
                "medico" => $item["medico"],
                "liquidados" => $item["liquidados"],
                "excluidos" => $item["excluidos"],
                "total" => $item["total"],
                "porcentaje" => $this->granTotal > 0 ? round($item["total"] / $this->granTotal, 4) : 0.0,
            ];
        }

        // Segmentación Consulta vs Urgencias
        if ($this->granTotal > 0) {
            $this->resumenTipo = [
                [
                    "tipo" => "Consulta Externa / Ambulatorio",
                    "total" => $this->totalConsulta,
                    "porcentaje" => round($this->totalConsulta / $this->granTotal, 4),
                ],
                [
                    "tipo" => "Urgencias / Prioritario",
                    "total" => $this->totalUrgencias,
                    "porcentaje" => round($this->totalUrgencias / $this->granTotal, 4),
                ],
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
        return "Estadísticas Productividad";
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
            "D" => 15,
            "E" => 15,
            "F" => 20,
            "G" => 16,
            "H" => 4,
            "I" => 32,
            "J" => 20,
            "K" => 16,
            "L" => 4,
            "M" => 18,
            "N" => 18,
        ];
    }

    /**
     * Construcción de la hoja con estilos.
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
        $sheet->setCellValue("B2", "SALUD TOLIMA — DASHBOARD DE PRODUCTIVIDAD Y HONORARIOS MÉDICOS");
        $sheet->getStyle("B2")->getFont()->setBold(true)->setSize(15)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B2:K2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1E3A8A");
        $sheet->getStyle("B2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(32);

        $sheet->mergeCells("B3:K3");
        $sheet->setCellValue("B3", "Sede: {$this->nomSede} | Periodo: {$this->periodo} | Generado: ".Carbon::now()->format("d/m/Y H:i"));
        $sheet->getStyle("B3")->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B3:K3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF2563EB");
        $sheet->getStyle("B3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Tarjetas KPI (Fila 5 y 6)
        $pctLiq = $this->totalEstudios > 0 ? round(($this->totalLiquidados / $this->totalEstudios) * 100, 1) : 0;
        $pctExc = $this->totalEstudios > 0 ? round(($this->totalExcluidos / $this->totalEstudios) * 100, 1) : 0;

        $kpis = [
            ["cols" => "B5:D5", "val_col" => "B6:D6", "vcell" => "B6", "titulo" => "TOTAL HONORARIOS LIQUIDADOS", "valor" => "$".number_format($this->granTotal, 0, ",", "."), "bg" => "FF1E3A8A", "fcolor" => "FFFFFFFF"],
            ["cols" => "E5:F5", "val_col" => "E6:F6", "vcell" => "E6", "titulo" => "ESTUDIOS EVALUADOS", "valor" => number_format($this->totalEstudios), "bg" => "FF0F766E", "fcolor" => "FFFFFFFF"],
            ["cols" => "H5:I5", "val_col" => "H6:I6", "vcell" => "H6", "titulo" => "ESTUDIOS LIQUIDADOS", "valor" => number_format($this->totalLiquidados)." ({$pctLiq}%)", "bg" => "FF047857", "fcolor" => "FFFFFFFF"],
            ["cols" => "J5:K5", "val_col" => "J6:K6", "vcell" => "J6", "titulo" => "ESTUDIOS EXCLUIDOS", "valor" => number_format($this->totalExcluidos)." ({$pctExc}%)", "bg" => "FFB45309", "fcolor" => "FFFFFFFF"],
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
        // TABLA 1: PRODUCTIVIDAD POR RADIÓLOGO (Filas 9..)
        // ==========================================
        $sheet->mergeCells("B9:G9");
        $sheet->setCellValue("B9", "RANKING DE PRODUCTIVIDAD Y HONORARIOS POR MÉDICO RADIÓLOGO");
        $sheet->getStyle("B9")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B9:G9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1E3A8A");
        $sheet->getStyle("B9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("B10", "#");
        $sheet->setCellValue("C10", "Médico Radiólogo");
        $sheet->setCellValue("D10", "Liquidados");
        $sheet->setCellValue("E10", "Excluidos");
        $sheet->setCellValue("F10", "Honorarios ($)");
        $sheet->setCellValue("G10", "% Total");
        $sheet->getStyle("B10:G10")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B10:G10")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF3B82F6");
        $sheet->getStyle("B10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D10:G10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowMed = 11;
        foreach ($this->resumenMedicos as $item) {
            $sheet->setCellValue("B{$rowMed}", $item["ranking"]);
            $sheet->setCellValue("C{$rowMed}", $item["medico"]);
            $sheet->setCellValue("D{$rowMed}", $item["liquidados"]);
            $sheet->setCellValue("E{$rowMed}", $item["excluidos"]);
            $sheet->setCellValue("F{$rowMed}", $item["total"]);
            $sheet->setCellValue("G{$rowMed}", $item["porcentaje"]);

            $sheet->getStyle("B{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("G{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("D{$rowMed}:E{$rowMed}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("F{$rowMed}")->getNumberFormat()->setFormatCode('"$"#,##0');
            $sheet->getStyle("G{$rowMed}")->getNumberFormat()->setFormatCode("0.0%");
            $rowMed++;
        }
        $finMed = max(11, $rowMed - 1);
        $sheet->getStyle("B10:G{$finMed}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFBFDBFE");

        // ==========================================
        // TABLA 2: DISTRIBUCIÓN CONSULTA VS URGENCIAS (Filas 9..)
        // ==========================================
        $sheet->mergeCells("I9:K9");
        $sheet->setCellValue("I9", "DISTRIBUCIÓN DEL VALOR SEGÚN CLASE");
        $sheet->getStyle("I9")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("I9:K9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0F766E");
        $sheet->getStyle("I9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("I10", "Tipo de Servicio");
        $sheet->setCellValue("J10", "Honorarios ($)");
        $sheet->setCellValue("K10", "% Total");
        $sheet->getStyle("I10:K10")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("I10:K10")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF14B8A6");
        $sheet->getStyle("J10:K10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowTipo = 11;
        foreach ($this->resumenTipo as $item) {
            $sheet->setCellValue("I{$rowTipo}", $item["tipo"]);
            $sheet->setCellValue("J{$rowTipo}", $item["total"]);
            $sheet->setCellValue("K{$rowTipo}", $item["porcentaje"]);

            $sheet->getStyle("J{$rowTipo}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rowTipo}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("J{$rowTipo}")->getNumberFormat()->setFormatCode('"$"#,##0');
            $sheet->getStyle("K{$rowTipo}")->getNumberFormat()->setFormatCode("0.0%");
            $rowTipo++;
        }
        $finTipo = max(11, $rowTipo - 1);
        $sheet->getStyle("I10:K{$finTipo}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFCCFBF1");

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
        $categories1 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Estadísticas Productividad'!\$C\$11:\$C\${$finMed}", null, count($this->resumenMedicos))];
        $values1 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Estadísticas Productividad'!\$F\$11:\$F\${$finMed}", null, count($this->resumenMedicos))];

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
        $title1 = new Title("Honorarios por Radiólogo ($)");

        $chart1 = new Chart(
            "chart_productividad_medicos",
            $title1,
            null,
            $plotArea1,
            true,
            DataSeries::EMPTY_AS_GAP,
            null,
            null
        );
        $chart1->setTopLeftPosition("B".($finMed + 3));
        $chart1->setBottomRightPosition("G".($finMed + 19));
        $charts[] = $chart1;

        // 2. Gráfico de Torta: Distribución Consulta vs Urgencias
        if (! empty($this->resumenTipo)) {
            $finTipo = 10 + count($this->resumenTipo);
            $categories2 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Estadísticas Productividad'!\$I\$11:\$I\${$finTipo}", null, count($this->resumenTipo))];
            $values2 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Estadísticas Productividad'!\$J\$11:\$J\${$finTipo}", null, count($this->resumenTipo))];

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
            $title2 = new Title("Distribución Consulta vs Urgencias");

            $chart2 = new Chart(
                "chart_tipo_servicio",
                $title2,
                $legend2,
                $plotArea2,
                true,
                DataSeries::EMPTY_AS_GAP,
                null,
                null
            );
            $chart2->setTopLeftPosition("I".($finMed + 3));
            $chart2->setBottomRightPosition("K".($finMed + 19));
            $charts[] = $chart2;
        }

        return $charts;
    }
}
