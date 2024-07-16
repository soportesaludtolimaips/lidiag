<?php

namespace App\Exports;

use App\Models\Estudio\Estudio;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProduccionDetalladaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomStartCell
{
    protected $fechaInicio;
    protected $fechaFin;
    protected $sedeId;

    public function __construct($fechaInicio, $fechaFin, $sedeId)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
    }

    public function collection()
    {
        return Estudio::with([
            'paciente',
            'medico',
            'quienRegistro',
            'sede',
            'prioridad',
            'productos',
            'productos.transcriptor',
            'diagnosticos'
        ])
            ->whereBetween('fec_estudio', [$this->fechaInicio, $this->fechaFin])
            ->where('sede_id', $this->sedeId)
            ->limit(12)
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Sede',
            'Fecha de Estudio',
            'Fecha de Estudio',
            'Fecha de Lectura',
            'Atención',
            'Prioridad',
            'Quien Registró',
            'Médico',
            'Documento Paciente',
            'Nombre Paciente',
            'Código CUPS',
            'Nombre Producto',
            'Transcriptor',
            'EPS',
            'Contrato'
        ];
    }

    public function map($estudio): array
    {
        $producto = $estudio->productos->first();
        return [
            $estudio->id,
            optional($estudio->sede)->nom_sede, // Sede
            $estudio->fec_estudio,
            $estudio->fec_estudio,
            optional($producto)->fechor_lectura,
            $estudio->atencion,
            optional($estudio->prioridad)->nom_priori,
            optional($estudio->quienRegistro)->name,
            optional($estudio->medico)->name,
            optional($estudio->paciente)->num_docu,
            optional($estudio->paciente)->nombres,
            optional($producto)->cod_cups,
            optional($producto)->nom_produc,
            optional(optional($producto)->transcriptor)->name,
            $estudio->eps,
            $estudio->contrato,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Agrega el encabezado personalizado
        $sheet->setCellValue('A1', 'Salud Tolima - Reporte de Producción');
        $sheet->mergeCells('A1:P1'); // A1 to P1 to include the new column
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 14,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Establece el estilo para el encabezado de los datos
        $sheet->getStyle('A3:P3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Establece bordes para todas las celdas de datos
        $sheet->getStyle('A4:P' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return [
            1 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]], // Estilo para el encabezado de los datos
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 20, // Ancho para la columna de Sede
            'C' => 18,
            'D' => 18,
            'E' => 18,
            'F' => 9,
            'G' => 9,
            'H' => 20,
            'I' => 25,
            'J' => 20,
            'K' => 30,
            'L' => 12,
            'M' => 30,
            'N' => 25,
            'O' => 35,
            'P' => 35,
        ];
    }

    public function startCell(): string
    {
        return 'A3'; // Los datos comenzarán desde la celda A4
    }
}
