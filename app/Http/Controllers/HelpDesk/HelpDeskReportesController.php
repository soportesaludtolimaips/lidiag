<?php

namespace App\Http\Controllers\HelpDesk;

use App\Exports\HelpDeskTicketsDetalladoExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class HelpDeskReportesController extends Controller
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

    private function validateFilters(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fechaIni' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaIni',
            'sedeId' => 'nullable',
            'estadoId' => 'nullable|integer',
            'categoriaId' => 'nullable|integer',
            'prioridadId' => 'nullable|integer',
            'solicitanteId' => 'nullable|integer',
            'tecnicoId' => 'nullable|integer',
        ], [
            'fechaIni.required' => 'Debes seleccionar fecha inicio.',
            'fechaFin.required' => 'Debes seleccionar fecha fin.',
            'fechaFin.after_or_equal' => 'La fecha fin no puede ser menor que la fecha inicio.',
        ]);

        if ($validator->fails()) {
            return [null, response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422)];
        }

        $filters = [
            'fechaIni' => $request->input('fechaIni'),
            'fechaFin' => $request->input('fechaFin'),
            'sedeId' => $this->normalizeNullable($request->input('sedeId')),
            'estadoId' => $this->normalizeNullable($request->input('estadoId')),
            'categoriaId' => $this->normalizeNullable($request->input('categoriaId')),
            'prioridadId' => $this->normalizeNullable($request->input('prioridadId')),
            'solicitanteId' => $this->normalizeNullable($request->input('solicitanteId')),
            'tecnicoId' => $this->normalizeNullable($request->input('tecnicoId')),
        ];

        return [$filters, null];
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

    public function indicadores(Request $request)
    {
        [$filters, $errorResponse] = $this->validateFilters($request);
        if ($errorResponse) {
            return $errorResponse;
        }

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

        // Abiertos vs resueltos (por estado nombre)
        $abiertos = (clone $base)->whereIn('e.nombre', ['Abierto', 'En Progreso'])->count();
        $resueltos = (clone $base)->whereIn('e.nombre', ['Resuelto', 'Cerrado'])->count();

        return response()->json([
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

    public function detallado(Request $request)
    {
        [$filters, $errorResponse] = $this->validateFilters($request);
        if ($errorResponse) {
            return $errorResponse;
        }

        $limit = (int) ($request->input('limit') ?? 500);
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

        return response()->json([
            'filters' => $filters,
            'items' => $items,
        ]);
    }

    public function exportExcel(Request $request)
    {
        [$filters, $errorResponse] = $this->validateFilters($request);
        if ($errorResponse) {
            return $errorResponse;
        }

        $name = 'helpdesk_tickets_'.$filters['fechaIni'].'_a_'.$filters['fechaFin'].'.xlsx';

        return Excel::download(new HelpDeskTicketsDetalladoExport($filters), $name);
    }

    public function exportPdf(Request $request)
    {
        [$filters, $errorResponse] = $this->validateFilters($request);
        if ($errorResponse) {
            return $errorResponse;
        }

        // Reutiliza el mismo payload de indicadores
        $indicadores = $this->indicadores(new Request($filters))->getData(true);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('modulos.help-desk.reportes-pdf', [
            'data' => $indicadores,
        ]);

        $name = 'helpdesk_reportes_'.$filters['fechaIni'].'_a_'.$filters['fechaFin'].'.pdf';

        return $pdf->download($name);
    }
}
