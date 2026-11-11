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
 * Hoja de Estadísticas Consolidadas, Motivos de Pérdida y Gráficos Nativos para Ausentismo de Citas.
 *
 * Flujo del proceso:
 * 1. Recibe las citas no atendidas, canceladas o rechazadas en el período.
 * 2. Consolida indicadores de inasistencia (No-Show), cancelaciones voluntarias y motivos clínicos/administrativos.
 * 3. Analiza la distribución por estado de cita y los motivos más recurrentes.
 * 4. Diseña tablas ejecutivas institucionales (#1A2274).
 * 5. Genera e incrusta gráficos nativos: Torta de Estados y Barras de Motivos de Pérdida.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteAusentismoCitasEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalCitas = 0;

    protected int $totalNoAsistio = 0;

    protected int $totalCanceladas = 0;

    protected int $totalRechazadas = 0;

    protected float $pctNoShow = 0.0;

    /**
     * @var array<int, array{estado_legible: string, estado_raw: string, total: int, porcentaje: float}>
     */
    protected array $resumenEstados = [];

    /**
     * @var array<int, array{ranking: int, motivo: string, total: int, porcentaje: float}>
     */
    protected array $topMotivos = [];

    /**
     * Constructor con inyección de datos.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected Collection $citas,
        protected string $sedeNombre,
        protected string $fechaInicio,
        protected string $fechaFin
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Calcula métricas y agrupaciones de ausentismo.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function calcularEstadisticas(): void
    {
        $this->totalCitas = $this->citas->count();

        if ($this->totalCitas === 0) {
            return;
        }

        $noShow = 0;
        $canc = 0;
        $rech = 0;

        foreach ($this->citas as $c) {
            $st = strtolower((string) ($c->estado ?? ""));
            if ($st === "no_asistio") {
                $noShow++;
            } elseif ($st === "cancelada") {
                $canc++;
            } else {
                $rech++;
            }
        }

        $this->totalNoAsistio = $noShow;
        $this->totalCanceladas = $canc;
        $this->totalRechazadas = $rech;
        $this->pctNoShow = round(($noShow / $this->totalCitas) * 100, 1);

        // 1. Resumen por Estado
        $this->resumenEstados = [
            [
                "estado_legible" => "Inasistencia (No Asistió / No-Show)",
                "estado_raw" => "no_asistio",
                "total" => $this->totalNoAsistio,
                "porcentaje" => round($this->totalNoAsistio / $this->totalCitas, 4),
            ],
            [
                "estado_legible" => "Cancelación de Cita",
                "estado_raw" => "cancelada",
                "total" => $this->totalCanceladas,
                "porcentaje" => round($this->totalCanceladas / $this->totalCitas, 4),
            ],
            [
                "estado_legible" => "Rechazo de Solicitud",
                "estado_raw" => "rechazada",
                "total" => $this->totalRechazadas,
                "porcentaje" => round($this->totalRechazadas / $this->totalCitas, 4),
            ],
        ];

        // 2. Top Motivos
        $gruposMotivo = $this->citas->groupBy(function ($item) {
            $m = trim((string) ($item->motivo_rechazo ?? $item->rechazo_observacion ?? ""));

            return $m !== "" ? mb_strtoupper($m, "UTF-8") : "NO ESPECIFICADO / INASISTENCIA DIRECTA";
        });

        $top = [];
        foreach ($gruposMotivo as $motivo => $items) {
            $cant = $items->count();
            $pct = round($cant / $this->totalCitas, 4);

            $top[] = [
                "ranking" => 0,
                "motivo" => (string) $motivo,
                "total" => $cant,
                "porcentaje" => $pct,
            ];
        }
        usort($top, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $top = array_slice($top, 0, 10);
        foreach ($top as $i => &$item) {
            $item["ranking"] = $i + 1;
        }
        unset($item);
        $this->topMotivos = $top;
    }

    /**
     * Título de la pestaña en el libro Excel.
     *
     * @returns string
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function title(): string
    {
        return "Estadísticas y Gráficas";
    }

    /**
     * Array vacío.
     *
     * @returns array<mixed>
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
     * @returns array<string, int>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function columnWidths(): array
    {
        return [
            "A" => 14, "B" => 36, "C" => 14, "D" => 14, "E" => 16,
            "F" => 4,  "G" => 6,  "H" => 45, "I" => 14, "J" => 14,
            "K" => 14, "L" => 16,
        ];
    }

    /**
     * Aplica el diseño corporativo institucional.
     *
     *
     * @returns array<int, array<string, mixed>>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        // 1. Título Corporativo
        $sheet->setCellValue("A1", "SALUD TOLIMA - RESUMEN EJECUTIVO Y ESTADÍSTICAS DE AUSENTISMO Y CANCELACIONES");
        $sheet->mergeCells("A1:L1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 14, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Subtítulo y Metadatos
        $fechaEmision = Carbon::now()->format("Y-m-d H:i");
        $metaTexto = "Sede: {$this->sedeNombre}  |  Período: {$this->fechaInicio} al {$this->fechaFin}  |  Emisión: {$fechaEmision}";
        $sheet->setCellValue("A2", $metaTexto);
        $sheet->mergeCells("A2:L2");
        $sheet->getStyle("A2")->applyFromArray([
            "font" => ["bold" => false, "color" => ["rgb" => "333333"], "size" => 10, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "E9ECEF"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // 3. Tarjetas de KPIs Ejecutivos (Filas 4 y 5)
        $sheet->setCellValue("A4", "TOTAL INASISTENCIAS/CANCELADAS");
        $sheet->setCellValue("A5", number_format($this->totalCitas));
        $sheet->mergeCells("A4:B4");
        $sheet->mergeCells("A5:B5");

        $sheet->setCellValue("C4", "NO ASISTIÓ (NO-SHOW)");
        $sheet->setCellValue("C5", number_format($this->totalNoAsistio));
        $sheet->mergeCells("C4:D4");
        $sheet->mergeCells("C5:D5");

        $sheet->setCellValue("E4", "CANCELADAS VOLUNTARIAS");
        $sheet->setCellValue("E5", number_format($this->totalCanceladas));
        $sheet->mergeCells("E4:F4");
        $sheet->mergeCells("E5:F5");

        $sheet->setCellValue("G4", "RECHAZADAS / NO CONFORMES");
        $sheet->setCellValue("G5", number_format($this->totalRechazadas));
        $sheet->mergeCells("G4:H4");
        $sheet->mergeCells("G5:H5");

        $sheet->setCellValue("I4", "TASA DE NO-SHOW");
        $sheet->setCellValue("I5", number_format($this->pctNoShow, 1)."%");
        $sheet->mergeCells("I4:L4");
        $sheet->mergeCells("I5:L5");

        $sheet->getStyle("A4:L4")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "555555"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F1F3F5"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]]],
        ]);
        $sheet->getStyle("A5:L5")->applyFromArray([
            "font" => ["bold" => true, "size" => 13, "color" => ["rgb" => "1A2274"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFFFFF"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]],
                "bottom" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(24);

        // 4. Encabezados de Sección (Fila 7)
        $sheet->setCellValue("A7", "DISTRIBUCIÓN POR ESTADO DE INASISTENCIA");
        $sheet->mergeCells("A7:E7");
        $sheet->getStyle("A7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue("G7", "TOP 10 MOTIVOS DE CANCELACIÓN O RECHAZO");
        $sheet->mergeCells("G7:L7");
        $sheet->getStyle("G7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(24);

        // 5. Encabezados de Columnas (Fila 8)
        $sheet->setCellValue("A8", "Código");
        $sheet->setCellValue("B8", "Estado de la Cita");
        $sheet->setCellValue("C8", "Citas");
        $sheet->setCellValue("D8", "Impacto");
        $sheet->setCellValue("E8", "% Cuota");
        $sheet->getStyle("A8:E8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $sheet->setCellValue("G8", "#");
        $sheet->setCellValue("H8", "Motivo de Inasistencia / Rechazo");
        $sheet->setCellValue("I8", "Tipo");
        $sheet->setCellValue("J8", "Citas");
        $sheet->setCellValue("K8", "Riesgo");
        $sheet->setCellValue("L8", "% del Total");
        $sheet->getStyle("G8:L8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(20);

        if ($this->totalCitas === 0) {
            $sheet->setCellValue("A9", "No se registraron cancelaciones ni ausentismo en el período.");
            $sheet->mergeCells("A9:E9");
            $sheet->setCellValue("G9", "Sin motivos.");
            $sheet->mergeCells("G9:L9");

            return [];
        }

        // 6.1 Tabla 1: Estados
        $rEst = 9;
        foreach ($this->resumenEstados as $est) {
            $sheet->setCellValue("A{$rEst}", strtoupper($est["estado_raw"]));
            $sheet->setCellValue("B{$rEst}", $est["estado_legible"]);
            $sheet->setCellValue("C{$rEst}", (int) $est["total"]);
            $sheet->setCellValue("D{$rEst}", "Pérdida Agenda");
            $sheet->setCellValue("E{$rEst}", (float) $est["porcentaje"]);

            $sheet->getStyle("A{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rEst}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("D{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rEst}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("A{$rEst}:E{$rEst}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rEst)->setRowHeight(20);
            $rEst++;
        }

        // Fila Total Estados
        $sheet->setCellValue("A{$rEst}", "TOTAL");
        $sheet->setCellValue("B{$rEst}", "Total Citas No Atendidas");
        $sheet->setCellValue("C{$rEst}", $this->totalCitas);
        $sheet->setCellValue("D{$rEst}", "-");
        $sheet->setCellValue("E{$rEst}", 1.0);
        $sheet->getStyle("A{$rEst}:E{$rEst}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("A{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$rEst}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("E{$rEst}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rEst)->setRowHeight(22);

        // 6.2 Tabla 2: Motivos
        $rMot = 9;
        $totalMot = 0;
        $totalMotPct = 0.0;
        foreach ($this->topMotivos as $m) {
            $sheet->setCellValue("G{$rMot}", $m["ranking"]);
            $sheet->setCellValue("H{$rMot}", $m["motivo"]);
            $sheet->setCellValue("I{$rMot}", "Causal");
            $sheet->setCellValue("J{$rMot}", (int) $m["total"]);
            $sheet->setCellValue("K{$rMot}", "-");
            $sheet->setCellValue("L{$rMot}", (float) $m["porcentaje"]);

            $totalMot += (int) $m["total"];
            $totalMotPct += (float) $m["porcentaje"];

            $sheet->getStyle("G{$rMot}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rMot}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I{$rMot}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$rMot}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rMot}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("K{$rMot}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$rMot}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L{$rMot}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("G{$rMot}:L{$rMot}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rMot)->setRowHeight(20);
            $rMot++;
        }

        // Fila Subtotal Motivos
        $sheet->setCellValue("G{$rMot}", "SUBTOTAL");
        $sheet->setCellValue("H{$rMot}", "Subtotal Top Motivos");
        $sheet->setCellValue("I{$rMot}", "-");
        $sheet->setCellValue("J{$rMot}", $totalMot);
        $sheet->setCellValue("K{$rMot}", "-");
        $sheet->setCellValue("L{$rMot}", $totalMotPct);
        $sheet->getStyle("G{$rMot}:L{$rMot}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("G{$rMot}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$rMot}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K{$rMot}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J{$rMot}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("L{$rMot}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rMot)->setRowHeight(22);

        return [];
    }

    /**
     * Gráficos nativos de Excel.
     *
     * @returns Chart[]
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function charts(): array
    {
        if ($this->totalCitas === 0 || empty($this->resumenEstados)) {
            return [];
        }

        $sheetTitle = $this->title();
        $rEstDataStart = 9;
        $rEstDataEnd = 8 + count($this->resumenEstados);
        $rMotDataStart = 9;
        $rMotDataEnd = 8 + count($this->topMotivos);

        $startChart = max($rEstDataEnd + 2, $rMotDataEnd + 2) + 3;
        $endChart = $startChart + 18;

        // 1. Gráfico de Torta: Estado de Citas
        $pieLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$8", null, 1),
        ];
        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\${$rEstDataStart}:\$B\${$rEstDataEnd}", null, count($this->resumenEstados)),
        ];
        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$C\${$rEstDataStart}:\$C\${$rEstDataEnd}", null, count($this->resumenEstados)),
        ];

        $pieSeries = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            range(0, count($pieValues) - 1),
            $pieLabels,
            $pieCategories,
            $pieValues
        );

        $piePlotArea = new PlotArea(null, [$pieSeries]);
        $pieLegend = new Legend(Legend::POSITION_RIGHT, null, false);
        $pieTitle = new Title("Distribución de Inasistencia y Cancelaciones");

        $pieChart = new Chart(
            "chart_ausentismo_pie",
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $pieChart->setTopLeftPosition("A".$startChart);
        $pieChart->setBottomRightPosition("E".$endChart);

        // 2. Gráfico de Barras: Top Motivos
        $chartsList = [$pieChart];

        if (! empty($this->topMotivos)) {
            $barLabels = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$J\$8", null, 1),
            ];
            $barCategories = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$H\${$rMotDataStart}:\$H\${$rMotDataEnd}", null, count($this->topMotivos)),
            ];
            $barValues = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$J\${$rMotDataStart}:\$J\${$rMotDataEnd}", null, count($this->topMotivos)),
            ];

            $barSeries = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                range(0, count($barValues) - 1),
                $barLabels,
                $barCategories,
                $barValues
            );
            $barSeries->setPlotDirection(DataSeries::DIRECTION_COL);

            $barPlotArea = new PlotArea(null, [$barSeries]);
            $barLegend = new Legend(Legend::POSITION_TOP, null, false);
            $barTitle = new Title("Top Motivos de Cancelación y Rechazo");

            $barChart = new Chart(
                "chart_motivos_bar",
                $barTitle,
                $barLegend,
                $barPlotArea,
                true,
                DataSeries::EMPTY_AS_GAP
            );
            $barChart->setTopLeftPosition("G".$startChart);
            $barChart->setBottomRightPosition("L".$endChart);

            $chartsList[] = $barChart;
        }

        return $chartsList;
    }
}
