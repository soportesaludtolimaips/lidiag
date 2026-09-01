<?php

namespace App\Exports;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Contratacion\ContratoProducto;
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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export de la plantilla general de productos para contratación.
 * Genera un Excel con los productos activos que requieren lectura (requi_lectura = 1):
 * - Fila 1: Título
 * - Fila 2: Encabezados (CODIGO | ACTIVIDAD | Tarifa Actividad)
 * - Filas 3+: Productos activos con lectura de config_productos
 *
 * Si se recibe un contrato_id, precarga en "Tarifa Actividad" el valor
 * contratado existente en contratos_productos; de lo contrario queda en 0
 * para que el usuario lo diligencie antes de importar.
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */
class ContratoPlantillaExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles
{
    /**
     * ID opcional del contrato para precargar valores contratados existentes.
     */
    protected ?int $contratoId;

    /**
     * Mapa producto_id => valor_contrato del contrato (si aplica).
     *
     * @var array<int, float>
     */
    protected array $valoresContrato = [];

    /**
     * Constructor del export.
     *
     * @param  int|null  $contratoId  Identificador opcional del contrato para precargar tarifas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function __construct(?int $contratoId = null)
    {
        $this->contratoId = $contratoId;

        if ($contratoId !== null) {
            $this->valoresContrato = ContratoProducto::where('contrato_id', $contratoId)
                ->pluck('valor_contrato', 'producto_id')
                ->toArray();
        }
    }

    /**
     * Consulta los productos activos del catálogo config_productos
     * que requieren lectura de estudios (requi_lectura = 1).
     *
     * @returns \Illuminate\Support\Collection<int, mixed> Colección de productos activos con lectura ordenada por código CUPS.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function collection()
    {
        return ConfigProducto::where('requi_lectura', 1)
            ->where('estado', 1)
            ->select('id', 'cod_cups', 'nom_produc')
            ->orderBy('cod_cups')
            ->get();
    }

    /**
     * Encabezados de las columnas del Excel (fila 2).
     *
     * @returns array<int, string> Nombres de columnas según formato de referencia.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function headings(): array
    {
        return [
            'CODIGO',
            'ACTIVIDAD',
            'Tarifa Actividad',
        ];
    }

    /**
     * Mapea cada producto a una fila del Excel.
     * La columna Tarifa se precarga del contrato si existe, si no queda en 0.
     *
     * @param  object  $producto  Modelo de config_productos.
     *
     * @returns array<int, mixed> Fila con [codigo, actividad, tarifa].
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function map($producto): array
    {
        return [
            $producto->cod_cups ?? '',
            $producto->nom_produc ?? '',
            (float) ($this->valoresContrato[$producto->id] ?? 0),
        ];
    }

    /**
     * Estilos del Excel: encabezados en negrita con fondo.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo.
     * @return array<string, array<string, mixed>> Estilos aplicados por fila.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A2274']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    /**
     * Registra eventos de Maatwebsite/Excel.
     * AfterSheet: escribe el título en la fila 1 y aplica bordes a los datos.
     *
     * @returns array<string, callable> Eventos registrados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // ── FILA 1: TÍTULO ─────────────────────────────────────
                $sheet->setCellValue('A1', 'PLANTILLA GENERAL DE PRODUCTOS - CONTRATACIÓN');
                $sheet->mergeCells('A1:C1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 14,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1A2274'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // ── BORDES DE DATOS ────────────────────────────────────
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 2) {
                    $sheet->getStyle('A3:C'.$highestRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                }
            },
        ];
    }

    /**
     * Anchos de columna del Excel.
     *
     * @return array<string, int> Ancho por columna.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 70,
            'C' => 20,
        ];
    }

    /**
     * Celda de inicio: encabezados en fila 2 (la fila 1 es el título).
     *
     * @return string Celda de inicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function startCell(): string
    {
        return 'A2';
    }
}
