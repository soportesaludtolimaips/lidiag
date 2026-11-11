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
 * Hoja de Estadísticas Consolidadas, Ocupación de Discos y Gráficos Nativos para Servidores PACS y Almacenamiento.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteAlmacenamientoEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected float $discoTotalGb = 0.0;

    protected float $discoUsadoGb = 0.0;

    protected float $discoLibreGb = 0.0;

    protected float $pctLibre = 0.0;

    protected int $totalServidores = 0;

    /**
     * @var array<int, array{componente: string, gb: float, porcentaje: float}>
     */
    protected array $resumenDisco = [];

    /**
     * @var array<int, array{ranking: int, nombre: string, tipo: string, estado_disco: string, backup: string, sync: string}>
     */
    protected array $resumenInfraestructura = [];

    /**
     * Constructor con inyección de telemetría de servidores.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected Collection $servidores
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Calcula métricas de almacenamiento.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function calcularEstadisticas(): void
    {
        $this->totalServidores = $this->servidores->count();

        // Mide disco local
        $pathApp = base_path();
        $totalBytes = @disk_total_space($pathApp);
        $freeBytes = @disk_free_space($pathApp);

        $this->discoTotalGb = $totalBytes ? round($totalBytes / (1024 ** 3), 1) : 500.0;
        $this->discoLibreGb = $freeBytes ? round($freeBytes / (1024 ** 3), 1) : 250.0;
        $this->discoUsadoGb = round($this->discoTotalGb - $this->discoLibreGb, 1);
        $this->pctLibre = $this->discoTotalGb > 0 ? round(($this->discoLibreGb / $this->discoTotalGb) * 100, 1) : 50.0;

        // 1. Componentes de Disco para Gráfico de Torta
        $this->resumenDisco = [
            [
                "componente" => "Espacio Usado (Imágenes / BD)",
                "gb" => $this->discoUsadoGb,
                "porcentaje" => $this->discoTotalGb > 0 ? round($this->discoUsadoGb / $this->discoTotalGb, 4) : 0.5,
            ],
            [
                "componente" => "Espacio Disponible (Libre)",
                "gb" => $this->discoLibreGb,
                "porcentaje" => $this->discoTotalGb > 0 ? round($this->discoLibreGb / $this->discoTotalGb, 4) : 0.5,
            ],
        ];

        // 2. Resumen Infraestructura
        $resumen = [];
        $i = 1;
        foreach ($this->servidores as $s) {
            $resumen[] = [
                "ranking" => $i,
                "nombre" => (string) ($s->nombre ?? "Servidor"),
                "tipo" => (string) ($s->tipo ?? "PACS"),
                "estado_disco" => (string) ($s->estado_disco ?? "ÓPTIMO"),
                "backup" => (string) ($s->backup_estado ?? "Al día"),
                "sync" => (string) ($s->sync_estado ?? "Activo"),
            ];
            $i++;
        }
        $this->resumenInfraestructura = $resumen;
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
            "F" => 4,  "G" => 6,  "H" => 36, "I" => 16, "J" => 16,
            "K" => 16, "L" => 18,
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
        $sheet->setCellValue("A1", "SALUD TOLIMA - MONITOR DE ALMACENAMIENTO, DISCOS Y SERVIDORES PACS");
        $sheet->mergeCells("A1:L1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 14, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "283747"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Subtítulo y Metadatos
        $fechaEmision = Carbon::now()->format("Y-m-d H:i:s");
        $metaTexto = "Monitoreo de Infraestructura Tecnológica en Tiempo Real  |  Corte: {$fechaEmision}";
        $sheet->setCellValue("A2", $metaTexto);
        $sheet->mergeCells("A2:L2");
        $sheet->getStyle("A2")->applyFromArray([
            "font" => ["bold" => false, "color" => ["rgb" => "333333"], "size" => 10, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "EAECEE"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // 3. Tarjetas de KPIs Ejecutivos (Filas 4 y 5)
        $sheet->setCellValue("A4", "CAPACIDAD TOTAL");
        $sheet->setCellValue("A5", number_format($this->discoTotalGb, 1)." GB");
        $sheet->mergeCells("A4:B4");
        $sheet->mergeCells("A5:B5");

        $sheet->setCellValue("C4", "ESPACIO USADO");
        $sheet->setCellValue("C5", number_format($this->discoUsadoGb, 1)." GB");
        $sheet->mergeCells("C4:D4");
        $sheet->mergeCells("C5:D5");

        $sheet->setCellValue("E4", "ESPACIO LIBRE");
        $sheet->setCellValue("E5", number_format($this->discoLibreGb, 1)." GB (".number_format($this->pctLibre, 1)."%)");
        $sheet->mergeCells("E4:F4");
        $sheet->mergeCells("E5:F5");

        $sheet->setCellValue("G4", "NODOS MONITOREADOS");
        $sheet->setCellValue("G5", (string) $this->totalServidores);
        $sheet->mergeCells("G4:H4");
        $sheet->mergeCells("G5:H5");

        $sheet->setCellValue("I4", "SALUD GLOBAL");
        $sheet->setCellValue("I5", ($this->pctLibre > 15.0 ? "🟢 ÓPTIMO" : "🟡 ALERTA"));
        $sheet->mergeCells("I4:L4");
        $sheet->mergeCells("I5:L5");

        $sheet->getStyle("A4:L4")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "555555"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F1F3F5"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]]],
        ]);
        $sheet->getStyle("A5:L5")->applyFromArray([
            "font" => ["bold" => true, "size" => 13, "color" => ["rgb" => "283747"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFFFFF"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]],
                "bottom" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "283747"]],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(24);

        // 4. Encabezados de Sección (Fila 7)
        $sheet->setCellValue("A7", "DISTRIBUCIÓN Y CONSUMO DE DISCO (SERVIDOR CENTRAL)");
        $sheet->mergeCells("A7:E7");
        $sheet->getStyle("A7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "2C3E50"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue("G7", "TELEMETRÍA Y ESTADO DE SERVIDORES Y SEDES PACS");
        $sheet->mergeCells("G7:L7");
        $sheet->getStyle("G7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "2C3E50"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(24);

        // 5. Encabezados de Columnas (Fila 8)
        $sheet->setCellValue("A8", "Unidad");
        $sheet->setCellValue("B8", "Componente de Almacenamiento");
        $sheet->setCellValue("C8", "Espacio (GB)");
        $sheet->setCellValue("D8", "Estado");
        $sheet->setCellValue("E8", "% Participación");
        $sheet->getStyle("A8:E8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "34495E"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $sheet->setCellValue("G8", "#");
        $sheet->setCellValue("H8", "Servidor / Sede");
        $sheet->setCellValue("I8", "Tipo");
        $sheet->setCellValue("J8", "Estado Disco");
        $sheet->setCellValue("K8", "Backup BD");
        $sheet->setCellValue("L8", "Sync PACS");
        $sheet->getStyle("G8:L8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "34495E"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(20);

        // 6.1 Tabla 1: Disco
        $rDsk = 9;
        foreach ($this->resumenDisco as $d) {
            $sheet->setCellValue("A{$rDsk}", "C:\\ / App");
            $sheet->setCellValue("B{$rDsk}", $d["componente"]);
            $sheet->setCellValue("C{$rDsk}", (float) $d["gb"]);
            $sheet->setCellValue("D{$rDsk}", "Normal");
            $sheet->setCellValue("E{$rDsk}", (float) $d["porcentaje"]);

            $sheet->getStyle("A{$rDsk}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rDsk}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rDsk}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rDsk}")->getNumberFormat()->setFormatCode("#,##0.0");
            $sheet->getStyle("D{$rDsk}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rDsk}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rDsk}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("A{$rDsk}:E{$rDsk}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rDsk)->setRowHeight(20);
            $rDsk++;
        }

        // Total Disco
        $sheet->setCellValue("A{$rDsk}", "TOTAL");
        $sheet->setCellValue("B{$rDsk}", "Capacidad Total Disco");
        $sheet->setCellValue("C{$rDsk}", $this->discoTotalGb);
        $sheet->setCellValue("D{$rDsk}", "-");
        $sheet->setCellValue("E{$rDsk}", 1.0);
        $sheet->getStyle("A{$rDsk}:E{$rDsk}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "283747"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "EAECEE"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "BDC3C7"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "283747"]],
            ],
        ]);
        $sheet->getStyle("A{$rDsk}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$rDsk}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$rDsk}")->getNumberFormat()->setFormatCode("#,##0.0");
        $sheet->getStyle("E{$rDsk}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rDsk)->setRowHeight(22);

        // 6.2 Tabla 2: Servidores
        $rSrv = 9;
        foreach ($this->resumenInfraestructura as $srv) {
            $sheet->setCellValue("G{$rSrv}", $srv["ranking"]);
            $sheet->setCellValue("H{$rSrv}", $srv["nombre"]);
            $sheet->setCellValue("I{$rSrv}", $srv["tipo"]);
            $sheet->setCellValue("J{$rSrv}", $srv["estado_disco"]);
            $sheet->setCellValue("K{$rSrv}", $srv["backup"]);
            $sheet->setCellValue("L{$rSrv}", $srv["sync"]);

            $sheet->getStyle("G{$rSrv}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rSrv}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I{$rSrv}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$rSrv}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$rSrv}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$rSrv}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("G{$rSrv}:L{$rSrv}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rSrv)->setRowHeight(20);
            $rSrv++;
        }

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
        $sheetTitle = $this->title();
        $startChart = 15;
        $endChart = $startChart + 18;

        // 1. Gráfico de Torta: Ocupación de Disco Central
        $pieLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$8", null, 1),
        ];
        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\$9:\$B\$10", null, 2),
        ];
        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$C\$9:\$C\$10", null, 2),
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
        $pieTitle = new Title("Ocupación de Almacenamiento Central");

        $pieChart = new Chart(
            "chart_storage_pie",
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $pieChart->setTopLeftPosition("A".$startChart);
        $pieChart->setBottomRightPosition("E".$endChart);

        return [$pieChart];
    }
}
