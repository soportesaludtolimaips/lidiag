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
 * Hoja de Estadísticas Consolidadas, Semáforo de Severidad y Gráficos Nativos para Estudios Represados y Urgencias.
 *
 * Flujo del proceso:
 * 1. Recibe los estudios pendientes de informe médico fuera de SLA (>24h) o urgencias acumuladas.
 * 2. Consolida indicadores de riesgo asistencial: Total Represados, Urgencias Fuera de SLA, Casos Severos (>72h) y Espera Máxima.
 * 3. Analiza la distribución semáforo por nivel de severidad (Preventivo, Crítico, Severo, Urgencias).
 * 4. Analiza la distribución de represamiento por sede hospitalaria afectada.
 * 5. Diseña tablas ejecutivas institucionales de alerta médica (#1A2274 / #C0392B).
 * 6. Genera e incrusta gráficos ejecutivos: Torta de Severidad y Barras de Represamiento por Sede.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteRepresadosEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalRepresados = 0;

    protected int $totalUrgencias = 0;

    protected int $totalSeveros = 0;

    protected int $totalCriticos = 0;

    protected int $totalPreventivos = 0;

    protected int $horasEsperaMax = 0;

    /**
     * @var array<int, array{nivel: string, estado: string, total: int, horas_ref: string, porcentaje: float}>
     */
    protected array $resumenSemaforo = [];

    /**
     * @var array<int, array{ranking: int, sede: string, total: int, urgencias: int, horas_max: int, porcentaje: float}>
     */
    protected array $resumenSedes = [];

    /**
     * Constructor con inyección de datos de represamiento.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected Collection $estudios,
        protected string $sedeNombre = "Todas las Sedes"
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Calcula métricas y agrupaciones de severidad y sedes.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function calcularEstadisticas(): void
    {
        $this->totalRepresados = $this->estudios->count();

        if ($this->totalRepresados === 0) {
            return;
        }

        $horas = $this->estudios->pluck("horas_espera")->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (int) $v);
        $this->horasEsperaMax = $horas->count() > 0 ? (int) $horas->max() : 0;

        $urg = 0;
        $sev = 0;
        $crit = 0;
        $prev = 0;

        foreach ($this->estudios as $e) {
            $sem = (string) ($e->semaforo ?? "");
            if (str_contains($sem, "URGENCIA")) {
                $urg++;
            } elseif (str_contains($sem, "Severo")) {
                $sev++;
            } elseif (str_contains($sem, "Crítico")) {
                $crit++;
            } else {
                $prev++;
            }
        }

        $this->totalUrgencias = $urg;
        $this->totalSeveros = $sev;
        $this->totalCriticos = $crit;
        $this->totalPreventivos = $prev;

        // 1. Resumen de Semáforo
        $this->resumenSemaforo = [
            [
                "nivel" => "Urgencias fuera de SLA",
                "estado" => "🚨 Crítico (>30 min)",
                "total" => $this->totalUrgencias,
                "horas_ref" => ">0.5 h",
                "porcentaje" => round($this->totalUrgencias / $this->totalRepresados, 4),
            ],
            [
                "nivel" => "Severo (>72 horas)",
                "estado" => "🔴 Retraso Severo",
                "total" => $this->totalSeveros,
                "horas_ref" => ">72 h",
                "porcentaje" => round($this->totalSeveros / $this->totalRepresados, 4),
            ],
            [
                "nivel" => "Crítico (48-72 horas)",
                "estado" => "🟠 Retraso Crítico",
                "total" => $this->totalCriticos,
                "horas_ref" => "48-72 h",
                "porcentaje" => round($this->totalCriticos / $this->totalRepresados, 4),
            ],
            [
                "nivel" => "Preventivo (24-48 horas)",
                "estado" => "🟡 Alerta Preventiva",
                "total" => $this->totalPreventivos,
                "horas_ref" => "24-48 h",
                "porcentaje" => round($this->totalPreventivos / $this->totalRepresados, 4),
            ],
        ];

        // 2. Resumen por Sede
        $gruposSede = $this->estudios->groupBy(function ($item) {
            $s = trim((string) ($item->nom_sede ?? ""));

            return $s !== "" ? $s : "SEDE SIN NOMBRE";
        });

        $resumenSede = [];
        foreach ($gruposSede as $nomSede => $items) {
            $cant = $items->count();
            $cantUrg = $items->filter(fn ($it) => str_contains((string) ($it->semaforo ?? ""), "URGENCIA"))->count();
            $itemsHoras = $items->pluck("horas_espera")->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (int) $v);
            $maxH = $itemsHoras->count() > 0 ? (int) $itemsHoras->max() : 0;
            $pct = round($cant / $this->totalRepresados, 4);

            $resumenSede[] = [
                "ranking" => 0,
                "sede" => (string) $nomSede,
                "total" => $cant,
                "urgencias" => $cantUrg,
                "horas_max" => $maxH,
                "porcentaje" => $pct,
            ];
        }
        usort($resumenSede, fn ($a, $b) => $b["total"] <=> $a["total"]);
        foreach ($resumenSede as $i => &$item) {
            $item["ranking"] = $i + 1;
        }
        unset($item);
        $this->resumenSedes = $resumenSede;
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
            "A" => 12, "B" => 32, "C" => 14, "D" => 14, "E" => 16,
            "F" => 4,  "G" => 6,  "H" => 36, "I" => 14, "J" => 14,
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
        $sheet->setCellValue("A1", "SALUD TOLIMA - SEMÁFORO DE REPRESAMIENTO ASISTENCIAL Y URGENCIAS FUERA DE SLA");
        $sheet->mergeCells("A1:L1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 14, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "8B0000"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Subtítulo y Metadatos
        $fechaEmision = Carbon::now()->format("Y-m-d H:i");
        $metaTexto = "Sede: {$this->sedeNombre}  |  Corte: En tiempo real al {$fechaEmision}";
        $sheet->setCellValue("A2", $metaTexto);
        $sheet->mergeCells("A2:L2");
        $sheet->getStyle("A2")->applyFromArray([
            "font" => ["bold" => false, "color" => ["rgb" => "333333"], "size" => 10, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FADBD8"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // 3. Tarjetas de KPIs Ejecutivos (Filas 4 y 5)
        $sheet->setCellValue("A4", "TOTAL REPRESADOS (>24H)");
        $sheet->setCellValue("A5", number_format($this->totalRepresados));
        $sheet->mergeCells("A4:B4");
        $sheet->mergeCells("A5:B5");

        $sheet->setCellValue("C4", "URGENCIAS CRÍTICAS (>30M)");
        $sheet->setCellValue("C5", number_format($this->totalUrgencias));
        $sheet->mergeCells("C4:D4");
        $sheet->mergeCells("C5:D5");

        $sheet->setCellValue("E4", "CASOS SEVEROS (>72H)");
        $sheet->setCellValue("E5", number_format($this->totalSeveros));
        $sheet->mergeCells("E4:F4");
        $sheet->mergeCells("E5:F5");

        $sheet->setCellValue("G4", "ESPERA MÁXIMA");
        $sheet->setCellValue("G5", number_format($this->horasEsperaMax)." horas");
        $sheet->mergeCells("G4:H4");
        $sheet->mergeCells("G5:H5");

        $sheet->setCellValue("I4", "SEDES CON RETRASO");
        $sheet->setCellValue("I5", (string) count($this->resumenSedes));
        $sheet->mergeCells("I4:L4");
        $sheet->mergeCells("I5:L5");

        $sheet->getStyle("A4:L4")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "555555"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F1F3F5"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]]],
        ]);
        $sheet->getStyle("A5:L5")->applyFromArray([
            "font" => ["bold" => true, "size" => 13, "color" => ["rgb" => "8B0000"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFFFFF"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]],
                "bottom" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "8B0000"]],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(24);

        // 4. Encabezados de Sección (Fila 7)
        $sheet->setCellValue("A7", "DISTRIBUCIÓN DE RETRASO POR NIVEL DE SEVERIDAD");
        $sheet->mergeCells("A7:E7");
        $sheet->getStyle("A7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "4A235A"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue("G7", "DISTRIBUCIÓN DE REPRESAMIENTO POR SEDE HOSPITALARIA");
        $sheet->mergeCells("G7:L7");
        $sheet->getStyle("G7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "4A235A"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(24);

        // 5. Encabezados de Columnas (Fila 8)
        $sheet->setCellValue("A8", "Estado");
        $sheet->setCellValue("B8", "Nivel de Severidad");
        $sheet->setCellValue("C8", "Estudios");
        $sheet->setCellValue("D8", "Rango Ref");
        $sheet->setCellValue("E8", "% Cuota");
        $sheet->getStyle("A8:E8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "5B2C6F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $sheet->setCellValue("G8", "#");
        $sheet->setCellValue("H8", "Sede Hospitalaria");
        $sheet->setCellValue("I8", "Urgencias");
        $sheet->setCellValue("J8", "Represados");
        $sheet->setCellValue("K8", "Espera Máx");
        $sheet->setCellValue("L8", "% de la Red");
        $sheet->getStyle("G8:L8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "5B2C6F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(20);

        if ($this->totalRepresados === 0) {
            $sheet->setCellValue("A9", "¡Excelente! No se registran estudios represados ni urgencias fuera de SLA.");
            $sheet->mergeCells("A9:E9");
            $sheet->setCellValue("G9", "Sin represamiento.");
            $sheet->mergeCells("G9:L9");

            return [];
        }

        // 6.1 Tabla 1: Semáforo
        $rSem = 9;
        foreach ($this->resumenSemaforo as $sem) {
            $sheet->setCellValue("A{$rSem}", $sem["estado"]);
            $sheet->setCellValue("B{$rSem}", $sem["nivel"]);
            $sheet->setCellValue("C{$rSem}", (int) $sem["total"]);
            $sheet->setCellValue("D{$rSem}", $sem["horas_ref"]);
            $sheet->setCellValue("E{$rSem}", (float) $sem["porcentaje"]);

            $sheet->getStyle("A{$rSem}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rSem}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rSem}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rSem}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("D{$rSem}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rSem}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rSem}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("A{$rSem}:E{$rSem}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rSem)->setRowHeight(20);
            $rSem++;
        }

        // Total Semáforo
        $sheet->setCellValue("A{$rSem}", "TOTAL");
        $sheet->setCellValue("B{$rSem}", "Consolidado Represamiento");
        $sheet->setCellValue("C{$rSem}", $this->totalRepresados);
        $sheet->setCellValue("D{$rSem}", "-");
        $sheet->setCellValue("E{$rSem}", 1.0);
        $sheet->getStyle("A{$rSem}:E{$rSem}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "8B0000"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FADBD8"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "F1948A"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "8B0000"]],
            ],
        ]);
        $sheet->getStyle("A{$rSem}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$rSem}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$rSem}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("E{$rSem}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rSem)->setRowHeight(22);

        // 6.2 Tabla 2: Sedes
        $rSede = 9;
        foreach ($this->resumenSedes as $s) {
            $sheet->setCellValue("G{$rSede}", $s["ranking"]);
            $sheet->setCellValue("H{$rSede}", $s["sede"]);
            $sheet->setCellValue("I{$rSede}", (int) $s["urgencias"]);
            $sheet->setCellValue("J{$rSede}", (int) $s["total"]);
            $sheet->setCellValue("K{$rSede}", $s["horas_max"]." h");
            $sheet->setCellValue("L{$rSede}", (float) $s["porcentaje"]);

            $sheet->getStyle("G{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I{$rSede}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("J{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rSede}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("K{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L{$rSede}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("G{$rSede}:L{$rSede}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rSede)->setRowHeight(20);
            $rSede++;
        }

        // Fila Total Sedes
        $sheet->setCellValue("G{$rSede}", "TOTAL");
        $sheet->setCellValue("H{$rSede}", "Toda la Red");
        $sheet->setCellValue("I{$rSede}", $this->totalUrgencias);
        $sheet->setCellValue("J{$rSede}", $this->totalRepresados);
        $sheet->setCellValue("K{$rSede}", $this->horasEsperaMax." h");
        $sheet->setCellValue("L{$rSede}", 1.0);
        $sheet->getStyle("G{$rSede}:L{$rSede}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "8B0000"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FADBD8"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "F1948A"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "8B0000"]],
            ],
        ]);
        $sheet->getStyle("G{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$rSede}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("J{$rSede}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("L{$rSede}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rSede)->setRowHeight(22);

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
        if ($this->totalRepresados === 0 || empty($this->resumenSemaforo)) {
            return [];
        }

        $sheetTitle = $this->title();
        $rSemDataStart = 9;
        $rSemDataEnd = 8 + count($this->resumenSemaforo);
        $rSedeDataStart = 9;
        $rSedeDataEnd = 8 + count($this->resumenSedes);

        $startChart = max($rSemDataEnd + 2, $rSedeDataEnd + 2) + 3;
        $endChart = $startChart + 18;

        // 1. Gráfico de Torta: Distribución por Severidad
        $pieLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$8", null, 1),
        ];
        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\${$rSemDataStart}:\$B\${$rSemDataEnd}", null, count($this->resumenSemaforo)),
        ];
        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$C\${$rSemDataStart}:\$C\${$rSemDataEnd}", null, count($this->resumenSemaforo)),
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
        $pieTitle = new Title("Distribución de Casos por Severidad Semáforo");

        $pieChart = new Chart(
            "chart_represados_pie",
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $pieChart->setTopLeftPosition("A".$startChart);
        $pieChart->setBottomRightPosition("E".$endChart);

        // 2. Gráfico de Barras: Represamiento por Sede
        $barLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$J\$8", null, 1),
        ];
        $barCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$H\${$rSedeDataStart}:\$H\${$rSedeDataEnd}", null, count($this->resumenSedes)),
        ];
        $barValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$J\${$rSedeDataStart}:\$J\${$rSedeDataEnd}", null, count($this->resumenSedes)),
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
        $barTitle = new Title("Estudios Represados por Sede Hospitalaria");

        $barChart = new Chart(
            "chart_represados_sede_bar",
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
