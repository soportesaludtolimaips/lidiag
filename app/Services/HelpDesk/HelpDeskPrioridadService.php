<?php

namespace App\Services\HelpDesk;

use App\Models\HelpDesk\HelpDeskPrioridad;
use App\Services\BaseService;

class HelpDeskPrioridadService extends BaseService
{
    /**
     * Retorna todas las prioridades de help desk ordenadas por orden y nombre.
     *
     * @returns array Respuesta con la lista de prioridades.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(HelpDeskPrioridad::query()->orderBy('orden')->orderBy('nombre')->get());
    }

    /**
     * Busca una prioridad por su identificador.
     *
     * @param  int  $id  Identificador de la prioridad a consultar.
     *
     * @returns array Respuesta con la prioridad encontrada o error de no encontrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = HelpDeskPrioridad::find($id);
        if (! $item) {
            return $this->notFound('Prioridad no encontrada');
        }

        return $this->success($item);
    }

    /**
     * Crea una nueva prioridad con los datos proporcionados.
     *
     * @param  array  $data  Datos validados de la prioridad (nombre, orden, activo).
     *
     * @returns array Respuesta con la prioridad creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = HelpDeskPrioridad::create([
            'nombre' => $data['nombre'],
            'orden' => isset($data['orden']) ? (int) $data['orden'] : 0,
            'activo' => isset($data['activo']) ? (bool) $data['activo'] : true,
        ]);

        return $this->created($item);
    }

    /**
     * Actualiza una prioridad existente por su identificador.
     *
     * @param  int  $id  Identificador de la prioridad a actualizar.
     * @param  array  $data  Datos validados con los campos a modificar.
     *
     * @returns array Respuesta con la prioridad actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Desactiva (elimina lógicamente) una prioridad por su identificador.
     *
     * @param  int  $id  Identificador de la prioridad a desactivar.
     *
     * @returns array Respuesta de eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = HelpDeskPrioridad::findOrFail($id);
        $item->activo = false;
        $item->save();

        return $this->deleted();
    }
}
