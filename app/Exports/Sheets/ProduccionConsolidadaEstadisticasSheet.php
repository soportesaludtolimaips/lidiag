<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
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
 * Hoja de Estadísticas y Gráficas del Consolidado de Producción: Estudios Tomados (PACS) vs Asignados (LIDiag).
 * Distingue la distribución de asignación entre Salud Tolima y Tele-Radiología.
 *
 * Flujo del proceso:
 * 1. Procesa y calcula los KPIs globales (Tomados, Salud Tolima, Tele-Radiología, Sin Asignar, Cobertura %, Leídos, Tiempos).
 * 2. Construye tablas ejecutivas emparejadas: Comparativa por Sede (A:G) y por Modalidad (I:O).
 * 3. Renderiza 4 gráficos nativos interactivos de PhpSpreadsheet.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-09
 */
class ProduccionConsolidadaEstadisticasSheet implements ShouldAutoSize, WithCharts, WithStyles, WithTitle
{
    protected Collection $estudios;

    protected string $fechaInicio;

    protected string $fechaFin;

    protected ?string $sedeNombre;

    // Métricas calculadas
    protected int $kpiTotalTomados = 0;

    protected int $kpiTotalAsignados = 0;

    protected int $kpiTotalSaludTolima = 0;

    protected int $kpiTotalTeleRadiologia = 0;

    protected int $kpiTotalSinAsignar = 0;

    protected float $kpiPorcentajeAsignacion = 0.0;

    protected int $kpiTotalLeidos = 0;

    protected float $kpiTiempoPromedioHoras = 0.0;

    /** @var array<int, array<string, mixed>> */
    protected array $resumenSedes = [];

    /** @var array<int, array<string, mixed>> */
    protected array $resumenModalidades = [];

    protected int $filaFinSedes = 8;

    protected int $filaFinModalidades = 8;

    /**
     * Constructor de la hoja estadística.
     *
     * @param  Collection  $estudios  Colección de estudios tomados y asignados.
     * @param  string  $fechaInicio  Fecha de inicio del rango.
     * @param  string  $fechaFin  Fecha de fin del rango.
     * @param  string|null  $sedeNombre  Nombre descriptivo de la sede o red.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-09
     */
    public function __construct(Collection $estudios, string $fechaInicio, string $fechaFin, ?string $sedeNombre = null)
    {
        $this->estudios = $estudios;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeNombre = $sedeNombre ?: "Todas las Sedes de la Red";

        $this->calcularEstadisticas();
    }

    /**
     * Título de la hoja.
     *
     * @returns string
     */
    public function title(): string
    {
        return "Estadísticas y Gráficas";
    }

    /**
     * Procesa la colección y genera métricas analíticas discriminando Salud Tolima vs Tele-Radiología.
     */
    protected function calcularEstadisticas(): void
    {
        $this->kpiTotalTomados = $this->estudios->count();

        $asignados = $this->estudios->filter(fn ($e) => ! empty($e->estudio_id));
        $this->kpiTotalAsignados = $asignados->count();
        $this->kpiTotalSinAsignar = $this->kpiTotalTomados - $this->kpiTotalAsignados;

        $this->kpiTotalSaludTolima = $asignados->filter(fn ($e) => ($e->destino_estudio ?? "") === "Salud Tolima")->count();
        $this->kpiTotalTeleRadiologia = $asignados->filter(fn ($e) => ($e->destino_estudio ?? "") === "Tele-Radiología")->count();

        $this->kpiPorcentajeAsignacion = $this->kpiTotalTomados > 0
            ? ($this->kpiTotalAsignados / $this->kpiTotalTomados)
            : 0.0;

        $leidos = $asignados->filter(fn ($e) => ! empty($e->fechor_lectura) || ! empty($e->con_lectura));
        $this->kpiTotalLeidos = $leidos->count();

        // Tiempo promedio entre toma y asignación (en horas)
        $tiempos = [];
        foreach ($asignados as $e) {
            if (! empty($e->study_datetime) && ! empty($e->fecha_asignacion)) {
                $toma = Carbon::parse($e->study_datetime);
                $asig = Carbon::parse($e->fecha_asignacion);
                $diff = round(abs($asig->getTimestamp() - $toma->getTimestamp()) / 3600, 1);
                if ($diff >= 0 && $diff < 720) {
                    $tiempos[] = $diff;
                }
            }
        }
        $this->kpiTiempoPromedioHoras = count($tiempos) > 0 ? round(array_sum($tiempos) / count($tiempos), 1) : 0.0;

        // Agrupación por Sede
        $sedesGroup = $this->estudios->groupBy(fn ($e) => $e->nom_sede ?? "Sede Desconocida");
        $resSedes = [];
        $i = 1;
        foreach ($sedesGroup as $nomSede => $items) {
            $tom = $items->count();
            $asigItems = $items->filter(fn ($e) => ! empty($e->estudio_id));
            $asig = $asigItems->count();
            $st = $asigItems->filter(fn ($e) => ($e->destino_estudio ?? "") === "Salud Tolima")->count();
            $tele = $asigItems->filter(fn ($e) => ($e->destino_estudio ?? "") === "Tele-Radiología")->count();
            $sinAsig = $tom - $asig;
            $pct = $tom > 0 ? ($asig / $tom) : 0.0;
            $lei = $items->filter(fn ($e) => ! empty($e->fechor_lectura) || ! empty($e->con_lectura))->count();

            $resSedes[] = [
                "ranking" => $i++,
                "sede" => $nomSede,
                "tomados" => $tom,
                "salud_tolima" => $st,
                "tele_radiologia" => $tele,
                "asignados" => $asig,
                "sin_asignar" => $sinAsig,
                "pct_asignacion" => $pct,
                "leidos" => $lei,
            ];
        }
        usort($resSedes, fn ($a, $b) => $b["tomados"] <=> $a["tomados"]);
        foreach ($resSedes as $k => &$item) {
            $item["ranking"] = $k + 1;
        }
        $this->resumenSedes = $resSedes;

        // Agrupación por Modalidad
        $modGroup = $this->estudios->groupBy(fn ($e) => trim((string) ($e->mods_in_study ?? "ND")));
        $resMod = [];
        $j = 1;
        foreach ($modGroup as $mod => $items) {
            $tom = $items->count();
            $asigItems = $items->filter(fn ($e) => ! empty($e->estudio_id));
            $asig = $asigItems->count();
            $st = $asigItems->filter(fn ($e) => ($e->destino_estudio ?? "") === "Salud Tolima")->count();
            $tele = $asigItems->filter(fn ($e) => ($e->destino_estudio ?? "") === "Tele-Radiología")->count();
            $sinAsig = $tom - $asig;
            $pct = $tom > 0 ? ($asig / $tom) : 0.0;

            $resMod[] = [
                "ranking" => $j++,
                "modalidad" => $mod ?: "ND",
                "tomados" => $tom,
                "salud_tolima" => $st,
                "tele_radiologia" => $tele,
                "asignados" => $asig,
                "sin_asignar" => $sinAsig,
                "pct_asignacion" => $pct,
            ];
        }
        usort($resMod, fn ($a, $b) => $b["tomados"] <=> $a["tomados"]);
        foreach ($resMod as $k => &$item) {
            $item["ranking"] = $k + 1;
        }
        $this->resumenModalidades = $resMod;
    }

    /**
     * Aplica estilos, genera tarjetas y tablas.
     *
     * @returns array<mixed, mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->setShowGridlines(true);

        // Fila 1: Título institucional
        $sheet->mergeCells("A1:O1");
        $sheet->setCellValue("A1", "CONSOLIDADO DE PRODUCCIÓN: ESTUDIOS TOMADOS (PACS) VS ASIGNADOS (LIDIAG)");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "size" => 13, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Fila 2: Subtítulo
        $sheet->mergeCells("A2:O2");
        $sheet->setCellValue(
            "A2",
            "Período de Toma: {$this->fechaInicio} al {$this->fechaFin}  |  Ámbito: {$this->sedeNombre}  |  Generado: ".now()->format("Y-m-d H:i:s")
        );
        $sheet->getStyle("A2")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "283593"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // Filas 4-5: Tarjetas KPI
        $this->construirTarjetasKPI($sheet);

        // Filas 7+: Tablas emparejadas (Sedes A:G, Modalidades I:O)
        $this->construirTablasComparativas($sheet);

        return [];
    }

    /**
     * Construye las tarjetas KPI ejecutivas discriminando Salud Tolima y Tele-Radiología.
     */
    protected function construirTarjetasKPI(Worksheet $sheet): void
    {
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(26);

        $kpis = [
            ["range_title" => "A4:B4", "range_val" => "A5:B5", "title" => "TOTAL TOMADOS (PACS)", "val" => $this->kpiTotalTomados, "format" => "#,##0", "color" => "1A2274"],
            ["range_title" => "C4:D4", "range_val" => "C5:D5", "title" => "ASIG. SALUD TOLIMA", "val" => $this->kpiTotalSaludTolima, "format" => "#,##0", "color" => "1565C0"],
            ["range_title" => "E4:F4", "range_val" => "E5:F5", "title" => "ASIG. TELE-RADIOLOGÍA", "val" => $this->kpiTotalTeleRadiologia, "format" => "#,##0", "color" => "6A1B9A"],
            ["range_title" => "G4:H4", "range_val" => "G5:H5", "title" => "BRECHA: SIN ASIGNAR", "val" => $this->kpiTotalSinAsignar, "format" => "#,##0", "color" => "C62828"],
            ["range_title" => "I4:J4", "range_val" => "I5:J5", "title" => "% COBERTURA ASIGNACIÓN", "val" => $this->kpiPorcentajeAsignacion, "format" => "0.0%", "color" => "0277BD"],
            ["range_title" => "K4:L4", "range_val" => "K5:L5", "title" => "ESTUDIOS LEÍDOS", "val" => $this->kpiTotalLeidos, "format" => "#,##0", "color" => "2E7D32"],
            ["range_title" => "M4:N4", "range_val" => "M5:N5", "title" => "TIEMPO PROM. ASIG (HRS)", "val" => $this->kpiTiempoPromedioHoras, "format" => "0.0", "color" => "EF6C00"],
        ];

        foreach ($kpis as $kpi) {
            $sheet->mergeCells($kpi["range_title"]);
            $sheet->setCellValue(substr($kpi["range_title"], 0, 2), $kpi["title"]);
            $sheet->getStyle($kpi["range_title"])->applyFromArray([
                "font" => ["bold" => true, "size" => 8, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
                "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => $kpi["color"]]],
                "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            ]);

            $sheet->mergeCells($kpi["range_val"]);
            $sheet->setCellValue(substr($kpi["range_val"], 0, 2), $kpi["val"]);
            $sheet->getStyle($kpi["range_val"])->applyFromArray([
                "font" => ["bold" => true, "size" => 14, "color" => ["rgb" => $kpi["color"]], "name" => "Segoe UI"],
                "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
                "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
                "borders" => [
                    "outline" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => $kpi["color"]]],
                ],
            ]);
            $sheet->getStyle(substr($kpi["range_val"], 0, 2))->getNumberFormat()->setFormatCode($kpi["format"]);
        }
    }

    /**
     * Construye las tablas comparativas de Sedes y Modalidades.
     */
    protected function construirTablasComparativas(Worksheet $sheet): void
    {
        // Fila 7: Banners de sección
        $sheet->mergeCells("A7:G7");
        $sheet->setCellValue("A7", "COMPARATIVA DE PRODUCCIÓN POR SEDE HOSPITALARIA");
        $sheet->getStyle("A7:G7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells("I7:O7");
        $sheet->setCellValue("I7", "COMPARATIVA DE PRODUCCIÓN POR MODALIDAD DIAGNÓSTICA");
        $sheet->getStyle("I7:O7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(24);

        // Fila 8: Encabezados de columnas
        $headersSedes = [
            "A8" => "#",
            "B8" => "Sede Hospitalaria",
            "C8" => "Tomados PACS",
            "D8" => "Salud Tolima",
            "E8" => "Tele-Radiología",
            "F8" => "Sin Asignar",
            "G8" => "% Asignación",
        ];
        foreach ($headersSedes as $cell => $txt) {
            $sheet->setCellValue($cell, $txt);
        }
        $sheet->getStyle("A8:G8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "283593"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $headersMod = [
            "I8" => "#",
            "J8" => "Modalidad Diagnóstica",
            "K8" => "Tomados PACS",
            "L8" => "Salud Tolima",
            "M8" => "Tele-Radiología",
            "N8" => "Sin Asignar",
            "O8" => "% Asignación",
        ];
        foreach ($headersMod as $cell => $txt) {
            $sheet->setCellValue($cell, $txt);
        }
        $sheet->getStyle("I8:O8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "283593"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(22);

        // Llenar datos de Sedes (Filas 9+)
        $rSede = 9;
        $sumSedeTom = 0;
        $sumSedeST = 0;
        $sumSedeTele = 0;
        $sumSedeSin = 0;
        foreach ($this->resumenSedes as $s) {
            $sheet->setCellValue("A{$rSede}", $s["ranking"]);
            $sheet->setCellValue("B{$rSede}", $s["sede"]);
            $sheet->setCellValue("C{$rSede}", (int) $s["tomados"]);
            $sheet->setCellValue("D{$rSede}", (int) $s["salud_tolima"]);
            $sheet->setCellValue("E{$rSede}", (int) $s["tele_radiologia"]);
            $sheet->setCellValue("F{$rSede}", (int) $s["sin_asignar"]);
            $sheet->setCellValue("G{$rSede}", (float) $s["pct_asignacion"]);

            $sumSedeTom += (int) $s["tomados"];
            $sumSedeST += (int) $s["salud_tolima"];
            $sumSedeTele += (int) $s["tele_radiologia"];
            $sumSedeSin += (int) $s["sin_asignar"];

            $sheet->getStyle("A{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rSede}:F{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rSede}:F{$rSede}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("G{$rSede}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("G{$rSede}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("A{$rSede}:G{$rSede}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]]],
            ]);
            $sheet->getRowDimension($rSede)->setRowHeight(20);
            $rSede++;
        }

        // Fila Total Sedes
        $sheet->setCellValue("A{$rSede}", "TOTAL");
        $sheet->mergeCells("A{$rSede}:B{$rSede}");
        $sheet->setCellValue("C{$rSede}", $sumSedeTom);
        $sheet->setCellValue("D{$rSede}", $sumSedeST);
        $sheet->setCellValue("E{$rSede}", $sumSedeTele);
        $sheet->setCellValue("F{$rSede}", $sumSedeSin);
        $sheet->setCellValue("G{$rSede}", $sumSedeTom > 0 ? (($sumSedeST + $sumSedeTele) / $sumSedeTom) : 0.0);
        $sheet->getStyle("A{$rSede}:G{$rSede}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "E8EAF6"]],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "283593"]]],
        ]);
        $sheet->getStyle("C{$rSede}:F{$rSede}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("G{$rSede}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rSede)->setRowHeight(22);
        $this->filaFinSedes = $rSede - 1;

        // Llenar datos de Modalidades (Filas 9+)
        $rMod = 9;
        $sumModTom = 0;
        $sumModST = 0;
        $sumModTele = 0;
        $sumModSin = 0;
        foreach ($this->resumenModalidades as $m) {
            $sheet->setCellValue("I{$rMod}", $m["ranking"]);
            $sheet->setCellValue("J{$rMod}", $m["modalidad"]);
            $sheet->setCellValue("K{$rMod}", (int) $m["tomados"]);
            $sheet->setCellValue("L{$rMod}", (int) $m["salud_tolima"]);
            $sheet->setCellValue("M{$rMod}", (int) $m["tele_radiologia"]);
            $sheet->setCellValue("N{$rMod}", (int) $m["sin_asignar"]);
            $sheet->setCellValue("O{$rMod}", (float) $m["pct_asignacion"]);

            $sumModTom += (int) $m["tomados"];
            $sumModST += (int) $m["salud_tolima"];
            $sumModTele += (int) $m["tele_radiologia"];
            $sumModSin += (int) $m["sin_asignar"];

            $sheet->getStyle("I{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$rMod}:N{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rMod}:N{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("O{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("O{$rMod}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("I{$rMod}:O{$rMod}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]]],
            ]);
            $sheet->getRowDimension($rMod)->setRowHeight(20);
            $rMod++;
        }

        // Fila Total Modalidades
        $sheet->setCellValue("I{$rMod}", "TOTAL");
        $sheet->mergeCells("I{$rMod}:J{$rMod}");
        $sheet->setCellValue("K{$rMod}", $sumModTom);
        $sheet->setCellValue("L{$rMod}", $sumModST);
        $sheet->setCellValue("M{$rMod}", $sumModTele);
        $sheet->setCellValue("N{$rMod}", $sumModSin);
        $sheet->setCellValue("O{$rMod}", $sumModTom > 0 ? (($sumModST + $sumModTele) / $sumModTom) : 0.0);
        $sheet->getStyle("I{$rMod}:O{$rMod}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "E8EAF6"]],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "283593"]]],
        ]);
        $sheet->getStyle("K{$rMod}:N{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("O{$rMod}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rMod)->setRowHeight(22);
        $this->filaFinModalidades = $rMod - 1;
    }

    /**
     * Construye y retorna los 4 gráficos nativos.
     *
     * @returns array<int, Chart>
     */
    public function charts(): array
    {
        $charts = [];
        $titleSheet = "'".$this->title()."'";

        // Gráfico 1: Comparativa Tomados vs Salud Tolima vs Tele-Radiología por Sede (A25:G43)
        if (count($this->resumenSedes) > 0) {
            $catSedes = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$titleSheet}!\$B\$9:\$B\${$this->filaFinSedes}", null, count($this->resumenSedes))];
            $valTomados = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$titleSheet}!\$C\$9:\$C\${$this->filaFinSedes}", null, count($this->resumenSedes));
            $valST = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$titleSheet}!\$D\$9:\$D\${$this->filaFinSedes}", null, count($this->resumenSedes));
            $valTele = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$titleSheet}!\$E\$9:\$E\${$this->filaFinSedes}", null, count($this->resumenSedes));

            $seriesSedes = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                [0, 1, 2],
                [
                    new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$titleSheet}!\$C\$8", null, 1),
                    new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$titleSheet}!\$D\$8", null, 1),
                    new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$titleSheet}!\$E\$8", null, 1),
                ],
                $catSedes,
                [$valTomados, $valST, $valTele]
            );
            $seriesSedes->setPlotDirection(DataSeries::DIRECTION_COL);

            $plotSedes = new PlotArea(null, [$seriesSedes]);
            $legendSedes = new Legend(Legend::POSITION_BOTTOM, null, false);
            $chartSedes = new Chart(
                "chart_sedes",
                new Title("Tomados PACS vs Salud Tolima vs Tele-Radiología por Sede"),
                $legendSedes,
                $plotSedes
            );
            $chartSedes->setTopLeftPosition("A25");
            $chartSedes->setBottomRightPosition("G43");
            $charts[] = $chartSedes;
        }

        // Gráfico 2: Comparativa Tomados vs Salud Tolima vs Tele-Radiología por Modalidad (I25:O43)
        if (count($this->resumenModalidades) > 0) {
            $catMods = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$titleSheet}!\$J\$9:\$J\${$this->filaFinModalidades}", null, count($this->resumenModalidades))];
            $valModTom = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$titleSheet}!\$K\$9:\$K\${$this->filaFinModalidades}", null, count($this->resumenModalidades));
            $valModST = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$titleSheet}!\$L\$9:\$L\${$this->filaFinModalidades}", null, count($this->resumenModalidades));
            $valModTele = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$titleSheet}!\$M\$9:\$M\${$this->filaFinModalidades}", null, count($this->resumenModalidades));

            $seriesMods = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                [0, 1, 2],
                [
                    new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$titleSheet}!\$K\$8", null, 1),
                    new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$titleSheet}!\$L\$8", null, 1),
                    new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$titleSheet}!\$M\$8", null, 1),
                ],
                $catMods,
                [$valModTom, $valModST, $valModTele]
            );
            $seriesMods->setPlotDirection(DataSeries::DIRECTION_COL);

            $plotMods = new PlotArea(null, [$seriesMods]);
            $legendMods = new Legend(Legend::POSITION_BOTTOM, null, false);
            $chartMods = new Chart(
                "chart_modalidades",
                new Title("Tomados PACS vs Salud Tolima vs Tele-Radiología por Modalidad"),
                $legendMods,
                $plotMods
            );
            $chartMods->setTopLeftPosition("I25");
            $chartMods->setBottomRightPosition("O43");
            $charts[] = $chartMods;
        }

        // Gráfico 3: Cobertura y Destino Global (A46:G64)
        if ($this->kpiTotalTomados > 0) {
            $catTorta = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$titleSheet}!\$D\$8:\$F\$8", null, 3)];
            $valTorta = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$titleSheet}!\$C\$5:\$G\$5", null, 3)];

            $seriesTorta = new DataSeries(
                DataSeries::TYPE_PIECHART,
                null,
                range(0, count($valTorta) - 1),
                [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 1)],
                $catTorta,
                $valTorta
            );
            $plotTorta = new PlotArea(null, [$seriesTorta]);
            $legendTorta = new Legend(Legend::POSITION_RIGHT, null, false);
            $chartTorta = new Chart(
                "chart_cobertura",
                new Title("Proporción Global: Salud Tolima vs Tele-Radiología vs Sin Asignar"),
                $legendTorta,
                $plotTorta
            );
            $chartTorta->setTopLeftPosition("A46");
            $chartTorta->setBottomRightPosition("G64");
            $charts[] = $chartTorta;
        }

        return $charts;
    }
}
