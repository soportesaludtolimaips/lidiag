<?php

namespace App\Exports\Dosimetria\Sheets;

use Carbon\Carbon;
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
 * Hoja de Registros Detallados e Histórico de Telemetría Dosimétrica Individual.
 *
 * Flujo del proceso:
 * 1. Presenta encabezado institucional con metadatos de trazabilidad.
 * 2. Mapea cada disparo radiológico con sus parámetros físicos (kVp, mAs, DAP, CTDIvol).
 * 3. Expone el hash criptográfico SHA-256 del paciente cumpliendo la norma ISO/IEC 27001.
 * 4. Aplica contrastes de evaluación individuales frente a los Niveles de Referencia Nacionales.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-26
 */
class DosimetriaDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected array $filtros;

    protected int $consecutivo = 0;

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
        $this->totalFilas = count($datosExportacion['detalles'] ?? []);
    }

    /**
     * Título de la pestaña en la hoja de cálculo.
     *
     * @return string Nombre de la pestaña
     */
    public function title(): string
    {
        return 'Registros Detallados';
    }

    /**
     * Celda inicial de los encabezados de la tabla de auditoría.
     *
     * @return string Celda de inicio
     */
    public function startCell(): string
    {
        return 'A4';
    }

    /**
     * Retorna la colección de registros individuales de exposición.
     *
     * @return Collection Colección de mediciones
     */
    public function collection()
    {
        return collect($this->datosExportacion['detalles'] ?? []);
    }

    /**
     * Encabezados de la grilla detallada.
     *
     * @return array Lista de encabezados de columna
     */
    public function headings(): array
    {
        return [
            '#',
            'Sede',
            'Modalidad',
            'Equipo Emisor',
            'AET Title',
            'Fecha y Hora Estudio',
            'Accession No.',
            'Descripción del Procedimiento',
            'Métrica',
            'Dosis Medida',
            'Unidad',
            'Límite NRD',
            'Evaluación',
            'Paciente Anonimizado (SHA-256)',
            'Study Instance UID (DICOM)',
        ];
    }

    /**
     * Mapea cada registro individual de dosimetría a la fila del archivo Excel.
     *
     * @param  mixed  $reg  Registro de dosis individual
     * @return array Fila mapeada con formato
     */
    public function map($reg): array
    {
        $this->consecutivo++;

        $fechaFormateada = $reg->fecha_estudio
            ? Carbon::parse($reg->fecha_estudio)->format('d/m/Y H:i:s')
            : '';

        return [
            $this->consecutivo,
            $reg->nom_sede ?? '',
            $reg->modalidad ?? '',
            $reg->equipo_nombre ?? '',
            $reg->aet_title ?? '',
            $fechaFormateada,
            $reg->accession_no ?? 'N/A',
            $reg->study_desc ?? 'Sin descripción',
            $reg->tipo_metrica ?? '',
            (float) ($reg->valor_dosis ?? 0),
            $reg->unidad_medida ?? 'mGy',
            $reg->limite_nrd !== null ? (float) $reg->limite_nrd : 'N/A',
            $reg->estado_evaluacion ?? 'CONFORME',
            $reg->patient_id_hashed ?? '',
            $reg->study_uid ?? '',
        ];
    }

    /**
     * Anchos de columna óptimos para auditoría técnica y biomédica.
     *
     * @return array Anchos por columna
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,  // Consecutivo
            'B' => 22, // Sede
            'C' => 12, // Modalidad
            'D' => 24, // Equipo Emisor
            'E' => 16, // AET Title
            'F' => 20, // Fecha Estudio
            'G' => 18, // Accession No
            'H' => 36, // Estudio Desc
            'I' => 14, // Métrica
            'J' => 16, // Valor Dosis
            'K' => 12, // Unidad
            'L' => 14, // Límite NRD
            'M' => 16, // Evaluación
            'N' => 32, // Patient ID Hash
            'O' => 38, // Study UID
        ];
    }

    /**
     * Aplica el diseño corporativo, fuentes y estilos visuales a la hoja de auditoría.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo activa
     * @return array Configuración de estilos
     */
    public function styles(Worksheet $sheet): array
    {
        // 1. Título Corporativo Principal
        $sheet->setCellValue('A1', 'SALUD TOLIMA IPS - REGISTRO DETALLADO DE EXPOSICIÓN A RADIACIÓN IONIZANTE');
        $sheet->mergeCells('A1:O1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 13,
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
        $sheet->getRowDimension(1)->setRowHeight(30);

        // 2. Subtítulo con Metadatos de Trazabilidad
        $metaTexto = "Trazabilidad Clínica Individual | Sede: {$this->filtros['nom_sede']} | Total Evaluaciones: {$this->totalFilas} | Período: {$this->filtros['fecha_desde']} al {$this->filtros['fecha_hasta']} | Protección PHI: ISO/IEC 27001";
        $sheet->setCellValue('A2', $metaTexto);
        $sheet->mergeCells('A2:O2');
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
        $sheet->getRowDimension(2)->setRowHeight(20);

        // 3. Encabezados de la Tabla de Datos (Fila 4)
        $sheet->getStyle('A4:O4')->applyFromArray([
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
        $sheet->getRowDimension(4)->setRowHeight(26);

        // 4. Formato de Celdas y Grilla de Datos (Fila 5 en adelante)
        $filaFin = 4 + $this->totalFilas;
        if ($this->totalFilas > 0) {
            $sheet->getStyle("A5:A{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B5:E{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("F5:G{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H5:H{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I5:I{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J5:L{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("M5:M{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N5:O{$filaFin}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Formato de números decimales de dosis (4 decimales para precisión en Gy*cm2 y mGy)
            $sheet->getStyle("J5:L{$filaFin}")->getNumberFormat()->setFormatCode('#,##0.0000');

            // Bordes finos
            $sheet->getStyle("A5:O{$filaFin}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']],
                ],
            ]);

            // Resaltado de evaluación condicional en columna M
            for ($r = 5; $r <= $filaFin; $r++) {
                $eval = (string) $sheet->getCell("M{$r}")->getValue();
                if ($eval === 'SUPERA NRD') {
                    $sheet->getStyle("M{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '721C24']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']],
                    ]);
                } elseif ($eval === 'ALERTA') {
                    $sheet->getStyle("M{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '856404']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
                    ]);
                } else {
                    $sheet->getStyle("M{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '155724']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
                    ]);
                }
            }
        }

        return [];
    }
}
