<?php

namespace App\Exports;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoMedico;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export de Plantilla 2: Contratación de Pago a Médicos Lectores.
 *
 * Basado en la estructura de TARIFAS DR MARIN.xlsx con metadatos:
 * - A1: Título: TARIFAS LECTURA [NOMBRE MÉDICO]
 * - A2: ID del Contrato (contrato_id) | B2: Etiqueta "ID CONTRATO"
 * - A3: Sede del Contrato | B3: Etiqueta "SEDE"
 * - A4: ID del Médico (medico_id) | B4: Nombre Completo del Médico
 * - A5: Fecha Inicio | B5: Fecha Fin | C5: "VIGENCIA (INICIO - FIN)"
 * - Fila 6: Divisoria vacía
 * - Fila 7: Encabezados (CUPS, ACTIVIDAD / PRODUCTO, CONSULTA EXTERNA, URGENCIAS / HOSP)
 * - Filas 8+: Productos exigibles con sus valores por lectura para el médico
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-10
 */
class ContratoMedicoPlantillaExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles
{
    /**
     * Contrato cargado con sede.
     */
    protected ?Contrato $contrato = null;

    /**
     * Usuario médico seleccionado.
     */
    protected ?User $medico = null;

    /**
     * Mapa de tarifas actuales del médico [producto_id => ['consulta' => float, 'urgencias' => float]].
     *
     * @var array<int, array{consulta: float, urgencias: float}>
     */
    protected array $tarifasActuales = [];

    /**
     * Constructor del export de tarifas de médico lector.
     *
     * @param  int  $contratoId  Identificador del contrato.
     * @param  int  $medicoId  Identificador del médico.
     */
    public function __construct(protected int $contratoId, protected int $medicoId)
    {
        $this->contrato = Contrato::with('sede')->find($contratoId);
        $this->medico = User::find($medicoId);

        $registros = ContratoMedico::where('contrato_id', $contratoId)
            ->where('medico_id', $medicoId)
            ->where('estado', 1)
            ->with('contratoProducto')
            ->get();

        foreach ($registros as $cm) {
            $prodId = $cm->contratoProducto->producto_id ?? null;
            if ($prodId) {
                $this->tarifasActuales[$prodId] = [
                    'consulta' => (float) $cm->valor_consulta,
                    'urgencias' => (float) $cm->valor_urgencias,
                ];
            }
        }
    }

    /**
     * Celda donde inicia la tabla de datos (encabezados en fila 7).
     */
    public function startCell(): string
    {
        return 'A7';
    }

    /**
     * Consulta el catálogo de productos exigibles que requieren lectura médica.
     *
     * @return Collection<int, ConfigProducto>
     */
    public function collection()
    {
        return ConfigProducto::where('requi_lectura', 1)
            ->where('estado', 1)
            ->orderBy('cod_cups')
            ->get(['id', 'cod_cups', 'nom_produc']);
    }

    /**
     * Encabezados de la tabla de tarifas médicas (fila 7).
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'CUPS',
            'ACTIVIDAD / PRODUCTO',
            'CONSULTA EXTERNA',
            'URGENCIAS / HOSP',
        ];
    }

    /**
     * Mapea cada producto a una fila con sus valores de consulta y urgencias.
     *
     * @param  ConfigProducto  $producto
     * @return array<int, mixed>
     */
    public function map($producto): array
    {
        $tarifa = $this->tarifasActuales[$producto->id] ?? null;

        return [
            $producto->cod_cups ?? '',
            $producto->nom_produc ?? '',
            $tarifa ? $tarifa['consulta'] : '',
            $tarifa ? $tarifa['urgencias'] : '',
        ];
    }

    /**
     * Anchos de columna.
     *
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 55,
            'C' => 22,
            'D' => 22,
        ];
    }

    /**
     * Estilos aplicados a la fila de encabezados (fila 7).
     *
     * @return array<int|string, mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            7 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A2274']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    /**
     * Inyección de metadatos estrictos en AfterSheet.
     *
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sede = $this->contrato?->sede;
                $sedeNombre = $sede instanceof ConfigSede ? $sede->nom_sede : 'GENERAL';
                $sedeId = $this->contrato ? $this->contrato->sede_id : 0;
                $medicoNombre = $this->medico instanceof User ? $this->medico->name : 'MÉDICO';
                $inicio = $this->contrato?->fecha_inicio ? $this->contrato->fecha_inicio->format('Y-m-d') : '';
                $fin = $this->contrato?->fecha_fin ? $this->contrato->fecha_fin->format('Y-m-d') : '';

                // Fila 1: Banner de título institucional
                $sheet->mergeCells('A1:D1');
                $sheet->setCellValue('A1', 'TARIFAS LECTURA - '.mb_strtoupper($medicoNombre));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A2274']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Fila 2: ID del Contrato (A2 = ID numérico para validación estricta)
                $sheet->setCellValue('A2', (int) $this->contratoId);
                $sheet->setCellValue('B2', 'ID CONTRATO (METADATO)');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1A2274']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('B2')->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '6c757d'], 'size' => 9],
                ]);

                // Fila 3: Sede del Contrato
                $sheet->setCellValue('A3', "{$sedeId} - {$sedeNombre}");
                $sheet->setCellValue('B3', 'SEDE ASISTENCIAL');
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                ]);
                $sheet->getStyle('B3')->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '6c757d'], 'size' => 9],
                ]);

                // Fila 4: ID del Médico en A4 y Nombre Completo en B4 (¡LECTURA ESTRICTA EN IMPORTACIÓN!)
                $sheet->setCellValue('A4', (int) $this->medicoId);
                $sheet->setCellValue('B4', $medicoNombre);
                $sheet->setCellValue('C4', 'ID Y NOMBRE MÉDICO');
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1A2274']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('B4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                ]);
                $sheet->getStyle('C4')->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '6c757d'], 'size' => 9],
                ]);

                // Fila 5: Vigencia Inicio y Fin
                $sheet->setCellValue('A5', $inicio);
                $sheet->setCellValue('B5', $fin);
                $sheet->setCellValue('C5', 'VIGENCIA (INICIO - FIN)');
                $sheet->getStyle('A5:B5')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('C5')->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '6c757d'], 'size' => 9],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Bordes para bloque de metadatos (A2:D5)
                $sheet->getStyle('A2:D5')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D4DC']]],
                ]);

                // Formato de moneda para columnas C y D (desde fila 8)
                $totalFilas = $sheet->getHighestRow();
                if ($totalFilas >= 8) {
                    $sheet->getStyle("C8:D{$totalFilas}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_INTEGER);
                    $sheet->getStyle("A8:A{$totalFilas}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("A7:D{$totalFilas}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN)
                        ->setColor(new Color('D0D4DC'));
                }
            },
        ];
    }
}
