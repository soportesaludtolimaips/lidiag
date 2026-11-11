<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigPrioridad;
use App\Services\BaseService;

class ConfigPrioridadService extends BaseService
{
    /**
     * Obtiene todas las prioridades registradas en el sistema.
     *
     * @returns array Respuesta con la lista de prioridades y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(ConfigPrioridad::all());
    }

    /**
     * Busca una prioridad por su identificador.
     *
     * @param  int  $id  Identificador único de la prioridad.
     *
     * @returns array Respuesta con los datos de la prioridad o un error si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConfigPrioridad::find($id);

        if (! $item) {
            return $this->notFound('Prioridad no encontrada');
        }

        return $this->success($item);
    }

    /**
     * Crea una nueva prioridad con los datos proporcionados y registra la auditoría.
     *
     * @param  array  $data  Datos validados de la prioridad a crear.
     *
     * @returns array Respuesta con la prioridad creada y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigPrioridad::create($data);
        AuditLogger::crear($item, 'Prioridad creada: '.($data['nom_priori'] ?? ''));

        return $this->created($item);
    }

    /**
     * Actualiza una prioridad existente y registra el cambio en auditoría.
     *
     * @param  int  $id  Identificador único de la prioridad.
     * @param  array  $data  Datos validados a actualizar en la prioridad.
     *
     * @returns array Respuesta con la prioridad actualizada y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigPrioridad::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Prioridad actualizada: '.$item->nom_priori);

        return $this->updated($item);
    }

    /**
     * Elimina una prioridad existente y registra la eliminación en auditoría.
     *
     * @param  int  $id  Identificador único de la prioridad a eliminar.
     *
     * @returns array Respuesta con el estado de la operación de eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigPrioridad::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Prioridad eliminada: '.$item->nom_priori);

        return $this->deleted();
    }
}
