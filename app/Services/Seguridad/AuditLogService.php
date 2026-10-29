<?php

namespace App\Services\Seguridad;

use App\Models\AuditLog;
use App\Services\BaseService;

class AuditLogService extends BaseService
{
    /**
     * Lista los registros de auditoría con filtros por evento, usuario, búsqueda y rango de fechas.
     *
     * Flujo del proceso:
     * 1. Inicializa la consulta sobre AuditLog con la relación del usuario responsable (id, name).
     * 2. Proyecta columnas normalizadas incluyendo la descripción legible del evento.
     * 3. Aplica filtros opcionales de tipo de evento, usuario, rango de fechas y búsqueda textual global.
     * 4. Ordena cronológicamente descendente y retorna la paginación estandarizada.
     *
     * @param  array  $filters  Filtros: evento, user_id, search, desde, hasta, per_page
     *
     * @returns array Respuesta con la paginación de registros de auditoría
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function all(array $filters): array
    {
        $query = AuditLog::with('user:id,name')
            ->select([
                'id',
                'user_id',
                'evento',
                'model_type',
                'model_id',
                'descripcion',
                'datos_viejos',
                'datos_nuevos',
                'ip',
                'user_agent',
                'created_at',
            ])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['evento'])) {
            $query->where('evento', $filters['evento']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('descripcion', 'like', "%{$search}%")
                    ->orWhere('model_type', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
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
     * Obtiene la lista distinta y ordenada de eventos registrados en el log de auditoría.
     *
     * Flujo del proceso:
     * 1. Consulta los valores únicos de la columna 'evento' en la tabla audit_logs.
     * 2. Ordena alfabéticamente y retorna el catálogo como colección plana.
     *
     * @returns array Lista ordenada de tipos de eventos registrados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function eventos(): array
    {
        $eventos = AuditLog::select('evento')
            ->whereNotNull('evento')
            ->where('evento', '!=', '')
            ->distinct()
            ->orderBy('evento')
            ->pluck('evento');

        return $this->success($eventos);
    }
}
