<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigAdminSalud;
use App\Services\BaseService;

class ConfigAdminSaludService extends BaseService
{
    /**
     * Obtiene todos los administradores de salud registrados.
     *
     * @returns array Arreglo con la lista de administradores de salud.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(ConfigAdminSalud::all());
    }

    /**
     * Crea un nuevo administrador de salud y registra la auditoría correspondiente.
     *
     * @param  array  $data  Datos del administrador de salud a crear.
     *
     * @returns array Arreglo con el administrador de salud creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigAdminSalud::create($data);
        AuditLogger::crear($item, 'AdminSalud creado: '.($data['nom_admin_salud'] ?? ''));

        return $this->created($item);
    }

    /**
     * Actualiza un administrador de salud existente y registra la auditoría correspondiente.
     *
     * @param  int  $id  Identificador del administrador de salud a actualizar.
     * @param  array  $data  Datos del administrador de salud a actualizar.
     *
     * @returns array Arreglo con el administrador de salud actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigAdminSalud::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'AdminSalud actualizado: '.$item->nom_admin_salud);

        return $this->updated($item);
    }

    /**
     * Elimina un administrador de salud existente y registra la auditoría correspondiente.
     *
     * @param  int  $id  Identificador del administrador de salud a eliminar.
     *
     * @returns array Arreglo con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigAdminSalud::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'AdminSalud eliminado: '.$item->nom_admin_salud);

        return $this->deleted();
    }
}
