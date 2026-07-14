<?php

namespace App\Services\HelpDesk;

use App\Exports\HelpDeskTicketsDetalladoExport;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class HelpDeskReporteService extends BaseService
{
    private function normalizeNullable($value)
    {
        if ($value === null) {
            return null;
        }
        $v = is_string($value) ? trim($value) : $value;
        if ($v === '' || $v === '0' || $v === 0 || $v === 'null') {
            return null;
        }

        return $v;
    }

    public function normalizeFilters(array $raw): array
    {
        return [
            'fechaIni' => $raw['fechaIni'] ?? null,
            'fechaFin' => $raw['fechaFin'] ?? null,
            'sedeId' => $this->normalizeNullable($raw['sedeId'] ?? null),
            'estadoId' => $this->normalizeNullable($raw['estadoId'] ?? null),
            'categoriaId' => $this->normalizeNullable($raw['categoriaId'] ?? null),
            'prioridadId' => $this->normalizeNullable($raw['prioridadId'] ?? null),
            'solicitanteId' => $this->normalizeNullable($raw['solicitanteId'] ?? null),
            'tecnicoId' => $this->normalizeNullable($raw['tecnicoId'] ?? null),
        ];
    }

    private function baseQuery(array $filters)
    {
        return DB::table('help_desk_tickets as t')
            ->leftJoin('help_desk_estados as e', 'e.id', '=', 't.estado_id')
            ->leftJoin('help_desk_categorias as c', 'c.id', '=', 't.categoria_id')
            ->leftJoin('help_desk_prioridades as p', 'p.id', '=', 't.prioridad_id')
            ->leftJoin('config_sedes as s', 's.id', '=', 't.sedes_id')
            ->leftJoin('users as u_sol', 'u_sol.id', '=', 't.quien_registra_id')
            ->leftJoin('users as u_asg', 'u_asg.id', '=', 't.quien_atiende_id')
            ->whereNull('t.deleted_at')
            ->whereBetween(DB::raw('DATE(t.created_at)'), [$filters['fechaIni'], $filters['fechaFin']])
            ->when($filters['sedeId'], fn ($q) => $q->where('t.sedes_id', (int) $filters['sedeId']))
            ->when($filters['estadoId'], fn ($q) => $q->where('t.estado_id', (int) $filters['estadoId']))
            ->when($filters['categoriaId'], fn ($q) => $q->where('t.categoria_id', (int) $filters['categoriaId']))
            ->when($filters['prioridadId'], fn ($q) => $q->where('t.prioridad_id', (int) $filters['prioridadId']))
            ->when($filters['solicitanteId'], fn ($q) => $q->where('t.quien_registra_id', (int) $filters['solicitanteId']))
            ->when($filters['tecnicoId'], fn ($q) => $q->where('t.quien_atiende_id', (int) $filters['tecnicoId']));
    }

    public function indicadores(array $filters): array
    {
        $base = $this->baseQuery($filters);

        $totalesPorEstado = (clone $base)
            ->select('e.nombre as estado', DB::raw('COUNT(*) as total'))
            ->groupBy('e.nombre')
            ->orderByDesc('total')
            ->get();

        $total = (clone $base)->count();

        $avgHoras = (clone $base)
            ->whereNotNull('t.resolved_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, t.created_at, t.resolved_at)) as avg_seconds'))
            ->value('avg_seconds');
        $avgHoras = $avgHoras ? round(((float) $avgHoras) / 3600, 2) : null;

        $porMes = (clone $base)
            ->select(DB::raw("DATE_FORMAT(t.created_at, '%Y-%m') as mes"), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw("DATE_FORMAT(t.created_at, '%Y-%m')"))
            ->orderBy(DB::raw("DATE_FORMAT(t.created_at, '%Y-%m')"))
            ->get();

        $porCategoria = (clone $base)
            ->select(DB::raw("COALESCE(c.nombre, 'Sin categoría') as categoria"), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw("COALESCE(c.nombre, 'Sin categoría')"))
            ->orderByDesc('total')
            ->get();

        $porSolicitante = (clone $base)
            ->select(DB::raw("COALESCE(u_sol.name, 'N/A') as solicitante"), 'u_sol.id as solicitante_id', DB::raw('COUNT(*) as total'))
            ->groupBy('u_sol.id', 'u_sol.name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $porTecnico = (clone $base)
            ->select(DB::raw("COALESCE(u_asg.name, 'N/A') as tecnico"), 'u_asg.id as tecnico_id', DB::raw('COUNT(*) as total'))
            ->groupBy('u_asg.id', 'u_asg.name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $abiertos = (clone $base)->whereIn('e.nombre', ['Abierto', 'En Progreso'])->count();
        $resueltos = (clone $base)->whereIn('e.nombre', ['Resuelto', 'Cerrado'])->count();

        return $this->success([
            'filters' => $filters,
            'total' => $total,
            'abiertos_vs_resueltos' => [
                'abiertos' => $abiertos,
                'resueltos' => $resueltos,
            ],
            'promedio_resolucion_horas' => $avgHoras,
            'totales_por_estado' => $totalesPorEstado,
            'tickets_por_mes' => $porMes,
            'tickets_por_categoria' => $porCategoria,
            'tickets_por_solicitante' => $porSolicitante,
            'tickets_por_tecnico' => $porTecnico,
        ]);
    }

    public function detallado(array $filters, int $limit): array
    {
        $limit = max(1, min(2000, $limit));

        $items = $this->baseQuery($filters)
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
            ->limit($limit)
            ->get();

        return $this->success([
            'filters' => $filters,
            'items' => $items,
        ]);
    }

    public function exportExcel(array $filters)
    {
        $name = 'helpdesk_tickets_'.$filters['fechaIni'].'_a_'.$filters['fechaFin'].'.xlsx';

        return Excel::download(new HelpDeskTicketsDetalladoExport($filters), $name);
    }

    public function exportPdf(array $filters)
    {
        $indicadoresResult = $this->indicadores($filters);
        $indicadores = $indicadoresResult['data'];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('modulos.help-desk.reportes-pdf', [
            'data' => $indicadores,
        ]);

        $name = 'helpdesk_reportes_'.$filters['fechaIni'].'_a_'.$filters['fechaFin'].'.pdf';

        return $pdf->download($name);
    }
}
