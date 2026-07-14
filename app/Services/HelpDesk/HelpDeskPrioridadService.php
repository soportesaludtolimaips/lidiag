<?php

namespace App\Services\HelpDesk;

use App\Models\HelpDesk\HelpDeskPrioridad;
use App\Services\BaseService;

class HelpDeskPrioridadService extends BaseService
{
    public function all(): array
    {
        return $this->success(HelpDeskPrioridad::query()->orderBy('orden')->orderBy('nombre')->get());
    }

    public function find(int $id): array
    {
        $item = HelpDeskPrioridad::find($id);
        if (! $item) {
            return $this->notFound('Prioridad no encontrada');
        }

        return $this->success($item);
    }

    public function create(array $data): array
    {
        $item = HelpDeskPrioridad::create([
            'nombre' => $data['nombre'],
            'orden' => isset($data['orden']) ? (int) $data['orden'] : 0,
            'activo' => isset($data['activo']) ? (bool) $data['activo'] : true,
        ]);

        return $this->created($item);
    }

    public function update(int $id, array $data): array
    {
        $item = HelpDeskPrioridad::findOrFail($id);
        $item->update([
            'nombre' => $data['nombre'],
            'orden' => (int) $data['orden'],
            'activo' => (bool) $data['activo'],
        ]);

        return $this->updated($item);
    }

    public function delete(int $id): array
    {
        $item = HelpDeskPrioridad::findOrFail($id);
        $item->activo = false;
        $item->save();

        return $this->deleted();
    }
}
