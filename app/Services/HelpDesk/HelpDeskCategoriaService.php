<?php

namespace App\Services\HelpDesk;

use App\Models\HelpDesk\HelpDeskCategoria;
use App\Services\BaseService;

class HelpDeskCategoriaService extends BaseService
{
    /**
     * Retorna todas las categorías de help desk ordenadas por nombre.
     *
     * @returns array Respuesta con la lista de categorías.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(HelpDeskCategoria::query()->orderBy('nombre')->get());
    }

    /**
     * Busca una categoría por su identificador.
     *
     * @param  int  $id  Identificador de la categoría a consultar.
     *
     * @returns array Respuesta con la categoría encontrada o error de no encontrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = HelpDeskCategoria::find($id);
        if (! $item) {
            return $this->notFound('Categoría no encontrada');
        }

        return $this->success($item);
    }

    /**
     * Crea una nueva categoría con los datos proporcionados.
     *
     * @param  array  $data  Datos validados de la categoría (nombre, activo).
     *
     * @returns array Respuesta con la categoría creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = HelpDeskCategoria::create([
            'nombre' => $data['nombre'],
            'activo' => isset($data['activo']) ? (bool) $data['activo'] : true,
        ]);

        return $this->created($item);
    }

    /**
     * Actualiza una categoría existente por su identificador.
     *
     * @param  int  $id  Identificador de la categoría a actualizar.
     * @param  array  $data  Datos validados con los campos a modificar.
     *
     * @returns array Respuesta con la categoría actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = HelpDeskCategoria::findOrFail($id);
        $item->update([
            'nombre' => $data['nombre'],
            'activo' => (bool) $data['activo'],
        ]);

        return $this->updated($item);
    }

    /**
     * Desactiva (elimina lógicamente) una categoría por su identificador.
     *
     * @param  int  $id  Identificador de la categoría a desactivar.
     *
     * @returns array Respuesta de eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = HelpDeskCategoria::findOrFail($id);
        $item->activo = false;
        $item->save();

        return $this->deleted();
    }
}
