<?php

namespace App\Services\Seguridad;

use App\Models\ActivityLog;
use App\Services\BaseService;

class AuditLogService extends BaseService
{
    /**
     * Consulta y pagina los registros de actividad aplicando los filtros proporcionados.
     *
     * @param  array  $filters  Filtros de búsqueda: evento, user_id, search, desde, hasta y per_page.
     *
     * @returns array Resultado con la lista paginada de registros de auditoría.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(array $filters): array
    {
        $query = ActivityLog::with('user:id,name')
            ->select([
                'id',
                'user_id',
                'loggable_type as model_type',
                'loggable_id as model_id',
                'event as evento',
                'old_values as datos_viejos',
                'new_values as datos_nuevos',
                'ip_address as ip',
                'user_agent',
                'created_at',
            ])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['evento'])) {
            $query->where('event', $filters['evento']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('loggable_type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['desde'])) {
            $query->whereDate('created_at', '>=', $filters['desde']);
        }

        if (! empty($filters['hasta'])) {
            $query->whereDate('created_at', '<=', $filters['hasta']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 50), 100);
        $logs = $query->paginate($perPage);

        return $this->success($logs);
    }

    /**
     * Retorna la lista de eventos de auditoría distintos registrados en el sistema.
     *
     * @returns array Resultado con el catálogo de eventos disponibles.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function eventos(): array
    {
        $eventos = ActivityLog::select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        return $this->success($eventos);
    }
}
