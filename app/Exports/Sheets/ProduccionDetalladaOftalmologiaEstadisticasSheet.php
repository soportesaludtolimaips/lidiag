<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Collection;
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
 * Hoja de Estadísticas Ejecutivas, Indicadores de Oftalmología y Gráficos Nativos.
 *
 * Flujo del proceso:
 * 1. Calcula indicadores de volumen de oftalmología: Total exámenes, especialistas, procedimientos únicos y aseguradoras.
 * 2. Agrupa el ranking de exámenes oftalmológicos más frecuentes (campimetrías, biometrías, tomografías oculares, etc.).
 * 3. Agrupa la producción por médico especialista en oftalmología.
 * 4. Genera gráficos nativos de Columnas y Torta.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-06
 */
class ProduccionDetalladaOftalmologiaEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalExamenes = 0;

    protected int $totalMedicos = 0;

    protected int $totalProcedimientos = 0;

    protected int $totalEps = 0;

    /**
     * @var array<int, array{ranking: int, procedimiento: string, total: int, porcentaje: float}>
     */
    protected array $topProcedimientos = [];

    /**
     * @var array<int, array{ranking: int, medico: string, total: int, porcentaje: float}>
     */
    protected array $rankingMedicos = [];

    /**
     * Constructor del reporte estadístico de oftalmología.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function __construct(
        protected Collection $examenes,
        protected ?string $fechaInicio = null,
        protected ?string $fechaFin = null
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Calcula métricas y agrupaciones de oftalmología.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    protected function calcularEstadisticas(): void
    {
        $this->totalExamenes = $this->examenes->count();
        if ($this->totalExamenes === 0) {
            return;
        }

        $conteoProcs = [];
        $conteoMedicos = [];
        $epsList = [];

        foreach ($this->examenes as $ex) {
            $proc = trim((string) ($ex->nom_produc ?? "Procedimiento Oftalmológico"));
            $conteoProcs[$proc] = ($conteoProcs[$proc] ?? 0) + 1;

            $med = trim((string) ($ex->medico ?? "Sin médico"));
            $conteoMedicos[$med] = ($conteoMedicos[$med] ?? 0) + 1;

            $eps = trim((string) ($ex->eps ?? "Particular"));
            $epsList[$eps] = true;
        }

        $this->totalProcedimientos = count($conteoProcs);
        $this->totalMedicos = count($conteoMedicos);
        $this->totalEps = count($epsList);

        // Top 10 Procedimientos
        arsort($conteoProcs);
        $rank = 1;
        foreach (array_slice($conteoProcs, 0, 10, true) as $p => $cant) {
            $this->topProcedimientos[] = [
                "ranking" => $rank++,
                "procedimiento" => $p,
                "total" => $cant,
                "porcentaje" => round($cant / $this->totalExamenes, 4),
            ];
        }

        // Ranking Médicos
        arsort($conteoMedicos);
        $rank = 1;
        foreach (array_slice($conteoMedicos, 0, 10, true) as $m => $cant) {
            $this->rankingMedicos[] = [
                "ranking" => $rank++,
                "medico" => $m,
                "total" => $cant,
                "porcentaje" => round($cant / $this->totalExamenes, 4),
            ];
        }
    }

    /**
     * Título de la pestaña.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function title(): string
    {
        return "Estadísticas Oftalmología";
    }

    /**
     * Array vacío.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
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
     * @date 2026-11-06
     */
    public function columnWidths(): array
    {
        return [
            "A" => 4,
            "B" => 6,
            "C" => 38,
            "D" => 16,
            "E" => 16,
            "F" => 4,
            "G" => 6,
            "H" => 36,
            "I" => 16,
            "J" => 16,
            "K" => 4,
            "L" => 18,
            "M" => 18,
        ];
    }

    /**
     * Construcción de la hoja de estadísticas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->setShowGridLines(true);

        // Membrete principal
        $sheet->mergeCells("B2:J2");
        $sheet->setCellValue("B2", "SALUD TOLIMA — RESUMEN EJECUTIVO Y ESTADÍSTICAS DE OFTALMOLOGÍA");
        $sheet->getStyle("B2")->getFont()->setBold(true)->setSize(15)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B2:J2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0D5C3A");
        $sheet->getStyle("B2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(32);

        $periodoTxt = ($this->fechaInicio && $this->fechaFin)
            ? "Periodo: {$this->fechaInicio} al {$this->fechaFin}"
            : "Periodo histórico completo";
        $sheet->mergeCells("B3:J3");
        $sheet->setCellValue("B3", "{$periodoTxt} | Generado: ".Carbon::now()->format("d/m/Y H:i")." | Módulo Especializado de Oftalmología");
        $sheet->getStyle("B3")->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B3:J3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF15803D");
        $sheet->getStyle("B3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Tarjetas KPI (Fila 5 y 6)
        $kpis = [
            ["cols" => "B5:C5", "val_col" => "B6:C6", "vcell" => "B6", "titulo" => "TOTAL EXÁMENES", "valor" => number_format($this->totalExamenes), "bg" => "FF0D5C3A", "fcolor" => "FFFFFFFF"],
            ["cols" => "D5:E5", "val_col" => "D6:E6", "vcell" => "D6", "titulo" => "MÉDICOS OFTALMÓLOGOS", "valor" => $this->totalMedicos, "bg" => "FF0284C7", "fcolor" => "FFFFFFFF"],
            ["cols" => "G5:H5", "val_col" => "G6:H6", "vcell" => "G6", "titulo" => "PROCEDIMIENTOS DISTINTOS", "valor" => $this->totalProcedimientos, "bg" => "FF0D9488", "fcolor" => "FFFFFFFF"],
            ["cols" => "I5:J5", "val_col" => "I6:J6", "vcell" => "I6", "titulo" => "ASEGURADORAS (EPS)", "valor" => $this->totalEps, "bg" => "FF475569", "fcolor" => "FFFFFFFF"],
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
        // TABLA 1: TOP PROCEDIMIENTOS DE OFTALMOLOGÍA (Filas 9..)
        // ==========================================
        $sheet->mergeCells("B9:E9");
        $sheet->setCellValue("B9", "TOP 10 EXÁMENES Y PROCEDIMIENTOS DE OFTALMOLOGÍA");
        $sheet->getStyle("B9")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B9:E9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0D5C3A");
        $sheet->getStyle("B9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("B10", "#");
        $sheet->setCellValue("C10", "Examen Oftalmológico");
        $sheet->setCellValue("D10", "Cantidad");
        $sheet->setCellValue("E10", "% Total");
        $sheet->getStyle("B10:E10")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B10:E10")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF15803D");
        $sheet->getStyle("B10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D10:E10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowProc = 11;
        foreach ($this->topProcedimientos as $item) {
            $sheet->setCellValue("B{$rowProc}", $item["ranking"]);
            $sheet->setCellValue("C{$rowProc}", $item["procedimiento"]);
            $sheet->setCellValue("D{$rowProc}", $item["total"]);
            $sheet->setCellValue("E{$rowProc}", $item["porcentaje"]);

            $sheet->getStyle("B{$rowProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rowProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("D{$rowProc}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("E{$rowProc}")->getNumberFormat()->setFormatCode("0.0%");
            $rowProc++;
        }
        $finProc = max(11, $rowProc - 1);
        $sheet->getStyle("B10:E{$finProc}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFBBF7D0");

        // ==========================================
        // TABLA 2: RANKING DE MÉDICOS OFTALMÓLOGOS (Filas 9..)
        // ==========================================
        $sheet->mergeCells("G9:J9");
        $sheet->setCellValue("G9", "PRODUCCIÓN POR MÉDICO OFTALMÓLOGO");
        $sheet->getStyle("G9")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("G9:J9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0369A1");
        $sheet->getStyle("G9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("G10", "#");
        $sheet->setCellValue("H10", "Médico Especialista");
        $sheet->setCellValue("I10", "Estudios");
        $sheet->setCellValue("J10", "% Total");
        $sheet->getStyle("G10:J10")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("G10:J10")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0284C7");
        $sheet->getStyle("G10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I10:J10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowMed = 11;
        foreach ($this->rankingMedicos as $item) {
            $sheet->setCellValue("G{$rowMed}", $item["ranking"]);
            $sheet->setCellValue("H{$rowMed}", $item["medico"]);
            $sheet->setCellValue("I{$rowMed}", $item["total"]);
            $sheet->setCellValue("J{$rowMed}", $item["porcentaje"]);

            $sheet->getStyle("G{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rowMed}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("I{$rowMed}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("J{$rowMed}")->getNumberFormat()->setFormatCode("0.0%");
            $rowMed++;
        }
        $finMed = max(11, $rowMed - 1);
        $sheet->getStyle("G10:J{$finMed}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFBAE6FD");

        return [];
    }

    /**
     * Gráficos nativos de PhpSpreadsheet.
     *
     * @return array<int, Chart>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function charts(): array
    {
        if ($this->totalExamenes === 0 || empty($this->topProcedimientos)) {
            return [];
        }

        $charts = [];
        $finProc = 10 + count($this->topProcedimientos);

        // 1. Gráfico de Barras: Top Procedimientos Oftalmológicos
        $categories1 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Estadísticas Oftalmología'!\$C\$11:\$C\${$finProc}", null, count($this->topProcedimientos))];
        $values1 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Estadísticas Oftalmología'!\$D\$11:\$D\${$finProc}", null, count($this->topProcedimientos))];

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
        $title1 = new Title("Top Exámenes de Oftalmología");

        $chart1 = new Chart(
            "chart_procedimientos_oftalmologia",
            $title1,
            null,
            $plotArea1,
            true,
            DataSeries::EMPTY_AS_GAP,
            null,
            null
        );
        $chart1->setTopLeftPosition("B".($finProc + 3));
        $chart1->setBottomRightPosition("E".($finProc + 19));
        $charts[] = $chart1;

        // 2. Gráfico de Torta: Médicos Oftalmólogos
        if (! empty($this->rankingMedicos)) {
            $finMed = 10 + count($this->rankingMedicos);
            $categories2 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Estadísticas Oftalmología'!\$H\$11:\$H\${$finMed}", null, count($this->rankingMedicos))];
            $values2 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Estadísticas Oftalmología'!\$I\$11:\$I\${$finMed}", null, count($this->rankingMedicos))];

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
            $title2 = new Title("Distribución de Exámenes por Oftalmólogo");

            $chart2 = new Chart(
                "chart_medicos_oftalmologia",
                $title2,
                $legend2,
                $plotArea2,
                true,
                DataSeries::EMPTY_AS_GAP,
                null,
                null
            );
            $chart2->setTopLeftPosition("G".($finProc + 3));
            $chart2->setBottomRightPosition("J".($finProc + 19));
            $charts[] = $chart2;
        }

        return $charts;
    }
}
