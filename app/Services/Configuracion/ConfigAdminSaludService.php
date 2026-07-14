<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigAdminSalud;
use App\Services\BaseService;

class ConfigAdminSaludService extends BaseService
{
    public function all(): array
    {
        return $this->success(ConfigAdminSalud::all());
    }

    public function create(array $data): array
    {
        $item = ConfigAdminSalud::create($data);
        AuditLogger::crear($item, 'AdminSalud creado: '.($data['nom_admin_salud'] ?? ''));

        return $this->created($item);
    }

    public function update(int $id, array $data): array
    {
        $item = ConfigAdminSalud::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'AdminSalud actualizado: '.$item->nom_admin_salud);

        return $this->updated($item);
    }

    public function delete(int $id): array
    {
        $item = ConfigAdminSalud::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'AdminSalud eliminado: '.$item->nom_admin_salud);

        return $this->deleted();
    }
}
