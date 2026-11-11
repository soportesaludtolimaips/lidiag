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
 * Hoja de Estadísticas Consolidadas, Ranking de Sedes y Gráficos Nativos para Toda la Red Hospitalaria.
 *
 * Flujo del proceso:
 * 1. Recibe los estudios leídos en todas las sedes de la red hospitalaria en el período.
 * 2. Consolida indicadores globales: Total Estudios Red, Pacientes Únicos, Sedes Activas, TAT Promedio y Cumplimiento SLA.
 * 3. Analiza el ranking y participación de producción por sede hospitalaria.
 * 4. Analiza la distribución de volumen por modalidad técnica.
 * 5. Diseña tablas ejecutivas institucionales (#1A2274).
 * 6. Genera e incrusta gráficos ejecutivos: Torta de Participación por Sede y Barras de Modalidades.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteConsolidadoRedEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalEstudios = 0;

    protected int $totalPacientes = 0;

    protected int $totalSedes = 0;

    protected float $tatPromedioRed = 0.0;

    protected float $pctCumplimientoSla = 0.0;

    /**
     * @var array<int, array{ranking: int, sede: string, total: int, pacientes: int, tat_promedio: float, porcentaje: float}>
     */
    protected array $resumenSedes = [];

    /**
     * @var array<int, array{codigo: string, nombre: string, total: int, pacientes: int, porcentaje: float}>
     */
    protected array $resumenModalidades = [];

    /**
     * Constructor con inyección de datos de red.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected Collection $estudios,
        protected string $fechaInicio,
        protected string $fechaFin,
        protected string $etiquetaPeriodo = ""
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Calcula métricas y agrupaciones de la red.
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

        $this->totalPacientes = $this->estudios
            ->pluck("documento")
            ->filter(fn ($v) => ! empty($v) && $v !== "N/A")
            ->unique()
            ->count();

        $tats = $this->estudios->pluck("tat_horas")->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v);
        $this->tatPromedioRed = $tats->count() > 0 ? round($tats->avg(), 1) : 0.0;

        $cumplen = $this->estudios->filter(fn ($e) => str_contains((string) ($e->semaforo_oportunidad ?? ""), "Normal"))->count();
        $this->pctCumplimientoSla = round(($cumplen / $this->totalEstudios) * 100, 1);

        // 1. Ranking por Sede
        $gruposSede = $this->estudios->groupBy(function ($item) {
            $s = trim((string) ($item->nom_sede ?? ""));

            return $s !== "" ? $s : "SEDE SIN NOMBRE";
        });

        $this->totalSedes = $gruposSede->count();

        $resumenSede = [];
        foreach ($gruposSede as $nomSede => $items) {
            $cant = $items->count();
            $pacs = $items->pluck("documento")->filter(fn ($v) => ! empty($v) && $v !== "N/A")->unique()->count();
            $itemsTats = $items->pluck("tat_horas")->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v);
            $avgTat = $itemsTats->count() > 0 ? round($itemsTats->avg(), 1) : 0.0;
            $pct = round($cant / $this->totalEstudios, 4);

            $resumenSede[] = [
                "ranking" => 0,
                "sede" => (string) $nomSede,
                "total" => $cant,
                "pacientes" => $pacs,
                "tat_promedio" => $avgTat,
                "porcentaje" => $pct,
            ];
        }
        usort($resumenSede, fn ($a, $b) => $b["total"] <=> $a["total"]);
        foreach ($resumenSede as $i => &$item) {
            $item["ranking"] = $i + 1;
        }
        unset($item);
        $this->resumenSedes = $resumenSede;

        // 2. Modalidades de la Red
        $gruposMod = $this->estudios->groupBy(function ($item) {
            $m = trim((string) ($item->mods_in_study ?? ""));

            return $m !== "" ? strtoupper($m) : "OTRA";
        });

        $modMeta = [
            "CT" => "Tomografía Computarizada (CT)",
            "MR" => "Resonancia Magnética (MR)",
            "DX" => "Radiografía Digital (DX)",
            "CR" => "Radiografía Computarizada (CR)",
            "MG" => "Mamografía Digital (MG)",
            "US" => "Ecografía / Ultrasonido (US)",
            "OTRA" => "Otras Modalidades",
        ];

        $resumenMod = [];
        foreach ($gruposMod as $cod => $items) {
            $cant = $items->count();
            $pacs = $items->pluck("documento")->filter(fn ($v) => ! empty($v) && $v !== "N/A")->unique()->count();
            $pct = round($cant / $this->totalEstudios, 4);

            $resumenMod[] = [
                "codigo" => (string) $cod,
                "nombre" => $modMeta[$cod] ?? "Modalidad {$cod}",
                "total" => $cant,
                "pacientes" => $pacs,
                "porcentaje" => $pct,
            ];
        }
        usort($resumenMod, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $this->resumenModalidades = $resumenMod;
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
            "A" => 6,  "B" => 34, "C" => 14, "D" => 14, "E" => 16,
            "F" => 4,  "G" => 12, "H" => 36, "I" => 14, "J" => 14,
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
        $sheet->setCellValue("A1", "SALUD TOLIMA - CONSOLIDADO Y RENDIMIENTO DE TODA LA RED HOSPITALARIA");
        $sheet->mergeCells("A1:L1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 14, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
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
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "E9ECEF"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // 3. Tarjetas de KPIs Ejecutivos (Filas 4 y 5)
        $sheet->setCellValue("A4", "TOTAL ESTUDIOS RED");
        $sheet->setCellValue("A5", number_format($this->totalEstudios));
        $sheet->mergeCells("A4:B4");
        $sheet->mergeCells("A5:B5");

        $sheet->setCellValue("C4", "PACIENTES ÚNICOS");
        $sheet->setCellValue("C5", number_format($this->totalPacientes));
        $sheet->mergeCells("C4:D4");
        $sheet->mergeCells("C5:D5");

        $sheet->setCellValue("E4", "SEDES ACTIVAS");
        $sheet->setCellValue("E5", (string) $this->totalSedes);
        $sheet->mergeCells("E4:F4");
        $sheet->mergeCells("E5:F5");

        $sheet->setCellValue("G4", "TAT PROMEDIO RED");
        $sheet->setCellValue("G5", number_format($this->tatPromedioRed, 1)." horas");
        $sheet->mergeCells("G4:H4");
        $sheet->mergeCells("G5:H5");

        $sheet->setCellValue("I4", "% CUMPLIMIENTO SLA");
        $sheet->setCellValue("I5", number_format($this->pctCumplimientoSla, 1)."% Conforme");
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
        $sheet->setCellValue("A7", "RANKING Y PARTICIPACIÓN DE PRODUCCIÓN POR SEDE");
        $sheet->mergeCells("A7:E7");
        $sheet->getStyle("A7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue("G7", "DISTRIBUCIÓN DE PRODUCCIÓN POR MODALIDAD EN LA RED");
        $sheet->mergeCells("G7:L7");
        $sheet->getStyle("G7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(24);

        // 5. Encabezados de Columnas (Fila 8)
        $sheet->setCellValue("A8", "#");
        $sheet->setCellValue("B8", "Sede Hospitalaria");
        $sheet->setCellValue("C8", "Estudios");
        $sheet->setCellValue("D8", "Pacientes");
        $sheet->setCellValue("E8", "% Cuota Red");
        $sheet->getStyle("A8:E8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $sheet->setCellValue("G8", "Código");
        $sheet->setCellValue("H8", "Modalidad Diagnóstica");
        $sheet->setCellValue("I8", "Estudios");
        $sheet->setCellValue("J8", "Pacientes");
        $sheet->setCellValue("K8", "TAT Ref");
        $sheet->setCellValue("L8", "% del Total");
        $sheet->getStyle("G8:L8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(20);

        if ($this->totalEstudios === 0) {
            $sheet->setCellValue("A9", "No se encontraron estudios leídos en la red durante el período.");
            $sheet->mergeCells("A9:E9");
            $sheet->setCellValue("G9", "Sin datos de red.");
            $sheet->mergeCells("G9:L9");

            return [];
        }

        // 6.1 Tabla 1: Sedes
        $rSede = 9;
        foreach ($this->resumenSedes as $s) {
            $sheet->setCellValue("A{$rSede}", $s["ranking"]);
            $sheet->setCellValue("B{$rSede}", $s["sede"]);
            $sheet->setCellValue("C{$rSede}", (int) $s["total"]);
            $sheet->setCellValue("D{$rSede}", (int) $s["pacientes"]);
            $sheet->setCellValue("E{$rSede}", (float) $s["porcentaje"]);

            $sheet->getStyle("A{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rSede}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("D{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$rSede}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("E{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rSede}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("A{$rSede}:E{$rSede}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rSede)->setRowHeight(20);
            $rSede++;
        }

        // Total Sedes
        $sheet->setCellValue("A{$rSede}", "TOTAL");
        $sheet->setCellValue("B{$rSede}", "Toda la Red Hospitalaria");
        $sheet->setCellValue("C{$rSede}", $this->totalEstudios);
        $sheet->setCellValue("D{$rSede}", $this->totalPacientes);
        $sheet->setCellValue("E{$rSede}", 1.0);
        $sheet->getStyle("A{$rSede}:E{$rSede}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("A{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$rSede}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("D{$rSede}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("E{$rSede}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rSede)->setRowHeight(22);

        // 6.2 Tabla 2: Modalidades
        $rMod = 9;
        foreach ($this->resumenModalidades as $m) {
            $sheet->setCellValue("G{$rMod}", $m["codigo"]);
            $sheet->setCellValue("H{$rMod}", $m["nombre"]);
            $sheet->setCellValue("I{$rMod}", (int) $m["total"]);
            $sheet->setCellValue("J{$rMod}", (int) $m["pacientes"]);
            $sheet->setCellValue("K{$rMod}", "-");
            $sheet->setCellValue("L{$rMod}", (float) $m["porcentaje"]);

            $sheet->getStyle("G{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("J{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("K{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L{$rMod}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("G{$rMod}:L{$rMod}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rMod)->setRowHeight(20);
            $rMod++;
        }

        // Fila Total Modalidades
        $sheet->setCellValue("G{$rMod}", "TOTAL");
        $sheet->setCellValue("H{$rMod}", "Consolidado de Modalidades");
        $sheet->setCellValue("I{$rMod}", $this->totalEstudios);
        $sheet->setCellValue("J{$rMod}", $this->totalPacientes);
        $sheet->setCellValue("K{$rMod}", "-");
        $sheet->setCellValue("L{$rMod}", 1.0);
        $sheet->getStyle("G{$rMod}:L{$rMod}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("G{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("J{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("L{$rMod}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rMod)->setRowHeight(22);

        return [];
    }

    /**
     * Genera los gráficos nativos de Excel.
     *
     * @returns Chart[]
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function charts(): array
    {
        if ($this->totalEstudios === 0 || empty($this->resumenSedes)) {
            return [];
        }

        $sheetTitle = $this->title();
        $rSedeDataStart = 9;
        $rSedeDataEnd = 8 + count($this->resumenSedes);
        $rModDataStart = 9;
        $rModDataEnd = 8 + count($this->resumenModalidades);

        $startChart = max($rSedeDataEnd + 2, $rModDataEnd + 2) + 3;
        $endChart = $startChart + 18;

        // 1. Gráfico de Torta: Participación por Sede
        $pieLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$8", null, 1),
        ];
        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\${$rSedeDataStart}:\$B\${$rSedeDataEnd}", null, count($this->resumenSedes)),
        ];
        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$C\${$rSedeDataStart}:\$C\${$rSedeDataEnd}", null, count($this->resumenSedes)),
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
        $pieTitle = new Title("Cuota de Producción por Sede Hospitalaria");

        $pieChart = new Chart(
            "chart_red_sedes_pie",
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $pieChart->setTopLeftPosition("A".$startChart);
        $pieChart->setBottomRightPosition("E".$endChart);

        // 2. Gráfico de Barras: Volumen por Modalidad en la Red
        $barLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$I\$8", null, 1),
        ];
        $barCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$H\${$rModDataStart}:\$H\${$rModDataEnd}", null, count($this->resumenModalidades)),
        ];
        $barValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$I\${$rModDataStart}:\$I\${$rModDataEnd}", null, count($this->resumenModalidades)),
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
        $barTitle = new Title("Volumen de Estudios por Modalidad en la Red");

        $barChart = new Chart(
            "chart_red_mod_bar",
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
