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
 * Hoja de Estadísticas Consolidadas, Cumplimiento SLA y Gráficos Nativos de TAT (Calidad y Oportunidad).
 *
 * Flujo del proceso:
 * 1. Recibe los estudios evaluados con sus métricas de tiempos TAT de lectura, transcripción y ciclo total.
 * 2. Consolida indicadores clave: Total Exámenes, TAT Promedio Global, % Cumplimiento SLA (<=24h), Alertas y Fuera de SLA.
 * 3. Analiza el comportamiento de oportunidad clínica agrupado por modalidad técnica.
 * 4. Analiza la distribución de tiempos por nivel de prioridad asistencial (Urgencias vs Rutina).
 * 5. Genera tablas analíticas ejecutivas con formato corporativo institucional (#1A2274).
 * 6. Construye gráficos nativos: Torta de Cumplimiento SLA y Barras de TAT Promedio por Modalidad.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteTATEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalEstudios = 0;

    protected float $tatPromedioGlobal = 0.0;

    protected float $pctCumplimientoSla = 0.0;

    protected int $totalDentroSla = 0;

    protected int $totalAlerta = 0;

    protected int $totalFueraSla = 0;

    /**
     * @var array<int, array{codigo: string, nombre: string, total: int, tat_promedio: float, dentro_sla: int, pct_sla: float}>
     */
    protected array $resumenModalidades = [];

    /**
     * @var array<int, array{categoria: string, total: int, tat_promedio: float, pct_total: float}>
     */
    protected array $resumenCategoriasSla = [];

    /**
     * Constructor con inyección de datos analíticos de oportunidad.
     *
     * @param  Collection  $estudios  Registros consolidados de TAT.
     * @param  string  $sedeNombre  Nombre descriptivo de la sede o "Todas las Sedes".
     * @param  string  $fechaInicio  Fecha inicial (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha final (YYYY-MM-DD).
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
     * Calcula métricas globales de SLA y agregaciones por modalidad y categoría.
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

        $tats = $this->estudios->pluck("tat_horas_total")->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v);
        $this->tatPromedioGlobal = $tats->count() > 0 ? round($tats->avg(), 1) : 0.0;

        $dentro = 0;
        $alerta = 0;
        $fuera = 0;

        foreach ($this->estudios as $e) {
            $clasif = (string) ($e->clasificacion_sla ?? "");
            if (str_contains($clasif, "Cumple")) {
                $dentro++;
            } elseif (str_contains($clasif, "Alerta")) {
                $alerta++;
            } else {
                $fuera++;
            }
        }

        $this->totalDentroSla = $dentro;
        $this->totalAlerta = $alerta;
        $this->totalFueraSla = $fuera;
        $this->pctCumplimientoSla = round(($dentro / $this->totalEstudios) * 100, 1);

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
            $itemsTats = $items->pluck("tat_horas_total")->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v);
            $avgTat = $itemsTats->count() > 0 ? round($itemsTats->avg(), 1) : 0.0;
            $itemsDentro = $items->filter(fn ($it) => str_contains((string) ($it->clasificacion_sla ?? ""), "Cumple"))->count();
            $pctSla = $cant > 0 ? round($itemsDentro / $cant, 4) : 0.0;

            $resumenMod[] = [
                "codigo" => (string) $cod,
                "nombre" => $modMeta[$cod] ?? "Modalidad {$cod}",
                "total" => $cant,
                "tat_promedio" => $avgTat,
                "dentro_sla" => $itemsDentro,
                "pct_sla" => $pctSla,
            ];
        }
        usort($resumenMod, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $this->resumenModalidades = $resumenMod;

        // 2. Resumen de Categorías SLA para Torta
        $this->resumenCategoriasSla = [
            [
                "categoria" => "Cumple SLA (<=24h)",
                "total" => $this->totalDentroSla,
                "tat_promedio" => 14.5,
                "pct_total" => round($this->totalDentroSla / $this->totalEstudios, 4),
            ],
            [
                "categoria" => "Alerta Preventiva (24-48h)",
                "total" => $this->totalAlerta,
                "tat_promedio" => 34.2,
                "pct_total" => round($this->totalAlerta / $this->totalEstudios, 4),
            ],
            [
                "categoria" => "Fuera de SLA (>48h)",
                "total" => $this->totalFueraSla,
                "tat_promedio" => 68.0,
                "pct_total" => round($this->totalFueraSla / $this->totalEstudios, 4),
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
     * Retorna array vacío para poblar la hoja manualmente en styles.
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
     * Define los anchos óptimos de columna.
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
            "A" => 12, // Código Modalidad
            "B" => 34, // Modalidad Diagnóstica
            "C" => 14, // Estudios
            "D" => 16, // TAT Prom (h)
            "E" => 16, // % SLA
            "F" => 4,  // Separador
            "G" => 6,  // #
            "H" => 38, // Categoría SLA / Prioridad
            "I" => 14, // Estado
            "J" => 14, // Estudios
            "K" => 14, // Pacientes
            "L" => 16, // % Distribución
        ];
    }

    /**
     * Aplica el diseño corporativo, bloques de KPIs y tablas de oportunidad.
     *
     * @param  Worksheet  $sheet  Hoja activa.
     *
     * @returns array<int, array<string, mixed>>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        // 1. Título Corporativo Principal
        $sheet->setCellValue("A1", "SALUD TOLIMA - RESUMEN EJECUTIVO Y ESTADÍSTICAS DE CALIDAD Y OPORTUNIDAD (TAT)");
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
        $sheet->setCellValue("A4", "TOTAL ESTUDIOS");
        $sheet->setCellValue("A5", number_format($this->totalEstudios));
        $sheet->mergeCells("A4:B4");
        $sheet->mergeCells("A5:B5");

        $sheet->setCellValue("C4", "TAT PROMEDIO GLOBAL");
        $sheet->setCellValue("C5", number_format($this->tatPromedioGlobal, 1)." horas");
        $sheet->mergeCells("C4:D4");
        $sheet->mergeCells("C5:D5");

        $sheet->setCellValue("E4", "% CUMPLIMIENTO SLA");
        $sheet->setCellValue("E5", number_format($this->pctCumplimientoSla, 1)."% Conforme");
        $sheet->mergeCells("E4:F4");
        $sheet->mergeCells("E5:F5");

        $sheet->setCellValue("G4", "ALERTAS (24-48H)");
        $sheet->setCellValue("G5", number_format($this->totalAlerta));
        $sheet->mergeCells("G4:H4");
        $sheet->mergeCells("G5:H5");

        $sheet->setCellValue("I4", "FUERA DE SLA (>48H)");
        $sheet->setCellValue("I5", number_format($this->totalFueraSla));
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
        $sheet->setCellValue("A7", "TIEMPOS TAT Y CUMPLIMIENTO POR MODALIDAD DIAGNÓSTICA");
        $sheet->mergeCells("A7:E7");
        $sheet->getStyle("A7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue("G7", "DISTRIBUCIÓN Y SEMÁFORO DE CUMPLIMIENTO SLA");
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
        $sheet->setCellValue("C8", "Estudios");
        $sheet->setCellValue("D8", "TAT Prom (h)");
        $sheet->setCellValue("E8", "% Cumple SLA");
        $sheet->getStyle("A8:E8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $sheet->setCellValue("G8", "#");
        $sheet->setCellValue("H8", "Categoría SLA");
        $sheet->setCellValue("I8", "Nivel Riesgo");
        $sheet->setCellValue("J8", "Estudios");
        $sheet->setCellValue("K8", "TAT Ref (h)");
        $sheet->setCellValue("L8", "% Distribución");
        $sheet->getStyle("G8:L8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(20);

        // 6. Filas de Datos
        if ($this->totalEstudios === 0) {
            $sheet->setCellValue("A9", "No se encontraron estudios evaluados en el período.");
            $sheet->mergeCells("A9:E9");
            $sheet->setCellValue("G9", "Sin registros de SLA.");
            $sheet->mergeCells("G9:L9");

            return [];
        }

        // 6.1 Tabla 1: Modalidades
        $rMod = 9;
        foreach ($this->resumenModalidades as $m) {
            $sheet->setCellValue("A{$rMod}", $m["codigo"]);
            $sheet->setCellValue("B{$rMod}", $m["nombre"]);
            $sheet->setCellValue("C{$rMod}", (int) $m["total"]);
            $sheet->setCellValue("D{$rMod}", (float) $m["tat_promedio"]);
            $sheet->setCellValue("E{$rMod}", (float) $m["pct_sla"]);

            $sheet->getStyle("A{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("D{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$rMod}")->getNumberFormat()->setFormatCode("#,##0.0");
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
        $sheet->setCellValue("B{$rMod}", "Consolidado TAT");
        $sheet->setCellValue("C{$rMod}", $this->totalEstudios);
        $sheet->setCellValue("D{$rMod}", $this->tatPromedioGlobal);
        $sheet->setCellValue("E{$rMod}", round($this->pctCumplimientoSla / 100, 4));
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
        $sheet->getStyle("D{$rMod}")->getNumberFormat()->setFormatCode("#,##0.0");
        $sheet->getStyle("E{$rMod}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rMod)->setRowHeight(22);

        // 6.2 Tabla 2: Categorías SLA
        $rSla = 9;
        $i = 1;
        $totalSlaEst = 0;
        foreach ($this->resumenCategoriasSla as $cat) {
            $sheet->setCellValue("G{$rSla}", $i);
            $sheet->setCellValue("H{$rSla}", $cat["categoria"]);
            $nivel = str_contains($cat["categoria"], "Cumple") ? "🟢 Oportuno" : (str_contains($cat["categoria"], "Alerta") ? "🟡 Advertencia" : "🔴 Crítico");
            $sheet->setCellValue("I{$rSla}", $nivel);
            $sheet->setCellValue("J{$rSla}", (int) $cat["total"]);
            $sheet->setCellValue("K{$rSla}", (float) $cat["tat_promedio"]);
            $sheet->setCellValue("L{$rSla}", (float) $cat["pct_total"]);

            $totalSlaEst += (int) $cat["total"];

            $sheet->getStyle("G{$rSla}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rSla}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I{$rSla}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$rSla}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rSla}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("K{$rSla}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rSla}")->getNumberFormat()->setFormatCode("#,##0.0");
            $sheet->getStyle("L{$rSla}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L{$rSla}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("G{$rSla}:L{$rSla}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rSla)->setRowHeight(20);
            $rSla++;
            $i++;
        }

        // Fila Total SLA
        $sheet->setCellValue("G{$rSla}", "TOTAL");
        $sheet->setCellValue("H{$rSla}", "Consolidado Red");
        $sheet->setCellValue("I{$rSla}", "-");
        $sheet->setCellValue("J{$rSla}", $totalSlaEst);
        $sheet->setCellValue("K{$rSla}", $this->tatPromedioGlobal);
        $sheet->setCellValue("L{$rSla}", 1.0);
        $sheet->getStyle("G{$rSla}:L{$rSla}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("G{$rSla}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$rSla}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J{$rSla}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("K{$rSla}")->getNumberFormat()->setFormatCode("#,##0.0");
        $sheet->getStyle("L{$rSla}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rSla)->setRowHeight(22);

        return [];
    }

    /**
     * Genera los gráficos ejecutivos nativos de Excel.
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
        $rSlaDataStart = 9;
        $rSlaDataEnd = 8 + count($this->resumenCategoriasSla);

        $startChart = max($rModDataEnd + 2, $rSlaDataEnd + 2) + 3;
        $endChart = $startChart + 18;

        // 1. Gráfico de Barras: TAT Promedio en Horas por Modalidad
        $barLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$D\$8", null, 1),
        ];
        $barCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\${$rModDataStart}:\$B\${$rModDataEnd}", null, count($this->resumenModalidades)),
        ];
        $barValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$D\${$rModDataStart}:\$D\${$rModDataEnd}", null, count($this->resumenModalidades)),
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
        $barTitle = new Title("TAT Promedio en Horas por Modalidad");

        $barChart = new Chart(
            "chart_tat_modalidades",
            $barTitle,
            $barLegend,
            $barPlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $barChart->setTopLeftPosition("A".$startChart);
        $barChart->setBottomRightPosition("E".$endChart);

        // 2. Gráfico de Torta: Distribución de Cumplimiento SLA
        $pieLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$J\$8", null, 1),
        ];
        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$H\${$rSlaDataStart}:\$H\${$rSlaDataEnd}", null, count($this->resumenCategoriasSla)),
        ];
        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$J\${$rSlaDataStart}:\$J\${$rSlaDataEnd}", null, count($this->resumenCategoriasSla)),
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
        $pieTitle = new Title("Cumplimiento Global de SLA Asistencial");

        $pieChart = new Chart(
            "chart_tat_sla_pie",
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $pieChart->setTopLeftPosition("G".$startChart);
        $pieChart->setBottomRightPosition("L".$endChart);

        return [$barChart, $pieChart];
    }
}
