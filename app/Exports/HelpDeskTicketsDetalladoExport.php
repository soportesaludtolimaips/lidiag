<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HelpDeskTicketsDetalladoExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    /** @var Collection */
    protected $collection;

    /** @param Collection $collection */
    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Folio',
            'Título',
            'Estado',
            'Prioridad',
            'Orden prioridad',
            'Categoría',
            'Pendiente de',
            'Sede',
            'Solicitante',
            'Técnico',
            'Creado',
            'Resuelto',
            'Cerrado',
            'Horas resolución',
        ];
    }

    public function map($row): array
    {
        $horas = null;
        if (! empty($row->segundos_resolucion)) {
            $horas = round(((float) $row->segundos_resolucion) / 3600, 2);
        }

        return [
            $row->id ?? 'N/A',
            $row->folio ?? 'N/A',
            $row->titulo ?? 'N/A',
            $row->estado ?? 'N/A',
            $row->prioridad ?? 'N/A',
            $row->prioridad_orden ?? 'N/A',
            $row->categoria ?? 'N/A',
            $row->pendiente_de ?? 'N/A',
            $row->sede ?? 'N/A',
            $row->solicitante ?? 'N/A',
            $row->tecnico ?? 'N/A',
            $row->created_at ?? 'N/A',
            $row->resolved_at ?? 'N/A',
            $row->closed_at ?? 'N/A',
            $horas ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setCellValue('A1', 'Helpdesk - Tickets (Detallado)');
        $sheet->mergeCells('A1:O1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A3:O3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A4:O'.($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return [
            1 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 20,
            'C' => 40,
            'D' => 14,
            'E' => 16,
            'F' => 14,
            'G' => 20,
            'H' => 14,
            'I' => 18,
            'J' => 22,
            'K' => 22,
            'L' => 18,
            'M' => 18,
            'N' => 18,
            'O' => 16,
        ];
    }

    public function startCell(): string
    {
        return 'A3';
    }
}
