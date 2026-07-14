<?php

namespace App\Services\Configuracion;

use App\Models\Citas\CitaPivot;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigServicioCitas;
use App\Services\BaseService;

class ServicioProductoService extends BaseService
{
    public function all(?int $servicioCitaId = null): array
    {
        if ($servicioCitaId) {
            $pivots = CitaPivot::with('servicio', 'producto')
                ->where('servicio_cita_id', $servicioCitaId)
                ->get();

            return $this->success($pivots);
        }

        $items = ConfigServicioCitas::with('productos')->get();

        return $this->success($items);
    }

    public function create(array $data): array
    {
        $existing = CitaPivot::where('servicio_cita_id', $data['servicio_cita_id'])
            ->where('producto_id', $data['producto_id'])
            ->first();

        if ($existing) {
            if ($existing->estado === false) {
                $existing->update(['estado' => true]);

                return $this->success($existing, 'Relación reactivada correctamente.');
            }

            return $this->error('El producto ya está vinculado a este servicio.', 400);
        }

        $item = CitaPivot::create($data);

        return $this->created($item->load('servicio', 'producto'));
    }

    public function delete(int $id): array
    {
        $item = CitaPivot::findOrFail($id);
        $item->update(['estado' => false]);

        return $this->deleted('Vinculación desactivada correctamente.');
    }

    public function productosNoVinculados(int $servicioCitaId): array
    {
        $vinculados = CitaPivot::where('servicio_cita_id', $servicioCitaId)
            ->where('estado', true)
            ->pluck('producto_id');

        $productos = ConfigProducto::with('recomendacionProducto')
            ->whereNotIn('id', $vinculados)
            ->get();

        return $this->success($productos);
    }
}
