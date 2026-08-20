<?php

namespace App\Exports;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Contratacion\Contrato;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export de plantilla para importar detalles de contratos.
 * Genera un Excel con:
 * - Filas 1-2: Información del contrato (sede, fechas, observaciones)
 * - Fila 3: Encabezados de columnas
 * - Fila 4+: Productos CUPS con requi_lectura=1 (vacíos para llenar)
 * - Columna B (Sede): Data Validation con lista de sedes activas
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */
class ContratoPlantillaExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    use RegistersEventListeners;

    /**
     * @var Contrato|null Contrato cargado con relación sede (accesible estáticamente en afterSheet)
     */
    public static ?Contrato $contratoStatic = null;

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
        self::$contratoStatic = Contrato::with('sede:id,cod_sede,nom_sede')->find($contratoId);
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
            'Sede',
            'Valor Contrato',
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
            '', // Sede (dropdown)
            '', // Valor Contrato
            '', // Valor Consulta
            '', // Valor Urgencias
        ];
    }

    /**
     * Estilos del Excel: título, info contrato, headers, bordes.
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
        // Fila 1: Título general
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

        // Fila 2: Info del contrato (llenada en afterSheet)
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

        // Fila 3: Encabezados de columnas
        $sheet->getStyle('A3:E3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Bordes para todas las celdas de datos
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

        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
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
            'B' => 25,
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
        return 'A4';
    }

    /**
     * Evento AfterSheet: llena info del contrato en fila 2 y agrega
     * Data Validation (desplegable) en columna B para las sedes.
     *
     * @param  AfterSheet  $event  Evento de maatwebsite/excel.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public static function afterSheet(AfterSheet $event): void
    {
        $sheet = $event->sheet;

        // ─── INFO DEL CONTRATO EN FILA 2 ────────────────────────────────
        $contrato = self::$contratoStatic;

        if ($contrato) {
            $nomSede = $contrato->sede->nom_sede ?? 'N/A';
            $fechaInicio = $contrato->fecha_inicio ? $contrato->fecha_inicio->format('d/m/Y') : 'N/A';
            $fechaFin = $contrato->fecha_fin ? $contrato->fecha_fin->format('d/m/Y') : 'N/A';
            $observaciones = $contrato->observaciones ?? '';

            $sheet->setCellValue('A2', "Sede: {$nomSede}");
            $sheet->setCellValue('B2', "Inicio: {$fechaInicio}");
            $sheet->setCellValue('C2', "Fin: {$fechaFin}");
            $sheet->setCellValue('D2', "Contrato ID: {$contrato->id}");
            $sheet->setCellValue('E2', $observaciones ? "Obs: {$observaciones}" : '');
        }

        // ─── DATA VALIDATION: SEDE EN COLUMNA B ─────────────────────────
        $sedes = ConfigSede::where('estado', 1)
            ->orderBy('nom_sede')
            ->pluck('nom_sede')
            ->toArray();

        if (! empty($sedes)) {
            $highestRow = $sheet->getHighestRow();
            $lastRow = max($highestRow, 100);

            $validation = new DataValidation;
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Sede inválida');
            $validation->setError('Seleccione una sede de la lista desplegable.');
            $validation->setPromptTitle('Seleccionar Sede');
            $validation->setPrompt('Seleccione la sede a la cual pertenece este contrato.');
            $validation->setFormula1('"'.implode(',', $sedes).'"');

            $sheet->setDataValidation("B4:B{$lastRow}", $validation);
        }
    }
}
