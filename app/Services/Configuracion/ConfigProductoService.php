<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigProducto;
use App\Services\BaseService;

class ConfigProductoService extends BaseService
{
    public function all(): array
    {
        return $this->success(ConfigProducto::orderBy('nom_produc')->get());
    }

    public function find(int $id): array
    {
        $item = ConfigProducto::find($id);

        if (! $item) {
            return $this->notFound('Producto no encontrado');
        }

        return $this->success($item);
    }

    public function create(array $data): array
    {
        $item = ConfigProducto::create($data);
        AuditLogger::crear($item, 'Producto creado: '.($data['nom_produc'] ?? ''));

        return $this->created($item);
    }

    public function update(int $id, array $data): array
    {
        $item = ConfigProducto::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Producto actualizado: '.$item->nom_produc);

        return $this->updated($item);
    }

    public function delete(int $id): array
    {
        $item = ConfigProducto::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Producto eliminado: '.$item->nom_produc);

        return $this->deleted();
    }
}
