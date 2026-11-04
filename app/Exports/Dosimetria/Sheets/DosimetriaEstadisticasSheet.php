<?php

namespace App\Exports\Dosimetria\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
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
 * Hoja de Estadísticas Consolidadas, Resumen Ejecutivo y Cumplimiento NRD para Dosimetría Radiológica.
 *
 * Flujo del proceso:
 * 1. Presenta bloque superior con identidad corporativa, filtros aplicados y fecha de emisión.
 * 2. Muestra 6 tarjetas de KPIs ejecutivos (Estudios, Pacientes únicos, Equipos, Dosis Efectiva Media E, Cumplimiento, Superaciones NRD).
 * 3. Renderiza la tabla analítica mensual agrupada por sede, modalidad, región anatómica y tipo de métrica (14 columnas).
 * 4. Aplica formato condicional semántico de semáforo para alertas preventivas y superaciones NRD en la columna N.
 * 5. Construye tablas de agregación analítica al pie: Carga por Modalidad y Perfil de Dosis por Órgano Diana (ICRP 103).
 * 6. Genera gráficos ejecutivos nativos basados en los datos agregados: Torta de Modalidades y Barras Comparativas de Radiación por Región Anatómica vs NRD (100%).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class DosimetriaEstadisticasSheet implements FromCollection, WithCharts, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected array $filtros;

    protected array $kpis;

    protected int $totalFilas = 0;

    protected array $resumenModalidades = [];

    protected array $resumenRegiones = [];

    protected int $totalEstudiosConsolidado = 0;

    /**
     * Constructor con inyección de datos consolidados y cálculo de agregaciones analíticas.
     *
     * @param  array  $datosExportacion  Estructura consolidada emitida por DosimetriaService
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(protected array $datosExportacion)
    {
        $this->filtros = $datosExportacion["filtros"] ?? [];
        $this->kpis = $datosExportacion["kpis"] ?? [];
        $this->totalFilas = count($datosExportacion["estadisticas"] ?? []);

        // 1. Agregación de carga de trabajo por modalidad técnica
        $modMeta = [
            "CT" => "Tomografía Computarizada (CT)",
            "DX" => "Radiología Digital (DX)",
            "MG" => "Mamografía Digital (MG)",
            "CR" => "Radiografía Computarizada (CR)",
        ];
        $modTotals = [];
        $totalEstudios = 0;
        foreach ($datosExportacion["estadisticas"] ?? [] as $fila) {
            $m = $fila->modalidad ?? "OTRA";
            $cant = (int) ($fila->total_estudios ?? 0);
            $modTotals[$m] = ($modTotals[$m] ?? 0) + $cant;
            $totalEstudios += $cant;
        }
        $this->totalEstudiosConsolidado = $totalEstudios;

        $resumenMod = [];
        foreach ($modMeta as $code => $nombre) {
            $cant = $modTotals[$code] ?? 0;
            if ($cant > 0 || count($resumenMod) < 2) {
                $pct = $totalEstudios > 0 ? round($cant / $totalEstudios, 4) : 0.0;
                $resumenMod[] = [
                    "codigo" => $code,
                    "nombre" => $nombre,
                    "total" => $cant,
                    "porcentaje" => $pct,
                ];
            }
        }
        foreach ($modTotals as $code => $cant) {
            if (! isset($modMeta[$code]) && $cant > 0) {
                $pct = $totalEstudios > 0 ? round($cant / $totalEstudios, 4) : 0.0;
                $resumenMod[] = [
                    "codigo" => $code,
                    "nombre" => "Modalidad {$code}",
                    "total" => $cant,
                    "porcentaje" => $pct,
                ];
            }
        }
        $this->resumenModalidades = $resumenMod;

        // 2. Agregación de perfil dosimétrico por los 7 órganos diana (ICRP 103)
        $regionesMeta = [
            "CRANEO" => "Cráneo / Encéfalo",
            "TORAX" => "Tórax / Cardio",
            "ABDOMEN" => "Abdomen / Hepático",
            "PELVIS" => "Pelvis / Urología",
            "COLUMNA" => "Columna Vertebral",
            "EXTREMIDADES" => "Extremidades",
            "MAMA" => "Mama / Senos",
        ];
        $regTotals = [];
        foreach (array_keys($regionesMeta) as $rc) {
            $regTotals[$rc] = ["total" => 0, "dosis_suma" => 0, "limite" => null];
        }
        foreach ($datosExportacion["estadisticas"] ?? [] as $fila) {
            $r = $fila->region_anatomica ?? "GENERAL";
            if (isset($regTotals[$r])) {
                $cant = (int) ($fila->total_estudios ?? 0);
                $prom = (float) ($fila->dosis_promedio ?? 0);
                $regTotals[$r]["total"] += $cant;
                $regTotals[$r]["dosis_suma"] += ($prom * $cant);
                if ($fila->limite_nrd !== null) {
                    $regTotals[$r]["limite"] = (float) $fila->limite_nrd;
                }
            }
        }

        $resumenReg = [];
        foreach ($regionesMeta as $code => $nombre) {
            $d = $regTotals[$code];
            $cant = $d["total"];
            $pctEstudios = $totalEstudios > 0 ? round($cant / $totalEstudios, 4) : 0.0;

            $pctNrd = 0.0;
            if ($cant > 0 && $d["limite"] !== null && $d["limite"] > 0) {
                $promDosis = $d["dosis_suma"] / $cant;
                $pctNrd = round($promDosis / $d["limite"], 4);
            }

            $estado = "SEGURO";
            $franja = "< 80% NRD";
            $accion = "Vigilancia de rutina";
            if ($pctNrd >= 1.0) {
                $estado = "CRÍTICO";
                $franja = "≥ 100% NRD";
                $accion = "Auditoría médica prioritaria";
            } elseif ($pctNrd >= 0.8) {
                $estado = "ALERTA";
                $franja = "80% - 99% NRD";
                $accion = "Optimización de protocolos";
            } elseif ($cant === 0) {
                $estado = "SIN DATOS";
                $franja = "Sin emisiones";
                $accion = "No aplica";
            }

            $resumenReg[] = [
                "codigo" => $code,
                "nombre" => $nombre,
                "total" => $cant,
                "pct_estudios" => $pctEstudios,
                "pct_nrd" => $pctNrd,
                "limite_nrd" => 1.0,
                "franja" => $franja,
                "estado" => $estado,
                "accion" => $accion,
            ];
        }
        $this->resumenRegiones = $resumenReg;
    }

    /**
     * Título de la pestaña en la hoja de cálculo.
     *
     * @return string Nombre de la pestaña
     */
    public function title(): string
    {
        return "Estadísticas y NRD";
    }

    /**
     * Celda inicial donde arrancan los encabezados de la tabla de datos.
     *
     * @return string Celda de inicio
     */
    public function startCell(): string
    {
        return "A8";
    }

    /**
     * Retorna la colección de estadísticas consolidada.
     *
     * @return Collection Colección de estadísticas
     */
    public function collection()
    {
        return collect($this->datosExportacion["estadisticas"] ?? []);
    }

    /**
     * Encabezados de la tabla analítica (14 columnas A a N).
     *
     * @return array Lista de encabezados de columna
     */
    public function headings(): array
    {
        return [
            "Sede Hospitalaria",
            "Período (Mes)",
            "Modalidad",
            "Región Anatómica",
            "Tipo Métrica",
            "Unidad",
            "Total Estudios",
            "Dosis Promedio",
            "Dosis Mínima",
            "Dosis Máxima",
            "Límite NRD (Res. 482)",
            "Alerta Preventiva (80%)",
            "% del Límite NRD",
            "Estado de Cumplimiento",
        ];
    }

    /**
     * Mapea cada fila de estadísticas a las 14 columnas del Excel.
     *
     * @param  mixed  $fila  Registro de estadística agrupada
     * @return array Fila mapeada con formato
     */
    public function map($fila): array
    {
        $pctNrd = null;
        if ($fila->porcentaje_nrd !== null) {
            $pctNrd = round($fila->porcentaje_nrd / 100, 3);
        }

        return [
            $fila->nom_sede ?? "",
            $fila->mes_periodo ?? "",
            $fila->modalidad ?? "",
            $fila->region_anatomica ?? "GENERAL",
            $fila->tipo_metrica ?? "",
            $fila->unidad_medida ?? "mGy",
            (int) ($fila->total_estudios ?? 0),
            (float) ($fila->dosis_promedio ?? 0),
            (float) ($fila->dosis_minima ?? 0),
            (float) ($fila->dosis_maxima ?? 0),
            $fila->limite_nrd !== null ? (float) $fila->limite_nrd : "N/A",
            $fila->valor_alerta !== null ? (float) $fila->valor_alerta : "N/A",
            $pctNrd !== null ? $pctNrd : "N/A",
            $fila->estado_regulatorio ?? "CONFORME (SEGURO)",
        ];
    }

    /**
     * Define los anchos óptimos de columna para lectura clínica (A hasta N).
     *
     * @return array Anchos por columna
     */
    public function columnWidths(): array
    {
        return [
            "A" => 28, // Sede
            "B" => 15, // Mes
            "C" => 14, // Modalidad
            "D" => 18, // Región Anatómica
            "E" => 15, // Tipo Métrica
            "F" => 12, // Unidad
            "G" => 16, // Total Estudios
            "H" => 18, // Dosis Promedio
            "I" => 16, // Dosis Mínima
            "J" => 16, // Dosis Máxima
            "K" => 22, // Límite NRD
            "L" => 22, // Alerta Preventiva
            "M" => 18, // % del Límite NRD
            "N" => 26, // Estado Cumplimiento
        ];
    }

    /**
     * Aplica el diseño corporativo, tipografía clínica y tablas de resumen agregado para gráficos.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo activa
     * @return array Configuración de estilos
     */
    public function styles(Worksheet $sheet): array
    {
        // 1. Título Corporativo Principal
        $sheet->setCellValue("A1", "SALUD TOLIMA IPS - INFORME CONSOLIDADO DE DOSIMETRÍA RADIOLÓGICA");
        $sheet->mergeCells("A1:N1");
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

        // 2. Subtítulo Regulatorio y Metadatos de Filtro
        $metaTexto = "Sede: {$this->filtros['nom_sede']}  |  Modalidad: {$this->filtros['modalidad']}  |  Métrica: {$this->filtros['tipo_metrica']}  |  Período: {$this->filtros['fecha_desde']} al {$this->filtros['fecha_hasta']}  |  Emisión: {$this->filtros['fecha_generacion']}";
        $sheet->setCellValue("A2", $metaTexto);
        $sheet->mergeCells("A2:N2");
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

        // 3. Tarjetas / Bloque de 6 KPIs Ejecutivos (Filas 4 y 5)
        $sheet->setCellValue("A4", "TOTAL ESTUDIOS");
        $sheet->setCellValue("A5", number_format($this->kpis["total_estudios"] ?? 0));
        $sheet->mergeCells("A4:B4");
        $sheet->mergeCells("A5:B5");

        $sheet->setCellValue("C4", "PACIENTES ÚNICOS");
        $sheet->setCellValue("C5", number_format($this->kpis["total_pacientes"] ?? 0));
        $sheet->mergeCells("C4:D4");
        $sheet->mergeCells("C5:D5");

        $sheet->setCellValue("E4", "EQUIPOS EMISORES");
        $sheet->setCellValue("E5", (string) ($this->kpis["total_equipos"] ?? 0));
        $sheet->mergeCells("E4:F4");
        $sheet->mergeCells("E5:F5");

        $sheet->setCellValue("G4", "DOSIS EFECTIVA MEDIA (E)");
        $sheet->setCellValue("G5", number_format($this->kpis["dosis_efectiva_media"] ?? 0, 2)." mSv");
        $sheet->mergeCells("G4:H4");
        $sheet->mergeCells("G5:H5");

        $sheet->setCellValue("I4", "CUMPLIMIENTO NORMATIVO");
        $sheet->setCellValue("I5", ($this->kpis["cumplimiento_pct"] ?? 100)."% Conforme");
        $sheet->mergeCells("I4:K4");
        $sheet->mergeCells("I5:K5");

        $sheet->setCellValue("L4", "SUPERACIONES NRD CRÍTICAS");
        $sheet->setCellValue("L5", (string) ($this->kpis["total_superaciones"] ?? 0));
        $sheet->mergeCells("L4:N4");
        $sheet->mergeCells("L5:N5");

        $sheet->getStyle("A4:N4")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "555555"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F1F3F5"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]]],
        ]);
        $sheet->getStyle("A5:N5")->applyFromArray([
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

        if (($this->kpis["total_superaciones"] ?? 0) > 0) {
            $sheet->getStyle("L4:N4")->applyFromArray([
                "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F8D7DA"]],
                "font" => ["color" => ["rgb" => "721C24"]],
            ]);
            $sheet->getStyle("L5:N5")->applyFromArray([
                "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF5F5"]],
                "font" => ["color" => ["rgb" => "721C24"]],
                "borders" => ["bottom" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "D9534F"]]],
            ]);
        }

        // 4. Encabezados de la Tabla de Datos Principal (Fila 8, Columnas A a N)
        $sheet->getStyle("A8:N8")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "FFFFFF"],
                "size" => 10,
                "name" => "Segoe UI",
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "203A43"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
                "vertical" => Alignment::VERTICAL_CENTER,
                "wrapText" => true,
            ],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]],
            ],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(28);

        // 5. Filas de Datos Tabulares (Fila 9 en adelante)
        $filaFin = 8 + $this->totalFilas;
        if ($this->totalFilas > 0) {
            $sheet->getStyle("A9:A{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("B9:F{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G9:L{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("M9:M{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("N9:N{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("G9:G{$filaFin}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("H9:L{$filaFin}")->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle("M9:M{$filaFin}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("A9:N{$filaFin}")->applyFromArray([
                "borders" => [
                    "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]],
                ],
            ]);

            for ($r = 9; $r <= $filaFin; $r++) {
                $sheet->getRowDimension($r)->setRowHeight(22);

                $estado = (string) $sheet->getCell("N{$r}")->getValue();
                if (str_contains($estado, "CRÍTICO") || str_contains($estado, "SUPERA")) {
                    $sheet->getStyle("N{$r}")->applyFromArray([
                        "font" => ["bold" => true, "color" => ["rgb" => "721C24"]],
                        "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F8D7DA"]],
                    ]);
                    $sheet->getStyle("M{$r}")->getFont()->setBold(true)->getColor()->setRGB("721C24");
                } elseif (str_contains($estado, "ALERTA")) {
                    $sheet->getStyle("N{$r}")->applyFromArray([
                        "font" => ["bold" => true, "color" => ["rgb" => "856404"]],
                        "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
                    ]);
                    $sheet->getStyle("M{$r}")->getFont()->setBold(true)->getColor()->setRGB("856404");
                } else {
                    $sheet->getStyle("N{$r}")->applyFromArray([
                        "font" => ["bold" => true, "color" => ["rgb" => "155724"]],
                        "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "D4EDDA"]],
                    ]);
                    $sheet->getStyle("M{$r}")->getFont()->getColor()->setRGB("155724");
                }
            }
        }

        // 6. RENDERIZADO DE TABLAS DE RESUMEN EJECUTIVO (Fuente para Gráficos Nativos de Alta Claridad)
        $rBanner = $filaFin + 2;
        $sheet->setCellValue("A{$rBanner}", "RESUMEN EJECUTIVO Y DISTRIBUCIÓN ANALÍTICA DE MEDICIONES (RESOLUCIÓN 482 DE 2018)");
        $sheet->mergeCells("A{$rBanner}:N{$rBanner}");
        $sheet->getStyle("A{$rBanner}")->applyFromArray([
            "font" => ["bold" => true, "size" => 11, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($rBanner)->setRowHeight(24);

        $rSub = $rBanner + 2;

        // 6.1 Tabla A (Cols A-E): Distribución y Carga de Trabajo por Modalidad
        $sheet->setCellValue("A{$rSub}", "CARGA DE TRABAJO Y MEDICIONES POR MODALIDAD");
        $sheet->mergeCells("A{$rSub}:E{$rSub}");
        $sheet->getStyle("A{$rSub}")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($rSub)->setRowHeight(22);

        $rH1 = $rSub + 1;
        $sheet->setCellValue("A{$rH1}", "Código");
        $sheet->setCellValue("B{$rH1}", "Modalidad Adquirida");
        $sheet->setCellValue("C{$rH1}", "Total Mediciones");
        $sheet->setCellValue("D{$rH1}", "% Distribución");
        $sheet->setCellValue("E{$rH1}", "Estado");
        $sheet->getStyle("A{$rH1}:E{$rH1}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension($rH1)->setRowHeight(20);

        $rModCur = $rH1 + 1;
        foreach ($this->resumenModalidades as $m) {
            $sheet->setCellValue("A{$rModCur}", $m["codigo"]);
            $sheet->setCellValue("B{$rModCur}", $m["nombre"]);
            $sheet->setCellValue("C{$rModCur}", (int) $m["total"]);
            $sheet->setCellValue("D{$rModCur}", (float) $m["porcentaje"]);
            $sheet->setCellValue("E{$rModCur}", $m["total"] > 0 ? "ACTIVO" : "SIN DISPAROS");

            $sheet->getStyle("A{$rModCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rModCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rModCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rModCur}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("D{$rModCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$rModCur}")->getNumberFormat()->setFormatCode("0.0%");
            $sheet->getStyle("E{$rModCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("A{$rModCur}:E{$rModCur}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rModCur)->setRowHeight(20);
            $rModCur++;
        }

        // Fila Total de Modalidades
        $sheet->setCellValue("A{$rModCur}", "TOTAL");
        $sheet->setCellValue("B{$rModCur}", "Consolidado de la Red");
        $sheet->setCellValue("C{$rModCur}", $this->totalEstudiosConsolidado);
        $sheet->setCellValue("D{$rModCur}", 1.0);
        $sheet->setCellValue("E{$rModCur}", "100%");
        $sheet->getStyle("A{$rModCur}:E{$rModCur}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("C{$rModCur}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("D{$rModCur}")->getNumberFormat()->setFormatCode("0.0%");

        // 6.2 Tabla B (Cols G-N): Perfil de Dosis por Región Anatómica vs Límite NRD
        $sheet->setCellValue("G{$rSub}", "PERFIL DOSIMÉTRICO Y CUMPLIMIENTO POR ÓRGANO DIANA (ICRP 103 / RES. 482)");
        $sheet->mergeCells("G{$rSub}:N{$rSub}");
        $sheet->getStyle("G{$rSub}")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $rH2 = $rSub + 1;
        $sheet->setCellValue("G{$rH2}", "Región Anatómica");
        $sheet->setCellValue("H{$rH2}", "Estudios");
        $sheet->setCellValue("I{$rH2}", "% Volumen");
        $sheet->setCellValue("J{$rH2}", "% Dosis s/ NRD");
        $sheet->setCellValue("K{$rH2}", "Límite Legal NRD");
        $sheet->setCellValue("L{$rH2}", "Franja ALARA");
        $sheet->setCellValue("M{$rH2}", "Semáforo");
        $sheet->setCellValue("N{$rH2}", "Acción Preventiva / Regulatoria");
        $sheet->getStyle("G{$rH2}:N{$rH2}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "37474F"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension($rH2)->setRowHeight(20);

        $rRegCur = $rH2 + 1;
        foreach ($this->resumenRegiones as $reg) {
            $sheet->setCellValue("G{$rRegCur}", $reg["nombre"]);
            $sheet->setCellValue("H{$rRegCur}", (int) $reg["total"]);
            $sheet->setCellValue("I{$rRegCur}", (float) $reg["pct_estudios"]);
            $sheet->setCellValue("J{$rRegCur}", (float) $reg["pct_nrd"]);
            $sheet->setCellValue("K{$rRegCur}", (float) $reg["limite_nrd"]);
            $sheet->setCellValue("L{$rRegCur}", $reg["franja"]);
            $sheet->setCellValue("M{$rRegCur}", $reg["estado"]);
            $sheet->setCellValue("N{$rRegCur}", $reg["accion"]);

            $sheet->getStyle("G{$rRegCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("H{$rRegCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("H{$rRegCur}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("I{$rRegCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I{$rRegCur}")->getNumberFormat()->setFormatCode("0.0%");
            $sheet->getStyle("J{$rRegCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rRegCur}")->getNumberFormat()->setFormatCode("0.0%");
            $sheet->getStyle("K{$rRegCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rRegCur}")->getNumberFormat()->setFormatCode("0.0%");
            $sheet->getStyle("L{$rRegCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$rRegCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N{$rRegCur}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Semáforo condicional en columna M
            if ($reg["estado"] === "CRÍTICO") {
                $sheet->getStyle("M{$rRegCur}")->applyFromArray([
                    "font" => ["bold" => true, "color" => ["rgb" => "721C24"]],
                    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F8D7DA"]],
                ]);
                $sheet->getStyle("J{$rRegCur}")->getFont()->setBold(true)->getColor()->setRGB("721C24");
            } elseif ($reg["estado"] === "ALERTA") {
                $sheet->getStyle("M{$rRegCur}")->applyFromArray([
                    "font" => ["bold" => true, "color" => ["rgb" => "856404"]],
                    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
                ]);
                $sheet->getStyle("J{$rRegCur}")->getFont()->setBold(true)->getColor()->setRGB("856404");
            } elseif ($reg["estado"] === "SEGURO") {
                $sheet->getStyle("M{$rRegCur}")->applyFromArray([
                    "font" => ["bold" => true, "color" => ["rgb" => "155724"]],
                    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "D4EDDA"]],
                ]);
                $sheet->getStyle("J{$rRegCur}")->getFont()->getColor()->setRGB("155724");
            } else {
                $sheet->getStyle("M{$rRegCur}")->applyFromArray([
                    "font" => ["color" => ["rgb" => "6C757D"]],
                    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F8F9FA"]],
                ]);
            }

            $sheet->getStyle("G{$rRegCur}:N{$rRegCur}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
            ]);
            $sheet->getRowDimension($rRegCur)->setRowHeight(20);
            $rRegCur++;
        }

        return [];
    }

    /**
     * Genera los gráficos ejecutivos nativos de Excel basados en los datos consolidados.
     *
     * Flujo del proceso:
     * 1. Gráfico 1: Torta (Pie) con exactamente 2 a 4 sectores de modalidades agregadas (CT, DX, MG, CR).
     * 2. Gráfico 2: Columnas comparativas agrupadas de los 7 órganos diana frente a la línea de corte NRD (100%).
     * 3. Ambas gráficas leen exclusivamente de las tablas de resumen ejecutivo, erradicando micro-sectores y códigos de barras ilegibles.
     *
     * @return Chart[] Lista de gráficos incrustados en la hoja
     */
    public function charts(): array
    {
        if ($this->totalFilas === 0) {
            return [];
        }

        $sheetTitle = $this->title();
        $filaFin = 8 + $this->totalFilas;
        $rSub = $filaFin + 4;
        $rH1 = $rSub + 1;
        $rH2 = $rSub + 1;

        $rModStart = $rH1 + 1;
        $rModEnd = $rModStart + count($this->resumenModalidades) - 1;

        $rRegStart = $rH2 + 1;
        $rRegEnd = $rRegStart + count($this->resumenRegiones) - 1;

        $startChart = max($rModEnd + 2, $rRegEnd + 2) + 2;

        // 1. Gráfico de Torta (Pie): Distribución Limpia de Mediciones por Modalidad
        $pieLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\${$rH1}", null, 1),
        ];
        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\${$rModStart}:\$B\${$rModEnd}", null, count($this->resumenModalidades)),
        ];
        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$C\${$rModStart}:\$C\${$rModEnd}", null, count($this->resumenModalidades)),
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
        $pieTitle = new Title("Distribución de Mediciones por Modalidad");

        $pieChart = new Chart(
            "chart_torta_modalidad",
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $pieChart->setTopLeftPosition("A".$startChart);
        $pieChart->setBottomRightPosition("F".($startChart + 18));

        // 2. Gráfico de Columnas: Perfil Dosimétrico por Región Anatómica vs NRD (100%)
        $barLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$J\${$rH2}", null, 1),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$K\${$rH2}", null, 1),
        ];
        $barCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$G\${$rRegStart}:\$G\${$rRegEnd}", null, count($this->resumenRegiones)),
        ];
        $barValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$J\${$rRegStart}:\$J\${$rRegEnd}", null, count($this->resumenRegiones)),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$K\${$rRegStart}:\$K\${$rRegEnd}", null, count($this->resumenRegiones)),
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
        $barTitle = new Title("Perfil de Dosis por Región Anatómica vs Límite NRD (100% Res. 482)");

        $barChart = new Chart(
            "chart_barras_nrd",
            $barTitle,
            $barLegend,
            $barPlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $barChart->setTopLeftPosition("G".$startChart);
        $barChart->setBottomRightPosition("N".($startChart + 18));

        return [$pieChart, $barChart];
    }
}
