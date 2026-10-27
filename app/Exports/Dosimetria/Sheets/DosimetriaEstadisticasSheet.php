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
 * Hoja de Estadísticas Consolidadas y Cumplimiento NRD para Dosimetría Radiológica.
 *
 * Flujo del proceso:
 * 1. Presenta bloque superior con identidad corporativa, filtros aplicados y fecha de emisión.
 * 2. Muestra tarjetas de KPIs ejecutivos (Total estudios, equipos, porcentaje de cumplimiento).
 * 3. Renderiza la tabla analítica mensual agrupada por sede, modalidad y tipo de métrica.
 * 4. Aplica formato condicional semántico de semáforo para alertas preventivas y superaciones NRD.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-26
 */
class DosimetriaEstadisticasSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected array $filtros;

    protected array $kpis;

    protected int $totalFilas = 0;

    /**
     * Constructor con inyección de datos consolidados.
     *
     * @param  array  $datosExportacion  Estructura consolidada emitida por DosimetriaService
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-26
     */
    public function __construct(protected array $datosExportacion)
    {
        $this->filtros = $datosExportacion['filtros'] ?? [];
        $this->kpis = $datosExportacion['kpis'] ?? [];
        $this->totalFilas = count($datosExportacion['estadisticas'] ?? []);
    }

    /**
     * Título de la pestaña en la hoja de cálculo.
     *
     * @return string Nombre de la pestaña
     */
    public function title(): string
    {
        return 'Estadísticas y NRD';
    }

    /**
     * Celda inicial donde arrancan los encabezados de la tabla de datos.
     *
     * @return string Celda de inicio
     */
    public function startCell(): string
    {
        return 'A8';
    }

    /**
     * Retorna la colección de estadísticas consolidada.
     *
     * @return Collection Colección de estadísticas
     */
    public function collection()
    {
        return collect($this->datosExportacion['estadisticas'] ?? []);
    }

    /**
     * Encabezados de la tabla analítica.
     *
     * @return array Lista de encabezados de columna
     */
    public function headings(): array
    {
        return [
            'Sede Hospitalaria',
            'Período (Mes)',
            'Modalidad',
            'Tipo Métrica',
            'Unidad',
            'Total Estudios',
            'Dosis Promedio',
            'Dosis Mínima',
            'Dosis Máxima',
            'Límite NRD (Res. 482)',
            'Alerta Preventiva (80%)',
            'Estado de Cumplimiento',
        ];
    }

    /**
     * Mapea cada fila de estadísticas a las columnas del Excel.
     *
     * @param  mixed  $fila  Registro de estadística agrupada
     * @return array Fila mapeada con formato
     */
    public function map($fila): array
    {
        return [
            $fila->nom_sede ?? '',
            $fila->mes_periodo ?? '',
            $fila->modalidad ?? '',
            $fila->tipo_metrica ?? '',
            $fila->unidad_medida ?? 'mGy',
            (int) ($fila->total_estudios ?? 0),
            (float) ($fila->dosis_promedio ?? 0),
            (float) ($fila->dosis_minima ?? 0),
            (float) ($fila->dosis_maxima ?? 0),
            $fila->limite_nrd !== null ? (float) $fila->limite_nrd : 'N/A',
            $fila->valor_alerta !== null ? (float) $fila->valor_alerta : 'N/A',
            $fila->estado_regulatorio ?? 'CONFORME (SEGURO)',
        ];
    }

    /**
     * Define los anchos óptimos de columna para lectura clínica.
     *
     * @return array Anchos por columna
     */
    public function columnWidths(): array
    {
        return [
            'A' => 28, // Sede
            'B' => 16, // Mes
            'C' => 14, // Modalidad
            'D' => 16, // Tipo Métrica
            'E' => 14, // Unidad
            'F' => 16, // Total Estudios
            'G' => 18, // Dosis Promedio
            'H' => 16, // Dosis Mínima
            'I' => 16, // Dosis Máxima
            'J' => 22, // Límite NRD
            'K' => 22, // Alerta Preventiva
            'L' => 26, // Estado
        ];
    }

    /**
     * Aplica el diseño corporativo, tipografía clínica y colores semánticos de alerta.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo activa
     * @return array Configuración de estilos
     */
    public function styles(Worksheet $sheet): array
    {
        // 1. Título Corporativo Principal
        $sheet->setCellValue('A1', 'SALUD TOLIMA IPS - INFORME CONSOLIDADO DE DOSIMETRÍA RADIOLÓGICA');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 14,
                'name' => 'Segoe UI',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Subtítulo Regulatorio y Metadatos de Filtro
        $metaTexto = "Sede: {$this->filtros['nom_sede']}  |  Modalidad: {$this->filtros['modalidad']}  |  Métrica: {$this->filtros['tipo_metrica']}  |  Período: {$this->filtros['fecha_desde']} al {$this->filtros['fecha_hasta']}  |  Emisión: {$this->filtros['fecha_generacion']}";
        $sheet->setCellValue('A2', $metaTexto);
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => false,
                'color' => ['rgb' => '333333'],
                'size' => 10,
                'name' => 'Segoe UI',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // 3. Tarjetas / Bloque de KPIs Ejecutivos (Filas 4 y 5)
        $sheet->setCellValue('A4', 'TOTAL ESTUDIOS');
        $sheet->setCellValue('A5', number_format($this->kpis['total_estudios'] ?? 0));
        $sheet->mergeCells('A4:C4');
        $sheet->mergeCells('A5:C5');

        $sheet->setCellValue('D4', 'EQUIPOS EVALUADOS');
        $sheet->setCellValue('D5', (string) ($this->kpis['total_equipos'] ?? 0));
        $sheet->mergeCells('D4:F4');
        $sheet->mergeCells('D5:F5');

        $sheet->setCellValue('G4', 'CUMPLIMIENTO NORMATIVO');
        $sheet->setCellValue('G5', ($this->kpis['cumplimiento_pct'] ?? 100).'% Conforme');
        $sheet->mergeCells('G4:I4');
        $sheet->mergeCells('G5:I5');

        $sheet->setCellValue('J4', 'SUPERACIONES NRD CRÍTICAS');
        $sheet->setCellValue('J5', (string) ($this->kpis['total_superaciones'] ?? 0));
        $sheet->mergeCells('J4:L4');
        $sheet->mergeCells('J5:L5');

        // Estilos para las tarjetas KPI
        $sheet->getStyle('A4:L4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F3F5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A5:L5')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '1A2274']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1A2274']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(24);

        // Si hay superaciones críticas, colorear la tarjeta de alerta en rojo suave
        if (($this->kpis['total_superaciones'] ?? 0) > 0) {
            $sheet->getStyle('J4:L4')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']],
                'font' => ['color' => ['rgb' => '721C24']],
            ]);
            $sheet->getStyle('J5:L5')->applyFromArray([
                'font' => ['color' => ['rgb' => '721C24']],
            ]);
        }

        // 4. Encabezados de la Tabla de Datos (Fila 8)
        $sheet->getStyle('A8:L8')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
                'name' => 'Segoe UI',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '203A43'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']],
            ],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(28);

        // 5. Estilos de las Filas de Datos (Fila 9 en adelante)
        $filaFin = 8 + $this->totalFilas;
        if ($this->totalFilas > 0) {
            // Alineación de datos
            $sheet->getStyle("A9:A{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("B9:E{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F9:I{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J9:K{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L9:L{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Formato numérico de dosis con 2 decimales
            $sheet->getStyle("G9:K{$filaFin}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("F9:F{$filaFin}")->getNumberFormat()->setFormatCode('#,##0');

            // Bordes finos de la grilla
            $sheet->getStyle("A9:L{$filaFin}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']],
                ],
            ]);

            // Resaltado semántico condicional por estado en columna L
            for ($r = 9; $r <= $filaFin; $r++) {
                $estado = (string) $sheet->getCell("L{$r}")->getValue();
                if (str_contains($estado, 'CRÍTICO')) {
                    $sheet->getStyle("L{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '721C24']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']],
                    ]);
                } elseif (str_contains($estado, 'ALERTA')) {
                    $sheet->getStyle("L{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '856404']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
                    ]);
                } else {
                    $sheet->getStyle("L{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '155724']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
                    ]);
                }
            }
        }

        return [];
    }
}
