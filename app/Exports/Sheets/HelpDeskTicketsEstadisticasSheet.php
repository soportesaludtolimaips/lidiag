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
 * Hoja de Estadísticas Ejecutivas, Indicadores de Mesa de Ayuda y Gráficos Nativos para HelpDesk.
 *
 * Flujo del proceso:
 * 1. Calcula indicadores clave de rendimiento (KPIs): Total tickets, resueltos, pendientes y horas promedio.
 * 2. Agrupa tickets por estado (Abierto, En progreso, Resuelto, Cerrado).
 * 3. Agrupa tickets por categoría técnica y por prioridad de atención.
 * 4. Genera gráficos nativos de Torta y Barras para la gestión de soporte.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class HelpDeskTicketsEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalTickets = 0;

    protected int $totalResueltos = 0;

    protected int $totalPendientes = 0;

    protected float $promedioHoras = 0.0;

    /**
     * @var array<int, array{estado: string, total: int, porcentaje: float}>
     */
    protected array $resumenEstados = [];

    /**
     * @var array<int, array{ranking: int, categoria: string, total: int, porcentaje: float}>
     */
    protected array $topCategorias = [];

    /**
     * Constructor con inyección de tickets.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected Collection $tickets
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Calcula métricas y agrupaciones de tickets de soporte.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function calcularEstadisticas(): void
    {
        $this->totalTickets = $this->tickets->count();
        if ($this->totalTickets === 0) {
            return;
        }

        $conteoEstados = [];
        $conteoCategorias = [];
        $sumaHoras = 0.0;
        $conteoConHoras = 0;

        foreach ($this->tickets as $t) {
            $estado = trim((string) ($t->estado ?? "Abierto"));
            $conteoEstados[$estado] = ($conteoEstados[$estado] ?? 0) + 1;

            if (in_array(strtolower($estado), ["resuelto", "cerrado", "closed", "resolved"])) {
                $this->totalResueltos++;
            } else {
                $this->totalPendientes++;
            }

            $cat = trim((string) ($t->categoria ?? "General"));
            $conteoCategorias[$cat] = ($conteoCategorias[$cat] ?? 0) + 1;

            if (! empty($t->segundos_resolucion)) {
                $sumaHoras += ((float) $t->segundos_resolucion) / 3600;
                $conteoConHoras++;
            }
        }

        $this->promedioHoras = $conteoConHoras > 0 ? round($sumaHoras / $conteoConHoras, 1) : 0.0;

        // Resumen Estados
        arsort($conteoEstados);
        foreach ($conteoEstados as $est => $cant) {
            $this->resumenEstados[] = [
                "estado" => ucfirst(strtolower($est)),
                "total" => $cant,
                "porcentaje" => round($cant / $this->totalTickets, 4),
            ];
        }

        // Top Categorías
        arsort($conteoCategorias);
        $rank = 1;
        foreach (array_slice($conteoCategorias, 0, 10, true) as $c => $cant) {
            $this->topCategorias[] = [
                "ranking" => $rank++,
                "categoria" => $c,
                "total" => $cant,
                "porcentaje" => round($cant / $this->totalTickets, 4),
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
        return "Estadísticas HelpDesk";
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
            "E" => 16,
            "F" => 4,
            "G" => 28,
            "H" => 16,
            "I" => 16,
            "J" => 4,
            "K" => 18,
            "L" => 18,
        ];
    }

    /**
     * Construcción y formateo con estilos corporativos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->setShowGridLines(true);

        // Membrete principal
        $sheet->mergeCells("B2:I2");
        $sheet->setCellValue("B2", "SALUD TOLIMA / LIDIAG — DASHBOARD DE SOPORTE TÉCNICO Y HELPDESK");
        $sheet->getStyle("B2")->getFont()->setBold(true)->setSize(15)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B2:I2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1E293B");
        $sheet->getStyle("B2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(32);

        $sheet->mergeCells("B3:I3");
        $sheet->setCellValue("B3", "Corte de auditoría de incidentes y requerimientos: ".Carbon::now()->format("d/m/Y H:i:s")." | SLA y Resolución");
        $sheet->getStyle("B3")->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B3:I3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF334155");
        $sheet->getStyle("B3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Tarjetas KPI (Fila 5 y 6)
        $pctResueltos = $this->totalTickets > 0 ? round(($this->totalResueltos / $this->totalTickets) * 100, 1) : 0;
        $pctPendientes = $this->totalTickets > 0 ? round(($this->totalPendientes / $this->totalTickets) * 100, 1) : 0;

        $kpis = [
            ["cols" => "B5:C5", "val_col" => "B6:C6", "vcell" => "B6", "titulo" => "TOTAL TICKETS", "valor" => number_format($this->totalTickets), "bg" => "FF1E293B", "fcolor" => "FFFFFFFF"],
            ["cols" => "D5:E5", "val_col" => "D6:E6", "vcell" => "D6", "titulo" => "TICKETS RESUELTOS", "valor" => "{$this->totalResueltos} ({$pctResueltos}%)", "bg" => "FF059669", "fcolor" => "FFFFFFFF"],
            ["cols" => "G5:H5", "val_col" => "G6:H6", "vcell" => "G6", "titulo" => "TICKETS PENDIENTES", "valor" => "{$this->totalPendientes} ({$pctPendientes}%)", "bg" => "FFD97706", "fcolor" => "FFFFFFFF"],
            ["cols" => "I5:I5", "val_col" => "I6:I6", "vcell" => "I6", "titulo" => "TIEMPO PROM. RESOLUCIÓN", "valor" => "{$this->promedioHoras} h", "bg" => "FF475569", "fcolor" => "FFFFFFFF"],
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
        // TABLA 1: TOP CATEGORÍAS DE TICKETS (Filas 9..)
        // ==========================================
        $sheet->mergeCells("B9:E9");
        $sheet->setCellValue("B9", "TOP CATEGORÍAS DE INCIDENCIAS HELPDESK");
        $sheet->getStyle("B9")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B9:E9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1E293B");
        $sheet->getStyle("B9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("B10", "#");
        $sheet->setCellValue("C10", "Categoría Soporte");
        $sheet->setCellValue("D10", "Tickets");
        $sheet->setCellValue("E10", "% Total");
        $sheet->getStyle("B10:E10")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B10:E10")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF334155");
        $sheet->getStyle("B10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D10:E10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowCat = 11;
        foreach ($this->topCategorias as $item) {
            $sheet->setCellValue("B{$rowCat}", $item["ranking"]);
            $sheet->setCellValue("C{$rowCat}", $item["categoria"]);
            $sheet->setCellValue("D{$rowCat}", $item["total"]);
            $sheet->setCellValue("E{$rowCat}", $item["porcentaje"]);

            $sheet->getStyle("B{$rowCat}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowCat}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rowCat}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("D{$rowCat}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("E{$rowCat}")->getNumberFormat()->setFormatCode("0.0%");
            $rowCat++;
        }
        $finCat = max(11, $rowCat - 1);
        $sheet->getStyle("B10:E{$finCat}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFCBD5E1");

        // ==========================================
        // TABLA 2: DISTRIBUCIÓN POR ESTADO (Filas 9..)
        // ==========================================
        $sheet->mergeCells("G9:I9");
        $sheet->setCellValue("G9", "DISTRIBUCIÓN DE TICKETS POR ESTADO");
        $sheet->getStyle("G9")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("G9:I9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0F172A");
        $sheet->getStyle("G9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("G10", "Estado");
        $sheet->setCellValue("H10", "Tickets");
        $sheet->setCellValue("I10", "% Total");
        $sheet->getStyle("G10:I10")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("G10:I10")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1E293B");
        $sheet->getStyle("H10:I10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowEst = 11;
        foreach ($this->resumenEstados as $item) {
            $sheet->setCellValue("G{$rowEst}", $item["estado"]);
            $sheet->setCellValue("H{$rowEst}", $item["total"]);
            $sheet->setCellValue("I{$rowEst}", $item["porcentaje"]);

            $sheet->getStyle("H{$rowEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I{$rowEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("H{$rowEst}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("I{$rowEst}")->getNumberFormat()->setFormatCode("0.0%");
            $rowEst++;
        }
        $finEst = max(11, $rowEst - 1);
        $sheet->getStyle("G10:I{$finEst}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFCBD5E1");

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
        if ($this->totalTickets === 0 || empty($this->topCategorias)) {
            return [];
        }

        $charts = [];
        $finCat = 10 + count($this->topCategorias);

        // 1. Gráfico de Barras: Top Categorías
        $categories1 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Estadísticas HelpDesk'!\$C\$11:\$C\${$finCat}", null, count($this->topCategorias))];
        $values1 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Estadísticas HelpDesk'!\$D\$11:\$D\${$finCat}", null, count($this->topCategorias))];

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
        $title1 = new Title("Top Categorías HelpDesk");

        $chart1 = new Chart(
            "chart_categorias_helpdesk",
            $title1,
            null,
            $plotArea1,
            true,
            DataSeries::EMPTY_AS_GAP,
            null,
            null
        );
        $chart1->setTopLeftPosition("B".($finCat + 3));
        $chart1->setBottomRightPosition("E".($finCat + 19));
        $charts[] = $chart1;

        // 2. Gráfico de Torta: Distribución por Estado
        if (! empty($this->resumenEstados)) {
            $finEst = 10 + count($this->resumenEstados);
            $categories2 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Estadísticas HelpDesk'!\$G\$11:\$G\${$finEst}", null, count($this->resumenEstados))];
            $values2 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Estadísticas HelpDesk'!\$H\$11:\$H\${$finEst}", null, count($this->resumenEstados))];

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
            $title2 = new Title("Distribución de Tickets por Estado");

            $chart2 = new Chart(
                "chart_estados_helpdesk",
                $title2,
                $legend2,
                $plotArea2,
                true,
                DataSeries::EMPTY_AS_GAP,
                null,
                null
            );
            $chart2->setTopLeftPosition("G".($finCat + 3));
            $chart2->setBottomRightPosition("I".($finCat + 19));
            $charts[] = $chart2;
        }

        return $charts;
    }
}
