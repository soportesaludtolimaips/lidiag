<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HelpDeskTicketsDetalladoExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    /** @var array<string, mixed> */
    protected $filters;

    /** @param array<string, mixed> $filters */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $f = $this->filters;

        $q = DB::table('help_desk_tickets as t')
            ->leftJoin('help_desk_estados as e', 'e.id', '=', 't.estado_id')
            ->leftJoin('help_desk_categorias as c', 'c.id', '=', 't.categoria_id')
            ->leftJoin('help_desk_prioridades as p', 'p.id', '=', 't.prioridad_id')
            ->leftJoin('config_sedes as s', 's.id', '=', 't.sedes_id')
            ->leftJoin('users as u_sol', 'u_sol.id', '=', 't.quien_registra_id')
            ->leftJoin('users as u_asg', 'u_asg.id', '=', 't.quien_atiende_id')
            ->whereNull('t.deleted_at')
            ->whereBetween(DB::raw('DATE(t.created_at)'), [$f['fechaIni'], $f['fechaFin']])
            ->when($f['sedeId'] ?? null, fn ($qq) => $qq->where('t.sedes_id', (int) $f['sedeId']))
            ->when($f['estadoId'] ?? null, fn ($qq) => $qq->where('t.estado_id', (int) $f['estadoId']))
            ->when($f['categoriaId'] ?? null, fn ($qq) => $qq->where('t.categoria_id', (int) $f['categoriaId']))
            ->when($f['prioridadId'] ?? null, fn ($qq) => $qq->where('t.prioridad_id', (int) $f['prioridadId']))
            ->when($f['solicitanteId'] ?? null, fn ($qq) => $qq->where('t.quien_registra_id', (int) $f['solicitanteId']))
            ->when($f['tecnicoId'] ?? null, fn ($qq) => $qq->where('t.quien_atiende_id', (int) $f['tecnicoId']))
            ->select(
                't.id',
                't.folio',
                't.titulo',
                'e.nombre as estado',
                'p.nombre as prioridad',
                'p.orden as prioridad_orden',
                'c.nombre as categoria',
                't.pendiente_de',
                's.nom_sede as sede',
                'u_sol.name as solicitante',
                'u_asg.name as tecnico',
                't.created_at',
                't.resolved_at',
                't.closed_at',
                DB::raw('TIMESTAMPDIFF(SECOND, t.created_at, t.resolved_at) as segundos_resolucion')
            )
            ->orderByDesc('t.created_at')
            ->get();

        return $q;
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
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A3:O3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A4:O'.($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
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
