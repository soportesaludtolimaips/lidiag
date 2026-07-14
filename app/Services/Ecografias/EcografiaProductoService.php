<?php

namespace App\Services\Ecografias;

use App\Models\Ecografias\EcografiaProducto;
use App\Services\BaseService;

class EcografiaProductoService extends BaseService
{
    public function all(): array
    {
        $items = EcografiaProducto::with(['ecografia.medico', 'productos'])->get();

        return $this->success($items);
    }

    public function find(int $id): array
    {
        $item = EcografiaProducto::with(['ecografia.medico', 'productos'])->find($id);

        if (! $item) {
            return $this->notFound('Producto de ecografía no encontrado');
        }

        return $this->success($item);
    }

    public function create(array $data): array
    {
        $item = EcografiaProducto::create($data);

        return $this->created($item);
    }

    public function update(int $id, array $data): array
    {
        $item = EcografiaProducto::find($id);

        if (! $item) {
            return $this->notFound('Producto de ecografía no encontrado');
        }

        $item->update($data);

        return $this->updated($item);
    }

    public function delete(int $id): array
    {
        $item = EcografiaProducto::find($id);

        if (! $item) {
            return $this->notFound('Producto de ecografía no encontrado');
        }

        $item->delete();

        return $this->deleted('Producto de ecografía eliminado correctamente');
    }
}
