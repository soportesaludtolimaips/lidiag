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
 * Hoja de Estadísticas Ejecutivas, Monitoreo de Backups y Gráficos Nativos para Agentes de Telemetría.
 *
 * Flujo del proceso:
 * 1. Calcula indicadores clave de rendimiento (KPIs): Total sedes, backups al día, agentes online y equipos emisores.
 * 2. Agrupa el estado de recepción de backups (Al día, Advertencia, Crítico, Sin registros).
 * 3. Agrupa la conectividad de los agentes edge de dosimetría (Online, Retrasado, Offline).
 * 4. Consolida el ranking de sedes por dotación de equipos emisores de radiación.
 * 5. Genera gráficos nativos de Torta y Barras para presentación ejecutiva de infraestructura.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteAgentesTelemetriaEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalSedes = 0;

    protected int $backupsAlDia = 0;

    protected int $backupsCriticos = 0;

    protected int $dosisOnline = 0;

    protected int $totalEquipos = 0;

    /**
     * @var array<int, array{estado: string, total: int, porcentaje: float}>
     */
    protected array $resumenBackups = [];

    /**
     * @var array<int, array{estado: string, total: int, porcentaje: float}>
     */
    protected array $resumenDosis = [];

    /**
     * @var array<int, array{ranking: int, sede: string, equipos: int}>
     */
    protected array $rankingSedes = [];

    /**
     * Constructor con inyección de telemetría de sedes.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected Collection $filas
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Calcula métricas y agrupaciones de la telemetría.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function calcularEstadisticas(): void
    {
        $this->totalSedes = $this->filas->count();
        if ($this->totalSedes === 0) {
            return;
        }

        $conteoBk = [];
        $conteoDosi = [];
        $sedesEquipos = [];

        foreach ($this->filas as $item) {
            // Backup
            $bkEstado = (string) ($item->backup_estado ?? "Sin registros");
            if (str_contains(strtolower($bkEstado), "al día") || str_contains(strtolower($bkEstado), "<= 24h")) {
                $bkCat = "Al día (<= 24h)";
                $this->backupsAlDia++;
            } elseif (str_contains(strtolower($bkEstado), "advertencia")) {
                $bkCat = "Advertencia (24h-48h)";
            } elseif (str_contains(strtolower($bkEstado), "crítico") || str_contains(strtolower($bkEstado), "> 48h")) {
                $bkCat = "Crítico (> 48h)";
                $this->backupsCriticos++;
            } else {
                $bkCat = "Sin registros / Offline";
            }
            $conteoBk[$bkCat] = ($conteoBk[$bkCat] ?? 0) + 1;

            // Dosimetría
            $dosiEst = (string) ($item->dosimetria_estado ?? "Offline");
            if (str_contains(strtolower($dosiEst), "online") || str_contains(strtolower($dosiEst), "activo")) {
                $dosiCat = "Online (Activo)";
                $this->dosisOnline++;
            } elseif (str_contains(strtolower($dosiEst), "retrasado")) {
                $dosiCat = "Retrasado (16-30 min)";
            } else {
                $dosiCat = "Offline (> 30 min)";
            }
            $conteoDosi[$dosiCat] = ($conteoDosi[$dosiCat] ?? 0) + 1;

            // Equipos
            $eq = (int) ($item->equipos_emisores ?? 0);
            $this->totalEquipos += $eq;
            $sedesEquipos[(string) ($item->nombre_sede ?? "Sede")] = $eq;
        }

        // Resumen Backups
        foreach ($conteoBk as $est => $cant) {
            $this->resumenBackups[] = [
                "estado" => $est,
                "total" => $cant,
                "porcentaje" => round($cant / $this->totalSedes, 4),
            ];
        }

        // Resumen Dosimetría
        foreach ($conteoDosi as $est => $cant) {
            $this->resumenDosis[] = [
                "estado" => $est,
                "total" => $cant,
                "porcentaje" => round($cant / $this->totalSedes, 4),
            ];
        }

        // Ranking Sedes
        arsort($sedesEquipos);
        $rank = 1;
        foreach (array_slice($sedesEquipos, 0, 10, true) as $sedeNom => $eq) {
            $this->rankingSedes[] = [
                "ranking" => $rank++,
                "sede" => $sedeNom,
                "equipos" => $eq,
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
        return "Estadísticas y Telemetría";
    }

    /**
     * Retorna array vacío para construcción libre con estilos.
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
            "C" => 32,
            "D" => 18,
            "E" => 18,
            "F" => 4,
            "G" => 4,
            "H" => 4,
            "I" => 28,
            "J" => 16,
            "K" => 16,
            "L" => 4,
            "M" => 18,
            "N" => 18,
            "O" => 18,
            "P" => 18,
        ];
    }

    /**
     * Construye y formatea la hoja de estadísticas.
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
        $sheet->setCellValue("B2", "SALUD TOLIMA / LIDIAG — DASHBOARD DE TELEMETRÍA Y SALUD DE AGENTES");
        $sheet->getStyle("B2")->getFont()->setBold(true)->setSize(15)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B2:K2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0F172A");
        $sheet->getStyle("B2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(32);

        $sheet->mergeCells("B3:K3");
        $sheet->setCellValue("B3", "Corte de auditoría de agentes: ".Carbon::now()->format("d/m/Y H:i:s")." | Servidor Central y Red Hospitalaria");
        $sheet->getStyle("B3")->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B3:K3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF334155");
        $sheet->getStyle("B3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Tarjetas KPI (Fila 5 y 6)
        $pctBkAlDia = $this->totalSedes > 0 ? round(($this->backupsAlDia / $this->totalSedes) * 100, 1) : 0;
        $pctDosiOn = $this->totalSedes > 0 ? round(($this->dosisOnline / $this->totalSedes) * 100, 1) : 0;

        $kpis = [
            ["cols" => "B5:C5", "val_col" => "B6:C6", "vcell" => "B6", "titulo" => "SEDES EN RED", "valor" => $this->totalSedes, "bg" => "FF1E293B", "fcolor" => "FFFFFFFF"],
            ["cols" => "D5:E5", "val_col" => "D6:E6", "vcell" => "D6", "titulo" => "BACKUPS AL DÍA (<=24H)", "valor" => "{$this->backupsAlDia} ({$pctBkAlDia}%)", "bg" => "FF065F46", "fcolor" => "FFFFFFFF"],
            ["cols" => "G5:H5", "val_col" => "G6:H6", "vcell" => "G6", "titulo" => "AGENTES DOSIS ONLINE", "valor" => "{$this->dosisOnline} ({$pctDosiOn}%)", "bg" => "FF1E40AF", "fcolor" => "FFFFFFFF"],
            ["cols" => "I5:K5", "val_col" => "I6:K6", "vcell" => "I6", "titulo" => "EQUIPOS EMISORES REGISTRADOS", "valor" => $this->totalEquipos, "bg" => "FF475569", "fcolor" => "FFFFFFFF"],
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
        // TABLA 1: ESTADO DE BACKUPS (Filas 9..)
        // ==========================================
        $sheet->mergeCells("B9:D9");
        $sheet->setCellValue("B9", "ESTADO DE RECEPCIÓN DE BACKUPS DB");
        $sheet->getStyle("B9")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B9:D9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0F172A");
        $sheet->getStyle("B9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("B10", "Estado");
        $sheet->setCellValue("C10", "Sedes");
        $sheet->setCellValue("D10", "% Sedes");
        $sheet->getStyle("B10:D10")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B10:D10")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF334155");
        $sheet->getStyle("C10:D10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowBk = 11;
        foreach ($this->resumenBackups as $item) {
            $sheet->setCellValue("B{$rowBk}", $item["estado"]);
            $sheet->setCellValue("C{$rowBk}", $item["total"]);
            $sheet->setCellValue("D{$rowBk}", $item["porcentaje"]);
            $sheet->getStyle("C{$rowBk}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$rowBk}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$rowBk}")->getNumberFormat()->setFormatCode("0.0%");
            $rowBk++;
        }
        $finBk = max(11, $rowBk - 1);
        $sheet->getStyle("B10:D{$finBk}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFCBD5E1");

        // ==========================================
        // TABLA 2: ESTADO DOSIMETRÍA EDGE (Filas 9..)
        // ==========================================
        $sheet->mergeCells("G9:K9");
        $sheet->setCellValue("G9", "ESTADO DE AGENTES EDGE DOSIMETRÍA (HEARTBEAT)");
        $sheet->getStyle("G9")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("G9:K9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1E3A8A");
        $sheet->getStyle("G9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("G10:H10");
        $sheet->setCellValue("G10", "Estado Agente");
        $sheet->mergeCells("I10:J10");
        $sheet->setCellValue("I10", "Sedes");
        $sheet->setCellValue("K10", "% Sedes");
        $sheet->getStyle("G10:K10")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("G10:K10")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF2563EB");
        $sheet->getStyle("I10:K10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowDosi = 11;
        foreach ($this->resumenDosis as $item) {
            $sheet->mergeCells("G{$rowDosi}:H{$rowDosi}");
            $sheet->mergeCells("I{$rowDosi}:J{$rowDosi}");
            $sheet->setCellValue("G{$rowDosi}", $item["estado"]);
            $sheet->setCellValue("I{$rowDosi}", $item["total"]);
            $sheet->setCellValue("K{$rowDosi}", $item["porcentaje"]);
            $sheet->getStyle("I{$rowDosi}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rowDosi}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rowDosi}")->getNumberFormat()->setFormatCode("0.0%");
            $rowDosi++;
        }
        $finDosi = max(11, $rowDosi - 1);
        $sheet->getStyle("G10:K{$finDosi}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFBFDBFE");

        // ==========================================
        // TABLA 3: TOP SEDES POR EQUIPOS EMISORES (Fila 18..)
        // ==========================================
        $startEq = max($finBk, $finDosi) + 2;
        $sheet->mergeCells("B{$startEq}:E{$startEq}");
        $sheet->setCellValue("B{$startEq}", "TOP SEDES HOSPITALARIAS POR DOTACIÓN DE EQUIPOS EMISORES");
        $sheet->getStyle("B{$startEq}")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B{$startEq}:E{$startEq}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF334155");
        $sheet->getStyle("B{$startEq}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headEq = $startEq + 1;
        $sheet->setCellValue("B{$headEq}", "#");
        $sheet->mergeCells("C{$headEq}:D{$headEq}");
        $sheet->setCellValue("C{$headEq}", "Sede Hospitalaria");
        $sheet->setCellValue("E{$headEq}", "Equipos Registrados");
        $sheet->getStyle("B{$headEq}:E{$headEq}")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("B{$headEq}:E{$headEq}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF475569");
        $sheet->getStyle("B{$headEq}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$headEq}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowEq = $headEq + 1;
        foreach ($this->rankingSedes as $item) {
            $sheet->setCellValue("B{$rowEq}", $item["ranking"]);
            $sheet->mergeCells("C{$rowEq}:D{$rowEq}");
            $sheet->setCellValue("C{$rowEq}", $item["sede"]);
            $sheet->setCellValue("E{$rowEq}", $item["equipos"]);
            $sheet->getStyle("B{$rowEq}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowEq}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rowEq}")->getNumberFormat()->setFormatCode("#,##0");
            $rowEq++;
        }
        $finEq = max($headEq + 1, $rowEq - 1);
        $sheet->getStyle("B{$headEq}:E{$finEq}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFCBD5E1");

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
        if ($this->totalSedes === 0 || empty($this->resumenBackups)) {
            return [];
        }

        $charts = [];
        $finBk = 10 + count($this->resumenBackups);

        // 1. Gráfico de Torta: Distribución de Backups
        $categories1 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Estadísticas y Telemetría'!\$B\$11:\$B\${$finBk}", null, count($this->resumenBackups))];
        $values1 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Estadísticas y Telemetría'!\$C\$11:\$C\${$finBk}", null, count($this->resumenBackups))];

        $series1 = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            range(0, count($values1) - 1),
            [],
            $categories1,
            $values1
        );

        $plotArea1 = new PlotArea(null, [$series1]);
        $legend1 = new Legend(Legend::POSITION_RIGHT, null, false);
        $title1 = new Title("Salud de Backups de Bases de Datos");

        $chart1 = new Chart(
            "chart_backups_telemetria",
            $title1,
            $legend1,
            $plotArea1,
            true,
            DataSeries::EMPTY_AS_GAP,
            null,
            null
        );
        $chart1->setTopLeftPosition("G17");
        $chart1->setBottomRightPosition("K31");
        $charts[] = $chart1;

        // 2. Gráfico de Barras: Dotación de Equipos Emisores
        if (! empty($this->rankingSedes)) {
            $startHead = max(11, 10 + count($this->resumenBackups), 10 + count($this->resumenDosis)) + 3;
            $finRank = $startHead + count($this->rankingSedes) - 1;

            $categories2 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Estadísticas y Telemetría'!\$C\${$startHead}:\$C\${$finRank}", null, count($this->rankingSedes))];
            $values2 = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Estadísticas y Telemetría'!\$E\${$startHead}:\$E\${$finRank}", null, count($this->rankingSedes))];

            $series2 = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                range(0, count($values2) - 1),
                [],
                $categories2,
                $values2
            );
            $series2->setPlotDirection(DataSeries::DIRECTION_COL);

            $plotArea2 = new PlotArea(null, [$series2]);
            $title2 = new Title("Top Sedes por Equipos Emisores");

            $chart2 = new Chart(
                "chart_equipos_sedes",
                $title2,
                null,
                $plotArea2,
                true,
                DataSeries::EMPTY_AS_GAP,
                null,
                null
            );
            $chart2->setTopLeftPosition("B33");
            $chart2->setBottomRightPosition("K47");
            $charts[] = $chart2;
        }

        return $charts;
    }
}
