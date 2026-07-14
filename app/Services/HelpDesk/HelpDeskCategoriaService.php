<?php

namespace App\Services\HelpDesk;

use App\Models\HelpDesk\HelpDeskCategoria;
use App\Services\BaseService;

class HelpDeskCategoriaService extends BaseService
{
    public function all(): array
    {
        return $this->success(HelpDeskCategoria::query()->orderBy('nombre')->get());
    }

    public function find(int $id): array
    {
        $item = HelpDeskCategoria::find($id);
        if (! $item) {
            return $this->notFound('Categoría no encontrada');
        }

        return $this->success($item);
    }

    public function create(array $data): array
    {
        $item = HelpDeskCategoria::create([
            'nombre' => $data['nombre'],
            'activo' => isset($data['activo']) ? (bool) $data['activo'] : true,
        ]);

        return $this->created($item);
    }

    public function update(int $id, array $data): array
    {
        $item = HelpDeskCategoria::findOrFail($id);
        $item->update([
            'nombre' => $data['nombre'],
            'activo' => (bool) $data['activo'],
        ]);

        return $this->updated($item);
    }

    public function delete(int $id): array
    {
        $item = HelpDeskCategoria::findOrFail($id);
        $item->activo = false;
        $item->save();

        return $this->deleted();
    }
}
