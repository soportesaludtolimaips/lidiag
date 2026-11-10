<?php

namespace App\Exports;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoProducto;
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
 * Export de Plantilla 1: Contratación de Servicios con Entidades (Aseguradoras/Hospitales).
 *
 * Basado en la estructura de TARIFAS ST COBRA A HOSP CHAPARRAL.xlsx con metadatos:
 * - A1: Título institucional banner
 * - A2: ID del Contrato (contrato_id) | B2: Etiqueta "ID CONTRATO"
 * - A3: Sede del Contrato | B3: Etiqueta "SEDE"
 * - A4: Fecha Inicio | B4: Fecha Fin | C4: "VIGENCIA (INICIO - FIN)"
 * - Fila 5: Divisoria vacía
 * - Fila 6: Encabezados (CUPS, ACTIVIDAD / PRODUCTO, VALOR TARIFA)
 * - Filas 7+: Productos del catálogo institucional con sus tarifas configuradas
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-10
 */
class ContratoEntidadPlantillaExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles
{
    /**
     * Contrato cargado con sede.
     */
    protected ?Contrato $contrato = null;

    /**
     * Mapa de tarifas existentes del contrato [producto_id => valor_contrato].
     *
     * @var array<int, float>
     */
    protected array $tarifasActuales = [];

    /**
     * Constructor del export de tarifas de entidad.
     *
     * @param  int  $contratoId  Identificador del contrato.
     */
    public function __construct(protected int $contratoId)
    {
        $this->contrato = Contrato::with('sede')->find($contratoId);

        $this->tarifasActuales = ContratoProducto::where('contrato_id', $contratoId)
            ->where('estado', 1)
            ->pluck('valor_contrato', 'producto_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    /**
     * Celda donde inicia la tabla de datos (encabezados en fila 6).
     */
    public function startCell(): string
    {
        return 'A6';
    }

    /**
     * Consulta el catálogo de productos que requieren lectura y están activos.
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
     * Encabezados de la tabla de tarifas (fila 6).
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'CUPS',
            'ACTIVIDAD / PRODUCTO',
            'VALOR TARIFA',
        ];
    }

    /**
     * Mapea cada producto a una fila de la plantilla.
     *
     * @param  ConfigProducto  $producto  Producto del catálogo.
     * @return array<int, mixed>
     */
    public function map($producto): array
    {
        $valor = $this->tarifasActuales[$producto->id] ?? '';

        return [
            $producto->cod_cups ?? '',
            $producto->nom_produc ?? '',
            $valor !== '' ? (float) $valor : '',
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
        ];
    }

    /**
     * Estilos aplicados a la fila de encabezados.
     *
     * @return array<int|string, mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            6 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A2274']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    /**
     * Eventos para inyectar cabecera de metadatos estandarizada.
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
                $inicio = $this->contrato?->fecha_inicio ? $this->contrato->fecha_inicio->format('Y-m-d') : '';
                $fin = $this->contrato?->fecha_fin ? $this->contrato->fecha_fin->format('Y-m-d') : '';

                // Fila 1: Banner de título
                $sheet->mergeCells('A1:C1');
                $sheet->setCellValue('A1', 'TARIFAS QUE SALUD TOLIMA COBRA A '.mb_strtoupper($sedeNombre));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A2274']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Fila 2: ID del Contrato (A2 = ID para lectura estricta en importación)
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

                // Fila 4: Vigencia Inicio y Fin
                $sheet->setCellValue('A4', $inicio);
                $sheet->setCellValue('B4', $fin);
                $sheet->setCellValue('C4', 'VIGENCIA (INICIO - FIN)');
                $sheet->getStyle('A4:B4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('C4')->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '6c757d'], 'size' => 9],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Bordes para bloque de metadatos (A2:C4)
                $sheet->getStyle('A2:C4')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D4DC']]],
                ]);

                // Formato de moneda para columna C (desde fila 7 hasta el final)
                $totalFilas = $sheet->getHighestRow();
                if ($totalFilas >= 7) {
                    $sheet->getStyle("C7:C{$totalFilas}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_INTEGER);
                    $sheet->getStyle("A7:A{$totalFilas}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("A6:C{$totalFilas}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN)
                        ->setColor(new Color('D0D4DC'));
                }
            },
        ];
    }
}
