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
 * Hoja de Estadísticas Consolidadas, Tasa de Devolución y Gráficos Nativos para Tele-Radiología.
 *
 * Flujo del proceso:
 * 1. Recibe los registros derivados al servicio de lectura remota / tele-radiología.
 * 2. Consolida métricas: Total Derivados, Leídos/Devueltos, Pendientes, Tasa de Devolución y TAT Promedio.
 * 3. Analiza la distribución de derivación por modalidad diagnóstica (Tomografía, Resonancia, etc.).
 * 4. Analiza la carga de trabajo y oportunidad asignada por médico radiólogo externo.
 * 5. Diseña tablas ejecutivas institucionales (#1A2274).
 * 6. Genera e incrusta gráficos ejecutivos nativos de PhpSpreadsheet: Torta de Estado y Barras de Modalidades.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteTeleRadiologiaEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalEstudios = 0;

    protected int $totalLeidos = 0;

    protected int $totalPendientes = 0;

    protected float $pctDevolucion = 0.0;

    protected float $tatPromedioHoras = 0.0;

    /**
     * @var array<int, array{codigo: string, nombre: string, total: int, leidos: int, pendientes: int, pct_devolucion: float}>
     */
    protected array $resumenModalidades = [];

    /**
     * @var array<int, array{categoria: string, estado: string, total: int, pct_total: float}>
     */
    protected array $resumenEstados = [];

    /**
     * Constructor con inyección de datos de tele-radiología.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected Collection $estudios,
        protected string $sedeNombre,
        protected string $fechaInicio,
        protected string $fechaFin
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Calcula métricas y agrupaciones de tele-radiología.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function calcularEstadisticas(): void
    {
        $this->totalEstudios = $this->estudios->count();

        if ($this->totalEstudios === 0) {
            return;
        }

        $leidos = $this->estudios->filter(fn ($e) => str_contains((string) ($e->estado_lectura ?? ""), "Leído"))->count();
        $pendientes = $this->totalEstudios - $leidos;

        $this->totalLeidos = $leidos;
        $this->totalPendientes = $pendientes;
        $this->pctDevolucion = round(($leidos / $this->totalEstudios) * 100, 1);

        $tats = $this->estudios->pluck("tat_horas")->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v);
        $this->tatPromedioHoras = $tats->count() > 0 ? round($tats->avg(), 1) : 0.0;

        // 1. Agrupación por Modalidad
        $gruposMod = $this->estudios->groupBy(function ($item) {
            $m = trim((string) ($item->modalidad ?? ""));

            return $m !== "" ? strtoupper($m) : "OTRA";
        });

        $modMeta = [
            "CT" => "Tomografía Computarizada (CT)",
            "MR" => "Resonancia Magnética (MR)",
            "DX" => "Radiografía Digital (DX)",
            "CR" => "Radiografía Computarizada (CR)",
            "MG" => "Mamografía Digital (MG)",
            "US" => "Ecografía (US)",
            "OTRA" => "Otras Modalidades",
        ];

        $resumenMod = [];
        foreach ($gruposMod as $cod => $items) {
            $cant = $items->count();
            $cantLeidos = $items->filter(fn ($it) => str_contains((string) ($it->estado_lectura ?? ""), "Leído"))->count();
            $cantPend = $cant - $cantLeidos;
            $pctDev = $cant > 0 ? round($cantLeidos / $cant, 4) : 0.0;

            $resumenMod[] = [
                "codigo" => (string) $cod,
                "nombre" => $modMeta[$cod] ?? "Modalidad {$cod}",
                "total" => $cant,
                "leidos" => $cantLeidos,
                "pendientes" => $cantPend,
                "pct_devolucion" => $pctDev,
            ];
        }
        usort($resumenMod, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $this->resumenModalidades = $resumenMod;

        // 2. Resumen Estados para Gráfico de Torta
        $this->resumenEstados = [
            [
                "categoria" => "Leídos / Devueltos",
                "estado" => "🟢 Con Informe",
                "total" => $this->totalLeidos,
                "pct_total" => round($this->totalLeidos / $this->totalEstudios, 4),
            ],
            [
                "categoria" => "Pendientes de Lectura",
                "estado" => "🟡 En Espera",
                "total" => $this->totalPendientes,
                "pct_total" => round($this->totalPendientes / $this->totalEstudios, 4),
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
            "A" => 12, "B" => 34, "C" => 14, "D" => 14, "E" => 16,
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
        $sheet->setCellValue("A1", "SALUD TOLIMA - RESUMEN EJECUTIVO Y ESTADÍSTICAS DE TELE-RADIOLOGÍA");
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
        $sheet->setCellValue("A4", "TOTAL DERIVADOS");
        $sheet->setCellValue("A5", number_format($this->totalEstudios));
        $sheet->mergeCells("A4:B4");
        $sheet->mergeCells("A5:B5");

        $sheet->setCellValue("C4", "LEÍDOS / DEVUELTOS");
        $sheet->setCellValue("C5", number_format($this->totalLeidos));
        $sheet->mergeCells("C4:D4");
        $sheet->mergeCells("C5:D5");

        $sheet->setCellValue("E4", "PENDIENTES LECTURA");
        $sheet->setCellValue("E5", number_format($this->totalPendientes));
        $sheet->mergeCells("E4:F4");
        $sheet->mergeCells("E5:F5");

        $sheet->setCellValue("G4", "TASA DE DEVOLUCIÓN");
        $sheet->setCellValue("G5", number_format($this->pctDevolucion, 1)."%");
        $sheet->mergeCells("G4:H4");
        $sheet->mergeCells("G5:H5");

        $sheet->setCellValue("I4", "TAT PROMEDIO REMOTO");
        $sheet->setCellValue("I5", number_format($this->tatPromedioHoras, 1)." horas");
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
        $sheet->setCellValue("A7", "DISTRIBUCIÓN DE TELE-RADIOLOGÍA POR MODALIDAD");
        $sheet->mergeCells("A7:E7");
        $sheet->getStyle("A7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue("G7", "ESTADO Y DEVOLUCIÓN DE INFORMES REMOTOS");
        $sheet->mergeCells("G7:L7");
        $sheet->getStyle("G7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(24);

        // 5. Encabezados de Columnas (Fila 8)
        $sheet->setCellValue("A8", "Código");
        $sheet->setCellValue("B8", "Modalidad Diagnóstica");
        $sheet->setCellValue("C8", "Derivados");
        $sheet->setCellValue("D8", "Leídos");
        $sheet->setCellValue("E8", "% Devolución");
        $sheet->getStyle("A8:E8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $sheet->setCellValue("G8", "#");
        $sheet->setCellValue("H8", "Estado de Lectura Remota");
        $sheet->setCellValue("I8", "Semáforo");
        $sheet->setCellValue("J8", "Estudios");
        $sheet->setCellValue("K8", "Pacientes");
        $sheet->setCellValue("L8", "% del Total");
        $sheet->getStyle("G8:L8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(20);

        if ($this->totalEstudios === 0) {
            $sheet->setCellValue("A9", "No se encontraron estudios derivados a tele-radiología en el período.");
            $sheet->mergeCells("A9:E9");
            $sheet->setCellValue("G9", "Sin datos remotos.");
            $sheet->mergeCells("G9:L9");

            return [];
        }

        // 6.1 Tabla 1: Modalidades
        $rMod = 9;
        foreach ($this->resumenModalidades as $m) {
            $sheet->setCellValue("A{$rMod}", $m["codigo"]);
            $sheet->setCellValue("B{$rMod}", $m["nombre"]);
            $sheet->setCellValue("C{$rMod}", (int) $m["total"]);
            $sheet->setCellValue("D{$rMod}", (int) $m["leidos"]);
            $sheet->setCellValue("E{$rMod}", (float) $m["pct_devolucion"]);

            $sheet->getStyle("A{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("D{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("E{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rMod}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("A{$rMod}:E{$rMod}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rMod)->setRowHeight(20);
            $rMod++;
        }

        // Total Modalidades
        $sheet->setCellValue("A{$rMod}", "TOTAL");
        $sheet->setCellValue("B{$rMod}", "Consolidado Tele-radiología");
        $sheet->setCellValue("C{$rMod}", $this->totalEstudios);
        $sheet->setCellValue("D{$rMod}", $this->totalLeidos);
        $sheet->setCellValue("E{$rMod}", round($this->pctDevolucion / 100, 4));
        $sheet->getStyle("A{$rMod}:E{$rMod}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("A{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("D{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("E{$rMod}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rMod)->setRowHeight(22);

        // 6.2 Tabla 2: Estados
        $rEst = 9;
        $i = 1;
        foreach ($this->resumenEstados as $est) {
            $sheet->setCellValue("G{$rEst}", $i);
            $sheet->setCellValue("H{$rEst}", $est["categoria"]);
            $sheet->setCellValue("I{$rEst}", $est["estado"]);
            $sheet->setCellValue("J{$rEst}", (int) $est["total"]);
            $sheet->setCellValue("K{$rEst}", "-");
            $sheet->setCellValue("L{$rEst}", (float) $est["pct_total"]);

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
            $i++;
        }

        // Fila Total Estados
        $sheet->setCellValue("G{$rEst}", "TOTAL");
        $sheet->setCellValue("H{$rEst}", "Total Estudios");
        $sheet->setCellValue("I{$rEst}", "-");
        $sheet->setCellValue("J{$rEst}", $this->totalEstudios);
        $sheet->setCellValue("K{$rEst}", "-");
        $sheet->setCellValue("L{$rEst}", 1.0);
        $sheet->getStyle("G{$rEst}:L{$rEst}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
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
        if ($this->totalEstudios === 0 || empty($this->resumenModalidades)) {
            return [];
        }

        $sheetTitle = $this->title();
        $rModDataStart = 9;
        $rModDataEnd = 8 + count($this->resumenModalidades);
        $rEstDataStart = 9;
        $rEstDataEnd = 8 + count($this->resumenEstados);

        $startChart = max($rModDataEnd + 2, $rEstDataEnd + 2) + 3;
        $endChart = $startChart + 18;

        // 1. Gráfico de Torta: Estado de Lectura Remota
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
        $pieTitle = new Title("Estado de Informes de Tele-Radiología");

        $pieChart = new Chart(
            "chart_tele_estado_pie",
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $pieChart->setTopLeftPosition("A".$startChart);
        $pieChart->setBottomRightPosition("E".$endChart);

        // 2. Gráfico de Barras: Derivación por Modalidad
        $barLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$8", null, 1),
        ];
        $barCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\${$rModDataStart}:\$B\${$rModDataEnd}", null, count($this->resumenModalidades)),
        ];
        $barValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$C\${$rModDataStart}:\$C\${$rModDataEnd}", null, count($this->resumenModalidades)),
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
        $barTitle = new Title("Volumen Derivado por Modalidad Diagnóstica");

        $barChart = new Chart(
            "chart_tele_mod_bar",
            $barTitle,
            $barLegend,
            $barPlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $barChart->setTopLeftPosition("G".$startChart);
        $barChart->setBottomRightPosition("L".$endChart);

        return [$pieChart, $barChart];
    }
}
