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
 * Export de la plantilla de detalle de un contrato (para cargar médicos-producto).
 * Genera un Excel con la estructura de Plantilla_Cotrato_Detalle.xlsx:
 * - Fila 1: Título
 * - Fila 2: Sede: {nom_sede} | Inicio: {fecha} | Contrato ID: {id}  (celda C2 con el ID)
 * - Fila 3: Encabezados (Código CUPS | Producto | Valor Consulta | Valor Urgencias | Fecha Ingreso)
 * - Filas 4+: Productos del catálogo que requieren lectura, valores vacíos para diligenciar
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
     * Consulta el catálogo de productos que requieren lectura.
     *
     * La plantilla de detalle es la única vía de carga del módulo, por lo
     * que lista el catálogo completo (y no solo los productos del contrato)
     * para que un contrato recién creado también descargue filas. Las
     * columnas de valor se dejan vacías y las filas sin diligenciar se
     * ignoran al importar.
     *
     * @returns \Illuminate\Support\Collection<int, mixed> Productos exigibles ordenados por código CUPS.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function collection()
    {
        return ConfigProducto::where('requi_lectura', 1)
            ->where('estado', 1)
            ->orderBy('cod_cups')
            ->get(['id', 'cod_cups', 'nom_produc']);
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
            'Fecha Ingreso',
        ];
    }

    /**
     * Mapea cada producto del catálogo a una fila del Excel.
     * Las columnas de valores quedan vacías para que el usuario las diligencie
     * para el médico seleccionado; las filas sin diligenciar se ignoran al
     * importar.
     *
     * @param  object  $producto  Registro de config_productos.
     * @return array<int, mixed> Fila con [codigo_cups, nombre, '', '', ''].
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function map($producto): array
    {
        return [
            $producto->cod_cups ?? '',
            $producto->nom_produc ?? '',
            '',
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

                // ── FILA 2: INFO DEL CONTRATO (C2 = Contrato ID, validado al importar) ──
                $contrato = Contrato::with('sede:id,nom_sede')->find($this->contratoId);
                $nomSede = $contrato?->sede?->nom_sede ?? 'N/A';
                $inicio = $contrato?->fecha_inicio instanceof Carbon
                    ? $contrato->fecha_inicio->format('d/m/Y')
                    : 'N/A';

                $sheet->setCellValue('A2', "Sede: {$nomSede}");
                $sheet->setCellValue('B2', "Inicio: {$inicio}");
                $sheet->setCellValue('C2', "Contrato ID: {$this->contratoId}");

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

                // ── BORDES DE DATOS ────────────────────────────────────
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
            'E' => 18,
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
