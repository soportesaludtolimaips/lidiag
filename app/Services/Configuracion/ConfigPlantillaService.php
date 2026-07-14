<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigPlantilla;
use App\Services\BaseService;

class ConfigPlantillaService extends BaseService
{
    public function all(): array
    {
        return $this->success(
            ConfigPlantilla::with(['plantillaTipo', 'medico'])->get()
        );
    }

    public function find(int $id): array
    {
        $item = ConfigPlantilla::with(['plantillaTipo', 'medico'])->find($id);

        if (! $item) {
            return $this->notFound('Plantilla no encontrada');
        }

        return $this->success($item);
    }

    public function listarActivos(): array
    {
        return $this->success(
            ConfigPlantilla::with(['plantillaTipo', 'medico'])
                ->where('estado', true)
                ->get()
        );
    }

    public function create(array $data): array
    {
        $item = ConfigPlantilla::create($data);
        AuditLogger::crear($item, 'Plantilla creada: '.($data['nom_plantilla'] ?? ''));

        return $this->created($item->load(['plantillaTipo', 'medico']));
    }

    public function update(int $id, array $data): array
    {
        $item = ConfigPlantilla::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Plantilla actualizada: '.$item->nom_plantilla);

        return $this->updated($item->load(['plantillaTipo', 'medico']));
    }

    public function delete(int $id): array
    {
        $item = ConfigPlantilla::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Plantilla eliminada: '.$item->nom_plantilla);

        return $this->deleted();
    }
}
