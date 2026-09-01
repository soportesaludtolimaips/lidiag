<?php

namespace App\Exports;

use App\Models\Contratacion\Contrato;
use Carbon\Carbon;
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
 * Export de la plantilla de detalle de un contrato (para cargar médicos-producto).
 * Genera un Excel con la estructura de Plantilla_Cotrato_Detalle.xlsx:
 * - Fila 1: Título
 * - Fila 2: Sede: {nom_sede} | Inicio: {fecha} | Contrato ID: {id}  (celda C2 con el ID)
 * - Fila 3: Encabezados (Código CUPS | Producto | Valor Consulta | Valor Urgencias)
 * - Filas 4+: Productos configurados en el contrato, valores vacíos para diligenciar
 *
 * El médico destino se selecciona en el modal al momento de importar.
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-24
 */
class ContratoDetallePlantillaExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles
{
    /**
     * Constructor del export.
     *
     * @param  int  $contratoId  Identificador del contrato cuyo detalle se va a cargar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function __construct(protected int $contratoId) {}

    /**
     * Consulta los productos configurados en el contrato con su nombre y código CUPS.
     *
     * @returns \Illuminate\Support\Collection<int, mixed> Productos del contrato ordenados por código CUPS.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function collection()
    {
        return Contrato::findOrFail($this->contratoId)
            ->productos()
            ->with('producto:id,cod_cups,nom_produc')
            ->get()
            ->sortBy(fn ($cp) => $cp->producto->cod_cups ?? '')
            ->values();
    }

    /**
     * Encabezados de las columnas del Excel (fila 3).
     *
     * @return array<int, string> Nombres de columnas según plantilla de detalle.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function headings(): array
    {
        return [
            'Código CUPS',
            'Producto',
            'Valor Consulta',
            'Valor Urgencias',
        ];
    }

    /**
     * Mapea cada producto del contrato a una fila del Excel.
     * Las columnas de valores quedan vacías para que el usuario las diligencie
     * para el médico seleccionado.
     *
     * @param  object  $contratoProducto  Registro de contratos_productos con relación producto.
     * @return array<int, mixed> Fila con [codigo_cups, nombre, '', ''].
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function map($contratoProducto): array
    {
        return [
            $contratoProducto->producto->cod_cups ?? '',
            $contratoProducto->producto->nom_produc ?? '',
            '',
            '',
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
     * @date 2026-08-24
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            3 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A2274']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    /**
     * Registra eventos de Maatwebsite/Excel.
     * AfterSheet: escribe el título (fila 1) y la info del contrato (fila 2,
     * con el Contrato ID en la celda C2 que valida la importación).
     *
     * @returns array<string, callable> Eventos registrados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // ── FILA 1: TÍTULO ─────────────────────────────────────
                $sheet->setCellValue('A1', 'Plantilla Importación de Detalle de Médico - Contrato');
                $sheet->mergeCells('A1:D1');
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

                // ── FILA 2: INFO DEL CONTRATO (C2 = Contrato ID, validado al importar) ──
                $contrato = Contrato::with('sede:id,nom_sede')->find($this->contratoId);
                $nomSede = $contrato?->sede?->nom_sede ?? 'N/A';
                $inicio = $contrato?->fecha_inicio instanceof Carbon
                    ? $contrato->fecha_inicio->format('d/m/Y')
                    : 'N/A';

                $sheet->setCellValue('A2', "Sede: {$nomSede}");
                $sheet->setCellValue('B2', "Inicio: {$inicio}");
                $sheet->setCellValue('C2', "Contrato ID: {$this->contratoId}");

                $sheet->getStyle('A2:D2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2'],
                    ],
                ]);

                // ── BORDES DE DATOS ────────────────────────────────────
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 3) {
                    $sheet->getStyle('A4:D'.$highestRow)->applyFromArray([
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
     * @date 2026-08-24
     */
    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 65,
            'C' => 18,
            'D' => 18,
        ];
    }

    /**
     * Celda de inicio: encabezados en fila 3 (filas 1-2 son título e info).
     *
     * @return string Celda de inicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function startCell(): string
    {
        return 'A3';
    }
}
