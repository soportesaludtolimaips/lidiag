<?php

namespace App\Exports\Dosimetria\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de Matriz de Riesgo Dosimétrico y Mapa de Calor Institucional (Resolución 482 de 2018 / ICRP 135).
 *
 * Flujo del proceso:
 * 1. Presenta encabezado corporativo y normativo con metadatos de emisión.
 * 2. Renderiza la leyenda metodológica de semaforización (Zona Segura <80%, Alerta 80-99%, Crítica ≥100%).
 * 3. Expone la matriz bidimensional de Sedes vs. Regiones Anatómicas diana con % respecto al NRD.
 * 4. Aplica coloración condicional pastel de alta legibilidad tipo Mapa de Calor en celdas de órganos.
 * 5. Incorpora notas al pie con referencias a la Resolución 482 de 2018, ICRP 103, ICRP 135 e IAEA GSR Part 3.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class DosimetriaMatrizRiesgoSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected array $filtros;

    protected array $matrizRiesgo;

    protected int $totalFilas = 0;

    /**
     * Constructor con inyección de datos analíticos consolidados.
     *
     * @param  array  $datosExportacion  Estructura emitida por DosimetriaService
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(protected array $datosExportacion)
    {
        $this->filtros = $datosExportacion["filtros"] ?? [];
        $this->matrizRiesgo = $datosExportacion["matriz_riesgo"] ?? [];
        $this->totalFilas = count($this->matrizRiesgo);
    }

    /**
     * Título de la pestaña en el libro Excel.
     *
     * @return string Nombre de la pestaña
     */
    public function title(): string
    {
        return "Mapa de Calor - Riesgo NRD";
    }

    /**
     * Celda donde inicia la grilla de datos tabulares.
     *
     * @return string Celda de inicio
     */
    public function startCell(): string
    {
        return "A8";
    }

    /**
     * Retorna la colección de sedes y sus perfiles de riesgo por órgano.
     *
     * @return Collection Colección de filas para la matriz
     */
    public function collection()
    {
        return collect($this->matrizRiesgo);
    }

    /**
     * Encabezados de columnas de la matriz de calor.
     *
     * @return array Encabezados
     */
    public function headings(): array
    {
        return [
            "Sede Hospitalaria",
            "Cráneo (% NRD)",
            "Tórax (% NRD)",
            "Abdomen (% NRD)",
            "Pelvis (% NRD)",
            "Columna (% NRD)",
            "Extremidades (% NRD)",
            "Mama (% NRD)",
            "Conformidad Global",
            "Nivel de Riesgo Institucional",
        ];
    }

    /**
     * Mapea cada sede y sus órganos a los valores de la fila.
     *
     * @param  mixed  $fila  Datos consolidados de la sede
     * @return array Fila con valores de radiación relativa
     */
    public function map($fila): array
    {
        $reg = $fila["regiones"] ?? [];

        $formatCell = function (string $key) use ($reg) {
            $r = $reg[$key] ?? null;
            if (! $r || $r["pct_nrd"] === null) {
                return "N/A";
            }

            return round($r["pct_nrd"] / 100, 3);
        };

        return [
            $fila["nom_sede"] ?? "",
            $formatCell("CRANEO"),
            $formatCell("TORAX"),
            $formatCell("ABDOMEN"),
            $formatCell("PELVIS"),
            $formatCell("COLUMNA"),
            $formatCell("EXTREMIDADES"),
            $formatCell("MAMA"),
            isset($fila["tasa_conformidad"]) ? round($fila["tasa_conformidad"] / 100, 3) : 1.0,
            $fila["nivel_riesgo"] ?? "BAJO (Seguro)",
        ];
    }

    /**
     * Anchos de columna optimizados para visualización de matriz.
     *
     * @return array Anchos por columna
     */
    public function columnWidths(): array
    {
        return [
            "A" => 30, // Sede Hospitalaria
            "B" => 17, // Cráneo
            "C" => 17, // Tórax
            "D" => 17, // Abdomen
            "E" => 17, // Pelvis
            "F" => 17, // Columna
            "G" => 17, // Extremidades
            "H" => 17, // Mama
            "I" => 22, // Conformidad Global
            "J" => 28, // Nivel de Riesgo
        ];
    }

    /**
     * Aplica el mapa de calor condicional, estilos corporativos y notas normativas.
     *
     * @param  Worksheet  $sheet  Hoja activa de PhpSpreadsheet
     * @return array Arreglo de estilos
     */
    public function styles(Worksheet $sheet): array
    {
        // 1. Título Corporativo Principal
        $sheet->setCellValue("A1", "SALUD TOLIMA IPS - MATRIZ DE RIESGO DOSIMÉTRICO Y MAPA DE CALOR INSTITUCIONAL");
        $sheet->mergeCells("A1:J1");
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

        // 2. Subtítulo Regulatorio y Metadatos de Consulta
        $metaTexto = "Vigilancia de Radioprotección por Sede y Región Anatómica | Filtros: {$this->filtros['nom_sede']} | Modalidad: {$this->filtros['modalidad']} | Período: {$this->filtros['fecha_desde']} al {$this->filtros['fecha_hasta']} | Resolución 482 de 2018 & ICRP 135";
        $sheet->setCellValue("A2", $metaTexto);
        $sheet->mergeCells("A2:J2");
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

        // 3. Leyenda Metodológica y Criterios de Semáforo (Filas 4 y 5)
        $sheet->setCellValue("A4", "METODOLOGÍA DE EVALUACIÓN Y SEMAFORIZACIÓN DEL MAPA DE CALOR (% RESPECTO AL NIVEL DE REFERENCIA NRD)");
        $sheet->mergeCells("A4:J4");
        $sheet->getStyle("A4")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "203A43"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(20);

        // Bloques de Leyenda en Fila 5
        $sheet->setCellValue("A5", "🟢 SEGURO (< 80% NRD)");
        $sheet->mergeCells("A5:B5");
        $sheet->getStyle("A5:B5")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "155724"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "D4EDDA"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C3E6CB"]]],
        ]);

        $sheet->setCellValue("C5", "🟡 PRECAUCIÓN (80% - 99% NRD)");
        $sheet->mergeCells("C5:D5");
        $sheet->getStyle("C5:D5")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "856404"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFEEBA"]]],
        ]);

        $sheet->setCellValue("E5", "🔴 SUPERACIÓN CRÍTICA (≥ 100% NRD)");
        $sheet->mergeCells("E5:F5");
        $sheet->getStyle("E5:F5")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "721C24"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F8D7DA"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "F5C6CB"]]],
        ]);

        $sheet->setCellValue("G5", "⚪ NO APLICA / SIN EMISIÓN");
        $sheet->mergeCells("G5:H5");
        $sheet->getStyle("G5:H5")->applyFromArray([
            "font" => ["bold" => false, "size" => 9, "color" => ["rgb" => "6C757D"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F8F9FA"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "E0E0E0"]]],
        ]);

        $sheet->setCellValue("I5", "TASA DE CONFORMIDAD INSTITUCIONAL");
        $sheet->mergeCells("I5:J5");
        $sheet->getStyle("I5:J5")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A237E"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "E8EAF6"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]]],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(22);

        // 4. Encabezados de la Tabla de Datos (Fila 8)
        $sheet->getStyle("A8:J8")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "FFFFFF"],
                "size" => 10,
                "name" => "Segoe UI",
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "0F2027"],
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
        $sheet->getRowDimension(8)->setRowHeight(30);

        // 5. Aplicación del Mapa de Calor en Filas de Datos (Fila 9 en adelante)
        $filaFin = 8 + $this->totalFilas;
        if ($this->totalFilas > 0) {
            $sheet->getStyle("A9:A{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("B9:H{$filaFin}")->getNumberFormat()->setFormatCode("0.0%");
            $sheet->getStyle("I9:I{$filaFin}")->getNumberFormat()->setFormatCode("0.0%");
            $sheet->getStyle("B9:J{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("A9:J{$filaFin}")->applyFromArray([
                "borders" => [
                    "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "D6D8DB"]],
                ],
            ]);

            $columnasOrganos = ["B", "C", "D", "E", "F", "G", "H"];

            for ($r = 9; $r <= $filaFin; $r++) {
                $sheet->getRowDimension($r)->setRowHeight(24);

                // Colorear cada región anatómica según su porcentaje NRD
                foreach ($columnasOrganos as $col) {
                    $val = $sheet->getCell("{$col}{$r}")->getValue();

                    if ($val === "N/A" || $val === null || $val === "") {
                        $sheet->getStyle("{$col}{$r}")->applyFromArray([
                            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F8F9FA"]],
                            "font" => ["color" => ["rgb" => "ADB5BD"], "size" => 10],
                        ]);
                    } else {
                        $num = is_numeric($val) ? (float) $val : 0;
                        if ($num >= 1.0) {
                            // Superación Crítica (≥ 100% NRD)
                            $sheet->getStyle("{$col}{$r}")->applyFromArray([
                                "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F8D7DA"]],
                                "font" => ["bold" => true, "color" => ["rgb" => "721C24"], "size" => 10],
                            ]);
                        } elseif ($num >= 0.8) {
                            // Alerta Preventiva (80% - 99% NRD)
                            $sheet->getStyle("{$col}{$r}")->applyFromArray([
                                "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
                                "font" => ["bold" => true, "color" => ["rgb" => "856404"], "size" => 10],
                            ]);
                        } else {
                            // Zona Conforme / Segura (< 80% NRD)
                            $sheet->getStyle("{$col}{$r}")->applyFromArray([
                                "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "D4EDDA"]],
                                "font" => ["bold" => true, "color" => ["rgb" => "155724"], "size" => 10],
                            ]);
                        }
                    }
                }

                // Estilo para Tasa de Conformidad (Columna I)
                $sheet->getStyle("I{$r}")->applyFromArray([
                    "font" => ["bold" => true, "color" => ["rgb" => "1A237E"]],
                    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
                ]);

                // Estilo semántico para Nivel de Riesgo Institucional (Columna J)
                $riesgoTexto = (string) $sheet->getCell("J{$r}")->getValue();
                if (str_contains($riesgoTexto, "ALTO")) {
                    $sheet->getStyle("J{$r}")->applyFromArray([
                        "font" => ["bold" => true, "color" => ["rgb" => "721C24"]],
                        "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F8D7DA"]],
                    ]);
                } elseif (str_contains($riesgoTexto, "MEDIO")) {
                    $sheet->getStyle("J{$r}")->applyFromArray([
                        "font" => ["bold" => true, "color" => ["rgb" => "856404"]],
                        "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFF3CD"]],
                    ]);
                } else {
                    $sheet->getStyle("J{$r}")->applyFromArray([
                        "font" => ["bold" => true, "color" => ["rgb" => "155724"]],
                        "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "D4EDDA"]],
                    ]);
                }
            }
        }

        // 6. Bloque de Notas Regulatorias y Fundamentación Clínica al pie de la tabla
        $filaNotas = max($filaFin + 2, 12);

        $sheet->setCellValue("A{$filaNotas}", "FUNDAMENTACIÓN TÉCNICA Y MARCO REGULATORIO INTERNACIONAL");
        $sheet->mergeCells("A{$filaNotas}:J{$filaNotas}");
        $sheet->getStyle("A{$filaNotas}")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_LEFT, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($filaNotas)->setRowHeight(22);

        $notas = [
            "1. Resolución 482 de 2018 (MinSalud Colombia): Establece los Niveles de Referencia para Diagnóstico (NRD) como herramienta obligatoria de optimización de la protección radiológica en prácticas médicas.",
            "2. ICRP Publicación 135: Define los procedimientos para el establecimiento y utilización clínica de los Niveles de Referencia para Diagnóstico en tomografía computarizada, radiología intervencionista y convencional.",
            "3. ICRP Publicación 103: Ponderación de dosis efectiva (E) mediante coeficientes tisulares normalizados según sensibilidad biológica de cada órgano diana.",
            "4. Principio ALARA (As Low As Reasonably Achievable): La franja de precaución preventiva (80%–99% NRD) permite identificar derivas técnicas y ajustar protocolos antes de incurrir en sobredosis o infracción legal.",
        ];

        foreach ($notas as $idx => $nota) {
            $f = $filaNotas + 1 + $idx;
            $sheet->setCellValue("A{$f}", $nota);
            $sheet->mergeCells("A{$f}:J{$f}");
            $sheet->getStyle("A{$f}")->applyFromArray([
                "font" => ["italic" => true, "size" => 9, "color" => ["rgb" => "495057"], "name" => "Segoe UI"],
                "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F8F9FA"]],
                "alignment" => ["horizontal" => Alignment::HORIZONTAL_LEFT, "vertical" => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($f)->setRowHeight(19);
        }

        return [];
    }
}
