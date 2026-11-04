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
 * Hoja de Estadísticas Consolidadas, Resumen Ejecutivo y Gráficos Nativos para Estudios PACS.
 *
 * Flujo del proceso:
 * 1. Recibe la colección de estudios consultada desde la base de datos remota del PACS (MySQL sucursal).
 * 2. Consolida indicadores clave de desempeño (KPIs): Total Estudios, Pacientes Únicos, Modalidades Activas, Promedio Diario y Procedimientos Distintos.
 * 3. Agrupa la carga de trabajo por modalidad diagnóstica (CT, DX, MR, MG, CR, US, etc.) con conteo, pacientes y porcentaje de cuota.
 * 4. Calcula el ranking Top 10 de procedimientos radiológicos más demandados en el período.
 * 5. Diseña las tablas ejecutivas con tipografía Segoe UI, colores institucionales y formato numérico.
 * 6. Genera e incrusta gráficos ejecutivos nativos de PhpSpreadsheet: Torta de Modalidades y Barras de Top Procedimientos.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReportesEstudioPACEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalEstudios = 0;

    protected int $totalPacientes = 0;

    protected int $totalProcedimientosDistintos = 0;

    protected float $promedioDiario = 0.0;

    /**
     * @var array<int, array{codigo: string, nombre: string, total: int, pacientes: int, porcentaje: float}>
     */
    protected array $resumenModalidades = [];

    /**
     * @var array<int, array{ranking: int, procedimiento: string, modalidad: string, total: int, pacientes: int, porcentaje: float}>
     */
    protected array $topProcedimientos = [];

    /**
     * Constructor con inyección de datos y cálculo analítico de estadísticas.
     *
     * @param  Collection  $estudios  Colección de registros de la base de datos PACS.
     * @param  string  $sedeNombre  Nombre descriptivo de la sede hospitalaria.
     * @param  string  $fechaInicio  Fecha inicial del filtro (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha final del filtro (YYYY-MM-DD).
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
     * Calcula métricas analíticas, agrupaciones por modalidad y top procedimientos.
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

        $this->totalPacientes = $this->estudios
            ->pluck("pat_id")
            ->filter(fn ($v) => ! empty($v) && $v !== "N/A")
            ->unique()
            ->count();

        try {
            $inicio = Carbon::parse($this->fechaInicio);
            $fin = Carbon::parse($this->fechaFin);
            $dias = max(1, $inicio->diffInDays($fin) + 1);
        } catch (\Throwable) {
            $dias = 1;
        }
        $this->promedioDiario = round($this->totalEstudios / $dias, 1);

        $modMeta = [
            "CT" => "Tomografía Computarizada (CT)",
            "MR" => "Resonancia Magnética (MR)",
            "DX" => "Radiografía Digital (DX)",
            "CR" => "Radiografía Computarizada (CR)",
            "MG" => "Mamografía Digital (MG)",
            "US" => "Ultrasonido / Ecografía (US)",
            "XA" => "Angiografía / Fluoroscopia (XA)",
            "NM" => "Medicina Nuclear (NM)",
            "PT" => "Tomografía por Emisión (PT)",
            "OT" => "Otras Modalidades (OT)",
        ];

        // 1. Agrupación por Modalidad Diagnóstica
        $gruposMod = $this->estudios->groupBy(function ($item) {
            $m = trim((string) ($item->mods_in_study ?? ""));

            return $m !== "" ? strtoupper($m) : "OTRA";
        });

        $resumenMod = [];
        foreach ($gruposMod as $cod => $items) {
            $cant = $items->count();
            $pacs = $items->pluck("pat_id")->filter(fn ($v) => ! empty($v) && $v !== "N/A")->unique()->count();
            $pct = $this->totalEstudios > 0 ? round($cant / $this->totalEstudios, 4) : 0.0;
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

        // 2. Agrupación por Procedimiento Radiológico
        $gruposProc = $this->estudios->groupBy(function ($item) {
            $desc = trim((string) ($item->study_desc ?? ""));

            return $desc !== "" ? mb_strtoupper($desc, "UTF-8") : "SIN DESCRIPCIÓN ESPECÍFICA";
        });

        $this->totalProcedimientosDistintos = $gruposProc->count();

        $topProc = [];
        foreach ($gruposProc as $desc => $items) {
            $cant = $items->count();
            $pacs = $items->pluck("pat_id")->filter(fn ($v) => ! empty($v) && $v !== "N/A")->unique()->count();
            $pct = $this->totalEstudios > 0 ? round($cant / $this->totalEstudios, 4) : 0.0;
            $modPrincipal = $items->pluck("mods_in_study")->filter()->first() ?? "N/A";

            $topProc[] = [
                "ranking" => 0,
                "procedimiento" => (string) $desc,
                "modalidad" => strtoupper(trim((string) $modPrincipal)),
                "total" => $cant,
                "pacientes" => $pacs,
                "porcentaje" => $pct,
            ];
        }

        usort($topProc, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $topProc = array_slice($topProc, 0, 10);
        foreach ($topProc as $i => &$item) {
            $item["ranking"] = $i + 1;
        }
        unset($item);
        $this->topProcedimientos = $topProc;
    }

    /**
     * Título de la pestaña en el libro Excel.
     *
     * @returns string Nombre de la pestaña.
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
     * Retorna array base vacío (el renderizado es gestionado por styles).
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
     * Define los anchos óptimos de columna para las dos tablas paralelas.
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
            "D" => 14, // Pacientes
            "E" => 16, // % Cuota
            "F" => 4,  // Separador visual
            "G" => 6,  // Rank #
            "H" => 46, // Procedimiento Radiológico
            "I" => 14, // Modalidad
            "J" => 14, // Estudios
            "K" => 14, // Pacientes
            "L" => 16, // % del Total
        ];
    }

    /**
     * Aplica el diseño corporativo, tarjetas de KPIs y tablas estadísticas.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo activa.
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
        $sheet->setCellValue("A1", "SALUD TOLIMA - RESUMEN EJECUTIVO Y ESTADÍSTICAS DE ESTUDIOS PACS");
        $sheet->mergeCells("A1:L1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "FFFFFF"],
                "size" => 14,
                "name" => "Segoe UI",
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A2274"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Subtítulo y Metadatos de Filtro
        $fechaEmision = Carbon::now()->format("Y-m-d H:i");
        $sedeLabel = ! empty($this->sedeNombre) ? $this->sedeNombre : "Todas las Sedes";
        $metaTexto = "Sede: {$sedeLabel}  |  Rango de Fechas: {$this->fechaInicio} al {$this->fechaFin}  |  Fecha de Emisión: {$fechaEmision}";
        $sheet->setCellValue("A2", $metaTexto);
        $sheet->mergeCells("A2:L2");
        $sheet->getStyle("A2")->applyFromArray([
            "font" => [
                "bold" => false,
                "color" => ["rgb" => "333333"],
                "size" => 10,
                "name" => "Segoe UI",
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "E9ECEF"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // 3. Tarjetas de KPIs Ejecutivos (Filas 4 y 5)
        $sheet->setCellValue("A4", "TOTAL ESTUDIOS PACS");
        $sheet->setCellValue("A5", number_format($this->totalEstudios));
        $sheet->mergeCells("A4:B4");
        $sheet->mergeCells("A5:B5");

        $sheet->setCellValue("C4", "PACIENTES ÚNICOS");
        $sheet->setCellValue("C5", number_format($this->totalPacientes));
        $sheet->mergeCells("C4:D4");
        $sheet->mergeCells("C5:D5");

        $sheet->setCellValue("E4", "MODALIDADES ACTIVAS");
        $sheet->setCellValue("E5", (string) count($this->resumenModalidades));
        $sheet->mergeCells("E4:F4");
        $sheet->mergeCells("E5:F5");

        $sheet->setCellValue("G4", "PROMEDIO DIARIO");
        $sheet->setCellValue("G5", number_format($this->promedioDiario, 1)." est/día");
        $sheet->mergeCells("G4:H4");
        $sheet->mergeCells("G5:H5");

        $sheet->setCellValue("I4", "PROCEDIMIENTOS DISTINTOS");
        $sheet->setCellValue("I5", number_format($this->totalProcedimientosDistintos));
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
        $sheet->setCellValue("A7", "DISTRIBUCIÓN DE ESTUDIOS POR MODALIDAD DIAGNÓSTICA");
        $sheet->mergeCells("A7:E7");
        $sheet->getStyle("A7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue("G7", "TOP 10 PROCEDIMIENTOS MÁS FRECUENTES EN PACS");
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
        $sheet->setCellValue("D8", "Pacientes");
        $sheet->setCellValue("E8", "% Cuota");
        $sheet->getStyle("A8:E8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $sheet->setCellValue("G8", "#");
        $sheet->setCellValue("H8", "Procedimiento Radiológico");
        $sheet->setCellValue("I8", "Modalidad");
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

        // 6. Filas de Datos de las Tablas
        if ($this->totalEstudios === 0) {
            $sheet->setCellValue("A9", "No se encontraron estudios adquiridos en el período seleccionado.");
            $sheet->mergeCells("A9:E9");
            $sheet->getStyle("A9:E9")->applyFromArray([
                "font" => ["italic" => true, "color" => ["rgb" => "6C757D"]],
                "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension(9)->setRowHeight(22);

            $sheet->setCellValue("G9", "Sin datos de procedimientos para graficar.");
            $sheet->mergeCells("G9:L9");
            $sheet->getStyle("G9:L9")->applyFromArray([
                "font" => ["italic" => true, "color" => ["rgb" => "6C757D"]],
                "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);

            return [];
        }

        // 6.1 Datos Tabla 1: Modalidades (Cols A a E)
        $rMod = 9;
        foreach ($this->resumenModalidades as $m) {
            $sheet->setCellValue("A{$rMod}", $m["codigo"]);
            $sheet->setCellValue("B{$rMod}", $m["nombre"]);
            $sheet->setCellValue("C{$rMod}", (int) $m["total"]);
            $sheet->setCellValue("D{$rMod}", (int) $m["pacientes"]);
            $sheet->setCellValue("E{$rMod}", (float) $m["porcentaje"]);

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

        // Fila Total Consolidado Modalidades
        $sheet->setCellValue("A{$rMod}", "TOTAL");
        $sheet->setCellValue("B{$rMod}", "Consolidado Todas las Modalidades");
        $sheet->setCellValue("C{$rMod}", $this->totalEstudios);
        $sheet->setCellValue("D{$rMod}", $this->totalPacientes);
        $sheet->setCellValue("E{$rMod}", 1.0);
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

        // 6.2 Datos Tabla 2: Top 10 Procedimientos (Cols G a L)
        $rProc = 9;
        $topEstudiosTotal = 0;
        $topPctTotal = 0.0;
        foreach ($this->topProcedimientos as $p) {
            $sheet->setCellValue("G{$rProc}", $p["ranking"]);
            $sheet->setCellValue("H{$rProc}", $p["procedimiento"]);
            $sheet->setCellValue("I{$rProc}", $p["modalidad"]);
            $sheet->setCellValue("J{$rProc}", (int) $p["total"]);
            $sheet->setCellValue("K{$rProc}", (int) $p["pacientes"]);
            $sheet->setCellValue("L{$rProc}", (float) $p["porcentaje"]);

            $topEstudiosTotal += (int) $p["total"];
            $topPctTotal += (float) $p["porcentaje"];

            $sheet->getStyle("G{$rProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I{$rProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$rProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rProc}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("K{$rProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rProc}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("L{$rProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L{$rProc}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("G{$rProc}:L{$rProc}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rProc)->setRowHeight(20);
            $rProc++;
        }

        // Fila Subtotal Top 10
        $sheet->setCellValue("G{$rProc}", "SUBTOTAL");
        $sheet->setCellValue("H{$rProc}", "Subtotal Top 10 Procedimientos");
        $sheet->setCellValue("I{$rProc}", "-");
        $sheet->setCellValue("J{$rProc}", $topEstudiosTotal);
        $sheet->setCellValue("K{$rProc}", "-");
        $sheet->setCellValue("L{$rProc}", $topPctTotal);
        $sheet->getStyle("G{$rProc}:L{$rProc}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("G{$rProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$rProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K{$rProc}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J{$rProc}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("L{$rProc}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rProc)->setRowHeight(22);

        return [];
    }

    /**
     * Genera los gráficos ejecutivos nativos de Excel (Torta y Barras).
     *
     * Flujo del proceso:
     * 1. Gráfico 1: Torta (Pie Chart) de distribución porcentual por modalidad diagnóstica.
     * 2. Gráfico 2: Columnas agrupadas (Clustered Column) con el Top 10 de procedimientos más frecuentes.
     * 3. Ambas gráficas leen directamente de las tablas agregadas en la hoja, posicionándose en paralelo bajo ellas.
     *
     * @returns Chart[] Lista de gráficos incrustados en la hoja.
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

        $rProcDataStart = 9;
        $rProcDataEnd = 8 + count($this->topProcedimientos);

        // Posicionamiento de gráficos: 3 filas debajo de la tabla más extensa
        $startChart = max($rModDataEnd + 2, $rProcDataEnd + 2) + 3;
        $endChart = $startChart + 18;

        // 1. Gráfico de Torta (Pie): Distribución por Modalidad Diagnóstica
        $pieLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$8", null, 1),
        ];
        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\${$rModDataStart}:\$B\${$rModDataEnd}", null, count($this->resumenModalidades)),
        ];
        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$C\${$rModDataStart}:\$C\${$rModDataEnd}", null, count($this->resumenModalidades)),
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
        $pieTitle = new Title("Distribución de Estudios por Modalidad Diagnóstica");

        $pieChart = new Chart(
            "chart_pacs_modalidades",
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $pieChart->setTopLeftPosition("A".$startChart);
        $pieChart->setBottomRightPosition("E".$endChart);

        // 2. Gráfico de Barras / Columnas: Top Procedimientos Frecuentes en PACS
        $chartsList = [$pieChart];

        if (! empty($this->topProcedimientos)) {
            $barLabels = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$J\$8", null, 1),
            ];
            $barCategories = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$H\${$rProcDataStart}:\$H\${$rProcDataEnd}", null, count($this->topProcedimientos)),
            ];
            $barValues = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$J\${$rProcDataStart}:\$J\${$rProcDataEnd}", null, count($this->topProcedimientos)),
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
            $barTitle = new Title("Top Procedimientos Más Frecuentes en PACS");

            $barChart = new Chart(
                "chart_pacs_top_procedimientos",
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
