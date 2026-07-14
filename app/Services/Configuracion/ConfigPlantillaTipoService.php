<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigPlantillaTipo;
use App\Services\BaseService;

class ConfigPlantillaTipoService extends BaseService
{
    public function all(): array
    {
        return $this->success(ConfigPlantillaTipo::all());
    }

    public function find(int $id): array
    {
        $item = ConfigPlantillaTipo::find($id);

        if (! $item) {
            return $this->notFound('Tipo de plantilla no encontrado');
        }

        return $this->success($item);
    }

    public function listarActivos(): array
    {
        return $this->success(ConfigPlantillaTipo::where('estado', true)->get());
    }

    public function create(array $data): array
    {
        $item = ConfigPlantillaTipo::create($data);
        AuditLogger::crear($item, 'Tipo de plantilla creado: '.($data['nom_tipo'] ?? ''));

        return $this->created($item);
    }

    public function update(int $id, array $data): array
    {
        $item = ConfigPlantillaTipo::findOrFail($id);

        $fillable = [];
        if (isset($data['nom_tipo'])) {
            $fillable['nom_tipo'] = $data['nom_tipo'];
        }
        if (isset($data['producto_id'])) {
            $fillable['producto_id'] = $data['producto_id'];
        }
        if (isset($data['estado'])) {
            $fillable['estado'] = filter_var($data['estado'], FILTER_VALIDATE_BOOLEAN);
        }

        $item->update($fillable);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Tipo de plantilla actualizado: '.$item->nom_tipo);

        return $this->updated($item);
    }

    public function delete(int $id): array
    {
        $item = ConfigPlantillaTipo::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Tipo de plantilla eliminado: '.$item->nom_tipo);

        return $this->deleted();
    }
}
