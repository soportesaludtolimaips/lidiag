<?php

namespace App\Services\HelpDesk;

use App\Models\HelpDesk\HelpDeskEstado;
use App\Services\BaseService;

class HelpDeskEstadoService extends BaseService
{
    /**
     * Retorna todos los estados de help desk ordenados por orden y nombre.
     *
     * @returns array Respuesta con la lista de estados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(HelpDeskEstado::query()->orderBy('orden')->orderBy('nombre')->get());
    }

    /**
     * Busca un estado por su identificador.
     *
     * @param  int  $id  Identificador del estado a consultar.
     *
     * @returns array Respuesta con el estado encontrado o error de no encontrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = HelpDeskEstado::find($id);
        if (! $item) {
            return $this->notFound('Estado no encontrado');
        }

        return $this->success($item);
    }

    /**
     * Rechaza la creación de estados porque el catálogo de estados es fijo.
     *
     * @returns array Respuesta de error indicando que no se permite crear estados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(): array
    {
        return $this->error('Estados fijos: no se permite crear nuevos estados.', 403);
    }

    /**
     * Rechaza la edición de estados porque el catálogo de estados es fijo.
     *
     * @param  int  $id  Identificador del estado que se intenta editar.
     *
     * @returns array Respuesta de error indicando que no se permite editar estados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id): array
    {
        return $this->error('Estados fijos: no se permite editar estados.', 403);
    }

    /**
     * Rechaza la eliminación de estados porque el catálogo de estados es fijo.
     *
     * @param  int  $id  Identificador del estado que se intenta eliminar.
     *
     * @returns array Respuesta de error indicando que no se permite eliminar estados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        return $this->error('Estados fijos: no se permite eliminar estados.', 403);
    }
}
