<?php

namespace App\Exports;

use App\Models\Configuracion\ConfigProducto;
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
 * Export de plantilla para importar detalles de contratos.
 * Genera un Excel con:
 * - Filas 1-2: Información del contrato (sede, fechas, observaciones)
 * - Fila 3: Encabezados de columnas (Código CUPS, Valor Contrato, Valor Consulta, Valor Urgencias)
 * - Filas 4+: Productos CUPS con requi_lectura=1 (vacíos para llenar)
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */
class ContratoPlantillaExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles
{
    /**
     * @var int ID del contrato para el cual se genera la plantilla
     */
    protected int $contratoId;

    /**
     * Constructor del export.
     *
     * @param  int  $contratoId  Identificador del contrato.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function __construct(int $contratoId)
    {
        $this->contratoId = $contratoId;
    }

    /**
     * Consulta los productos activos con requi_lectura=1.
     * Estos se listan en la plantilla para que el usuario los seleccione.
     *
     * @returns \Illuminate\Support\Collection Colección de productos CUPS.
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
     * Encabezados de las columnas del Excel (fila 3).
     *
     * @returns array<int, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function headings(): array
    {
        return [
            'Código CUPS',
            'Producto',
            'Valor Contratado',
            'Valor Consulta',
            'Valor Urgencias',
        ];
    }

    /**
     * Mapea cada producto a una fila del Excel.
     * Las columnas de valores quedan vacías para que el usuario las llene.
     *
     * @param  object  $producto  Modelo de config_productos.
     *
     * @returns array<int, mixed>
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
            '', // Valor Contrato
            '', // Valor Consulta
            '', // Valor Urgencias
        ];
    }

    /**
     * Estilos del Excel: headers, bordes.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo.
     *
     * @returns array
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
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
     * AfterSheet: escribe info del contrato (fila 1-2) y Data Validation de sedes.
     *
     * @returns array<string, callable>
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
                $sheet->setCellValue('A1', 'Plantilla Importación de Detalles de Contrato');
                $sheet->mergeCells('A1:E1');
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

                // ── FILA 2: INFO DEL CONTRATO ──────────────────────────
                $contrato = Contrato::with('sede:id,nom_sede')->find($this->contratoId);

                if ($contrato) {
                    $nomSede = $contrato->sede->nom_sede ?? 'N/A';
                    $fechaInicio = $contrato->fecha_inicio instanceof Carbon
                        ? $contrato->fecha_inicio->format('d/m/Y')
                        : 'N/A';
                    $fechaFin = $contrato->fecha_fin instanceof Carbon
                        ? $contrato->fecha_fin->format('d/m/Y')
                        : 'N/A';

                    $sheet->setCellValue('A2', "Sede: {$nomSede}");
                    $sheet->setCellValue('B2', "Inicio: {$fechaInicio}");
                    $sheet->setCellValue('C2', "Fin: {$fechaFin}");
                    $sheet->setCellValue('D2', "Contrato ID: {$contrato->id}");
                } else {
                    $sheet->setCellValue('A2', 'Sede: N/A');
                    $sheet->setCellValue('B2', 'Inicio: N/A');
                    $sheet->setCellValue('C2', 'Fin: N/A');
                    $sheet->setCellValue('D2', 'Contrato ID: N/A');
                }

                $sheet->getStyle('A2:E2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2'],
                    ],
                ]);

                // ── BORDES ─────────────────────────────────────────────
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 3) {
                    $sheet->getStyle('A4:E'.$highestRow)->applyFromArray([
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
     * @returns array<string, int>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 60,
            'C' => 18,
            'D' => 18,
            'E' => 18,
        ];
    }

    /**
     * Celda de inicio de los datos (después de título + info contrato + headers).
     *
     * @returns string
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function startCell(): string
    {
        return 'A3';
    }
}
