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
 * Hoja de Estadísticas Consolidadas, Conversión y Gráficos Nativos para el Canal de Citas WhatsApp.
 *
 * Flujo del proceso:
 * 1. Recibe las citas y solicitudes ingresadas a través del asistente virtual y webhook de WhatsApp.
 * 2. Consolida indicadores: Total Solicitudes, Citas Confirmadas, Tasa de Conversión, Pendientes y Canceladas.
 * 3. Analiza la demanda por servicio clínico solicitado (Imágenes, Ecografías, etc.).
 * 4. Analiza la distribución por estado de gestión del canal conversacional.
 * 5. Genera tablas ejecutivas institucionales (#1A2274).
 * 6. Genera e incrusta gráficos ejecutivos: Torta de Conversión y Barras de Servicios.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteCitasWhatsAppEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalSolicitudes = 0;

    protected int $totalConfirmadas = 0;

    protected int $totalPendientes = 0;

    protected int $totalCanceladas = 0;

    protected float $pctConversion = 0.0;

    /**
     * @var array<int, array{servicio: string, total: int, confirmadas: int, porcentaje: float}>
     */
    protected array $resumenServicios = [];

    /**
     * @var array<int, array{estado_legible: string, estado_raw: string, total: int, porcentaje: float}>
     */
    protected array $resumenEstados = [];

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
        protected string $fechaInicio,
        protected string $fechaFin,
        protected string $etiquetaPeriodo = ""
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Calcula métricas y agrupaciones de WhatsApp.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function calcularEstadisticas(): void
    {
        $this->totalSolicitudes = $this->citas->count();

        if ($this->totalSolicitudes === 0) {
            return;
        }

        $conf = 0;
        $pend = 0;
        $canc = 0;

        foreach ($this->citas as $c) {
            $st = strtolower((string) ($c->estado ?? $c->whatsapp_estado ?? ""));
            if (str_contains($st, "confirmad") || str_contains($st, "atendid") || $st === "agendada") {
                $conf++;
            } elseif (str_contains($st, "cancelad") || str_contains($st, "rechazad")) {
                $canc++;
            } else {
                $pend++;
            }
        }

        $this->totalConfirmadas = $conf;
        $this->totalPendientes = $pend;
        $this->totalCanceladas = $canc;
        $this->pctConversion = round(($conf / $this->totalSolicitudes) * 100, 1);

        // 1. Resumen por Servicio
        $gruposServ = $this->citas->groupBy(function ($item) {
            $s = trim((string) ($item->nom_servicio ?? ""));

            return $s !== "" ? $s : "IMÁGENES DIAGNÓSTICAS GENERAL";
        });

        $resumenServ = [];
        foreach ($gruposServ as $nomServ => $items) {
            $cant = $items->count();
            $cantConf = $items->filter(fn ($it) => str_contains(strtolower((string) ($it->estado ?? "")), "confirm") || (string) ($it->estado ?? "") === "agendada")->count();
            $pct = round($cant / $this->totalSolicitudes, 4);

            $resumenServ[] = [
                "servicio" => (string) $nomServ,
                "total" => $cant,
                "confirmadas" => $cantConf,
                "porcentaje" => $pct,
            ];
        }
        usort($resumenServ, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $this->resumenServicios = $resumenServ;

        // 2. Resumen Estados
        $this->resumenEstados = [
            [
                "estado_legible" => "Citas Agendadas / Confirmadas",
                "estado_raw" => "confirmada",
                "total" => $this->totalConfirmadas,
                "porcentaje" => round($this->totalConfirmadas / $this->totalSolicitudes, 4),
            ],
            [
                "estado_legible" => "En Gestión / Pendientes",
                "estado_raw" => "pendiente",
                "total" => $this->totalPendientes,
                "porcentaje" => round($this->totalPendientes / $this->totalSolicitudes, 4),
            ],
            [
                "estado_legible" => "Canceladas / Descartadas",
                "estado_raw" => "cancelada",
                "total" => $this->totalCanceladas,
                "porcentaje" => round($this->totalCanceladas / $this->totalSolicitudes, 4),
            ],
        ];
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
            "A" => 12, "B" => 36, "C" => 14, "D" => 14, "E" => 16,
            "F" => 4,  "G" => 6,  "H" => 36, "I" => 16, "J" => 14,
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
        $sheet->setCellValue("A1", "SALUD TOLIMA - RESUMEN EJECUTIVO Y ESTADÍSTICAS DEL CANAL DE CITAS WHATSAPP");
        $sheet->mergeCells("A1:L1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 14, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1E8449"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Subtítulo y Metadatos
        $fechaEmision = Carbon::now()->format("Y-m-d H:i");
        $metaTexto = "Período: {$this->etiquetaPeriodo} ({$this->fechaInicio} al {$this->fechaFin})  |  Fecha de Emisión: {$fechaEmision}";
        $sheet->setCellValue("A2", $metaTexto);
        $sheet->mergeCells("A2:L2");
        $sheet->getStyle("A2")->applyFromArray([
            "font" => ["bold" => false, "color" => ["rgb" => "333333"], "size" => 10, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "D5F5E3"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // 3. Tarjetas de KPIs Ejecutivos (Filas 4 y 5)
        $sheet->setCellValue("A4", "TOTAL SOLICITUDES");
        $sheet->setCellValue("A5", number_format($this->totalSolicitudes));
        $sheet->mergeCells("A4:B4");
        $sheet->mergeCells("A5:B5");

        $sheet->setCellValue("C4", "CITAS CONFIRMADAS");
        $sheet->setCellValue("C5", number_format($this->totalConfirmadas));
        $sheet->mergeCells("C4:D4");
        $sheet->mergeCells("C5:D5");

        $sheet->setCellValue("E4", "TASA DE CONVERSIÓN");
        $sheet->setCellValue("E5", number_format($this->pctConversion, 1)."%");
        $sheet->mergeCells("E4:F4");
        $sheet->mergeCells("E5:F5");

        $sheet->setCellValue("G4", "EN GESTIÓN / PENDIENTES");
        $sheet->setCellValue("G5", number_format($this->totalPendientes));
        $sheet->mergeCells("G4:H4");
        $sheet->mergeCells("G5:H5");

        $sheet->setCellValue("I4", "CANCELADAS / DESCARTADAS");
        $sheet->setCellValue("I5", number_format($this->totalCanceladas));
        $sheet->mergeCells("I4:L4");
        $sheet->mergeCells("I5:L5");

        $sheet->getStyle("A4:L4")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "555555"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F1F3F5"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]]],
        ]);
        $sheet->getStyle("A5:L5")->applyFromArray([
            "font" => ["bold" => true, "size" => 13, "color" => ["rgb" => "1E8449"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFFFFF"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]],
                "bottom" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1E8449"]],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(24);

        // 4. Encabezados de Sección (Fila 7)
        $sheet->setCellValue("A7", "DEMANDA Y TRÁFICO POR SERVICIO CLÍNICO");
        $sheet->mergeCells("A7:E7");
        $sheet->getStyle("A7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "196F3D"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue("G7", "ESTADO DE CONVERSIÓN Y GESTIÓN CONVERSACIONAL");
        $sheet->mergeCells("G7:L7");
        $sheet->getStyle("G7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "196F3D"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(24);

        // 5. Encabezados de Columnas (Fila 8)
        $sheet->setCellValue("A8", "#");
        $sheet->setCellValue("B8", "Servicio Clínico");
        $sheet->setCellValue("C8", "Solicitudes");
        $sheet->setCellValue("D8", "Confirmadas");
        $sheet->setCellValue("E8", "% Participación");
        $sheet->getStyle("A8:E8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "239B56"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $sheet->setCellValue("G8", "#");
        $sheet->setCellValue("H8", "Estado de Solicitud");
        $sheet->setCellValue("I8", "Canal");
        $sheet->setCellValue("J8", "Solicitudes");
        $sheet->setCellValue("K8", "Conversión");
        $sheet->setCellValue("L8", "% del Total");
        $sheet->getStyle("G8:L8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "239B56"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(20);

        if ($this->totalSolicitudes === 0) {
            $sheet->setCellValue("A9", "No se registraron interacciones en el canal WhatsApp durante el período.");
            $sheet->mergeCells("A9:E9");
            $sheet->setCellValue("G9", "Sin datos.");
            $sheet->mergeCells("G9:L9");

            return [];
        }

        // 6.1 Tabla 1: Servicios
        $rServ = 9;
        $idx = 1;
        foreach ($this->resumenServicios as $s) {
            $sheet->setCellValue("A{$rServ}", $idx);
            $sheet->setCellValue("B{$rServ}", $s["servicio"]);
            $sheet->setCellValue("C{$rServ}", (int) $s["total"]);
            $sheet->setCellValue("D{$rServ}", (int) $s["confirmadas"]);
            $sheet->setCellValue("E{$rServ}", (float) $s["porcentaje"]);

            $sheet->getStyle("A{$rServ}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rServ}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rServ}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rServ}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("D{$rServ}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$rServ}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("E{$rServ}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rServ}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("A{$rServ}:E{$rServ}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rServ)->setRowHeight(20);
            $rServ++;
            $idx++;
        }

        // Total Servicios
        $sheet->setCellValue("A{$rServ}", "TOTAL");
        $sheet->setCellValue("B{$rServ}", "Total Demanda Canal");
        $sheet->setCellValue("C{$rServ}", $this->totalSolicitudes);
        $sheet->setCellValue("D{$rServ}", $this->totalConfirmadas);
        $sheet->setCellValue("E{$rServ}", 1.0);
        $sheet->getStyle("A{$rServ}:E{$rServ}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "196F3D"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "D5F5E3"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "A9DFBF"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "196F3D"]],
            ],
        ]);
        $sheet->getStyle("A{$rServ}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$rServ}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("D{$rServ}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("E{$rServ}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rServ)->setRowHeight(22);

        // 6.2 Tabla 2: Estados
        $rEst = 9;
        $j = 1;
        foreach ($this->resumenEstados as $est) {
            $sheet->setCellValue("G{$rEst}", $j);
            $sheet->setCellValue("H{$rEst}", $est["estado_legible"]);
            $sheet->setCellValue("I{$rEst}", "WhatsApp Meta");
            $sheet->setCellValue("J{$rEst}", (int) $est["total"]);
            $sheet->setCellValue("K{$rEst}", "-");
            $sheet->setCellValue("L{$rEst}", (float) $est["porcentaje"]);

            $sheet->getStyle("G{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rEst}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("K{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L{$rEst}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("G{$rEst}:L{$rEst}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rEst)->setRowHeight(20);
            $rEst++;
            $j++;
        }

        // Fila Total Estados
        $sheet->setCellValue("G{$rEst}", "TOTAL");
        $sheet->setCellValue("H{$rEst}", "Total Solicitudes WhatsApp");
        $sheet->setCellValue("I{$rEst}", "-");
        $sheet->setCellValue("J{$rEst}", $this->totalSolicitudes);
        $sheet->setCellValue("K{$rEst}", "-");
        $sheet->setCellValue("L{$rEst}", 1.0);
        $sheet->getStyle("G{$rEst}:L{$rEst}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "196F3D"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "D5F5E3"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "A9DFBF"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "196F3D"]],
            ],
        ]);
        $sheet->getStyle("G{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K{$rEst}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J{$rEst}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("L{$rEst}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rEst)->setRowHeight(22);

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
        if ($this->totalSolicitudes === 0 || empty($this->resumenEstados)) {
            return [];
        }

        $sheetTitle = $this->title();
        $rServDataStart = 9;
        $rServDataEnd = 8 + count($this->resumenServicios);
        $rEstDataStart = 9;
        $rEstDataEnd = 8 + count($this->resumenEstados);

        $startChart = max($rServDataEnd + 2, $rEstDataEnd + 2) + 3;
        $endChart = $startChart + 18;

        // 1. Gráfico de Torta: Estado de Conversión
        $pieLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$J\$8", null, 1),
        ];
        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$H\${$rEstDataStart}:\$H\${$rEstDataEnd}", null, count($this->resumenEstados)),
        ];
        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$J\${$rEstDataStart}:\$J\${$rEstDataEnd}", null, count($this->resumenEstados)),
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
        $pieTitle = new Title("Tasa de Conversión Canal WhatsApp");

        $pieChart = new Chart(
            "chart_whatsapp_pie",
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $pieChart->setTopLeftPosition("A".$startChart);
        $pieChart->setBottomRightPosition("E".$endChart);

        // 2. Gráfico de Barras: Solicitudes por Servicio
        $chartsList = [$pieChart];

        if (! empty($this->resumenServicios)) {
            $barLabels = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$8", null, 1),
            ];
            $barCategories = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\${$rServDataStart}:\$B\${$rServDataEnd}", null, count($this->resumenServicios)),
            ];
            $barValues = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$C\${$rServDataStart}:\$C\${$rServDataEnd}", null, count($this->resumenServicios)),
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
            $barTitle = new Title("Demanda por Servicio Clínico (WhatsApp)");

            $barChart = new Chart(
                "chart_whatsapp_serv_bar",
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
