<?php

namespace App\Services\Consentimientos;

use App\Models\Consentimientos\ConsentimientoDocumento;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;

class ConsentimientoEstadisticaService extends BaseService
{
    public function resumen(array $filters): array
    {
        $base = fn () => $this->baseQuery($filters);

        $total = $base()->count();
        $firmadosPaciente = $base()->whereNotNull('firma_paciente_at')->count();
        $completos = $base()->where('estado', 'COMPLETO')->count();
        $expirados = $base()->where('estado', 'EXPIRADO')->count();
        $anulados = $base()->where('estado', 'ANULADO')->count();
        $pendientes = $base()->whereIn('estado', ['GENERADO', 'PENDIENTE_PACIENTE', 'PENDIENTE_MEDICO'])->count();

        return $this->success([
            'total_generados' => $total,
            'firmados_paciente' => $firmadosPaciente,
            'completos' => $completos,
            'expirados' => $expirados,
            'anulados' => $anulados,
            'pendientes' => $pendientes,
            'tasa_firma_paciente' => $this->tasa($firmadosPaciente, $total),
            'tasa_completado' => $this->tasa($completos, $total),
            'tasa_expiracion' => $this->tasa($expirados, $total),
        ]);
    }

    public function porSede(array $filters): array
    {
        $rows = $this->baseQuery($filters)
            ->selectRaw('sede_id, COUNT(*) as total')
            ->selectRaw("SUM(estado = 'COMPLETO') as completos")
            ->selectRaw("SUM(estado = 'EXPIRADO') as expirados")
            ->groupBy('sede_id')
            ->with('sede:id,nom_sede')
            ->get();

        return $this->success($rows);
    }

    public function porProducto(array $filters): array
    {
        $rows = $this->baseQuery($filters)
            ->selectRaw('producto_id, COUNT(*) as total')
            ->selectRaw("SUM(estado = 'COMPLETO') as completos")
            ->selectRaw("SUM(estado = 'EXPIRADO') as expirados")
            ->groupBy('producto_id')
            ->with('producto:id,cod_cups,nom_produc')
            ->orderByDesc('total')
            ->get();

        return $this->success($rows);
    }

    public function porMedico(array $filters): array
    {
        $rows = $this->baseQuery($filters)
            ->whereNotNull('medico_id')
            ->selectRaw('medico_id, COUNT(*) as total')
            ->selectRaw("SUM(estado = 'PENDIENTE_MEDICO') as pendientes")
            ->selectRaw("SUM(estado = 'COMPLETO') as completos")
            ->groupBy('medico_id')
            ->with('medico:id,name')
            ->orderByDesc('total')
            ->get();

        return $this->success($rows);
    }

    public function tiemposPromedio(array $filters): array
    {
        $row = $this->baseQuery($filters)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, firma_paciente_at)) as min_firma_paciente')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, firma_medico_at)) as min_firma_medico')
            ->first();

        return $this->success([
            'minutos_promedio_firma_paciente' => $row?->min_firma_paciente !== null ? round((float) $row->min_firma_paciente, 1) : null,
            'minutos_promedio_firma_medico' => $row?->min_firma_medico !== null ? round((float) $row->min_firma_medico, 1) : null,
        ]);
    }

    public function productosPopulares(array $filters, int $limit = 10): array
    {
        $rows = $this->baseQuery($filters)
            ->selectRaw('producto_id, COUNT(*) as total')
            ->groupBy('producto_id')
            ->with('producto:id,cod_cups,nom_produc')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return $this->success($rows);
    }

    /**
     * Query base con filtros comunes (sede, producto, médico, rango de fechas).
     */
    private function baseQuery(array $filters): Builder
    {
        $query = ConsentimientoDocumento::query();

        if (! empty($filters['sede_id'])) {
            $query->where('sede_id', (int) $filters['sede_id']);
        }
        if (! empty($filters['producto_id'])) {
            $query->where('producto_id', (int) $filters['producto_id']);
        }
        if (! empty($filters['medico_id'])) {
            $query->where('medico_id', (int) $filters['medico_id']);
        }
        if (! empty($filters['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $filters['fecha_inicio']);
        }
        if (! empty($filters['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $filters['fecha_fin']);
        }

        return $query;
    }

    private function tasa(int $parte, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($parte / $total) * 100, 1);
    }
}
