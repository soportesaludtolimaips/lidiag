<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigDiagnostico;
use App\Services\BaseService;

class ConfigDiagnosticoService extends BaseService
{
    public function all(): array
    {
        return $this->success(ConfigDiagnostico::orderBy('nom_diagnos')->get());
    }

    public function create(array $data): array
    {
        $item = ConfigDiagnostico::create($data);
        AuditLogger::crear($item, 'Diagnóstico creado: '.($data['nom_diagnos'] ?? ''));

        return $this->created($item);
    }

    public function update(int $id, array $data): array
    {
        $item = ConfigDiagnostico::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Diagnóstico actualizado: '.$item->nom_diagnos);

        return $this->updated($item);
    }

    public function delete(int $id): array
    {
        $item = ConfigDiagnostico::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Diagnóstico eliminado: '.$item->nom_diagnos);

        return $this->deleted();
    }
}
