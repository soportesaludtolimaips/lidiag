<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigPrioridad;
use App\Services\BaseService;

class ConfigPrioridadService extends BaseService
{
    public function all(): array
    {
        return $this->success(ConfigPrioridad::all());
    }

    public function find(int $id): array
    {
        $item = ConfigPrioridad::find($id);

        if (! $item) {
            return $this->notFound('Prioridad no encontrada');
        }

        return $this->success($item);
    }

    public function create(array $data): array
    {
        $item = ConfigPrioridad::create($data);
        AuditLogger::crear($item, 'Prioridad creada: '.($data['nom_priori'] ?? ''));

        return $this->created($item);
    }

    public function update(int $id, array $data): array
    {
        $item = ConfigPrioridad::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Prioridad actualizada: '.$item->nom_priori);

        return $this->updated($item);
    }

    public function delete(int $id): array
    {
        $item = ConfigPrioridad::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Prioridad eliminada: '.$item->nom_priori);

        return $this->deleted();
    }
}
