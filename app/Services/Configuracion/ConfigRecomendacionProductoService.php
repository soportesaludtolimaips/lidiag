<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigRecomendacionProducto;
use App\Services\BaseService;

class ConfigRecomendacionProductoService extends BaseService
{
    public function all(?int $productoId = null, ?bool $estado = null): array
    {
        $query = ConfigRecomendacionProducto::with('producto');

        if ($productoId) {
            $query->where('producto_id', $productoId);
        }

        if ($estado !== null) {
            $query->where('estado', $estado);
        }

        return $this->success($query->get());
    }

    public function find(int $id): array
    {
        $item = ConfigRecomendacionProducto::with('producto')->find($id);

        if (! $item) {
            return $this->notFound('Recomendación de producto no encontrada');
        }

        return $this->success($item);
    }

    public function createOrUpdate(array $data): array
    {
        $item = ConfigRecomendacionProducto::updateOrCreate(
            ['producto_id' => $data['producto_id']],
            [
                'recomendacion' => $data['recomendacion'],
                'estado' => filter_var($data['estado'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]
        );

        $verb = $item->wasRecentlyCreated ? 'creada' : 'actualizada';

        return $this->success(
            $item->load('producto'),
            "Recomendación {$verb} correctamente.",
            $item->wasRecentlyCreated ? 201 : 200
        );
    }

    public function update(int $id, array $data): array
    {
        $item = ConfigRecomendacionProducto::findOrFail($id);
        $item->update([
            'recomendacion' => $data['recomendacion'],
            'estado' => filter_var($data['estado'] ?? $item->estado, FILTER_VALIDATE_BOOLEAN),
        ]);

        return $this->updated($item->load('producto'));
    }

    public function delete(int $id): array
    {
        $item = ConfigRecomendacionProducto::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }
}
