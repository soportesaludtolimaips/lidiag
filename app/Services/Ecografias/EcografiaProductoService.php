<?php

namespace App\Services\Ecografias;

use App\Models\Ecografias\EcografiaProducto;
use App\Services\BaseService;

class EcografiaProductoService extends BaseService
{
    /**
     * Retorna la lista completa de productos de ecografía con sus relaciones de ecografía, médico y productos cargadas.
     *
     * @returns array Arreglo con la respuesta exitosa que contiene la colección de productos de ecografía.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function all(): array
    {
        $items = EcografiaProducto::with(['ecografia.medico', 'productos'])->get();

        return $this->success($items);
    }

    /**
     * Busca un producto de ecografía por su identificador incluyendo sus relaciones, o retorna error si no existe.
     *
     * @param  int  $id  Identificador único del producto de ecografía a buscar.
     *
     * @returns array Arreglo con la respuesta exitosa y el producto encontrado, o una respuesta de no encontrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function find(int $id): array
    {
        $item = EcografiaProducto::with(['ecografia.medico', 'productos'])->find($id);

        if (! $item) {
            return $this->notFound('Producto de ecografía no encontrado');
        }

        return $this->success($item);
    }

    /**
     * Crea un nuevo registro de producto de ecografía con los datos proporcionados.
     *
     * @param  array  $data  Arreglo con los datos validados del producto de ecografía a crear.
     *
     * @returns array Arreglo con la respuesta de recurso creado y el producto de ecografía registrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function create(array $data): array
    {
        $item = EcografiaProducto::create($data);

        return $this->created($item);
    }

    /**
     * Actualiza un producto de ecografía existente con los datos proporcionados o retorna error si no existe.
     *
     * @param  int  $id  Identificador único del producto de ecografía a actualizar.
     * @param  array  $data  Arreglo con los datos validados a aplicar sobre el producto de ecografía.
     *
     * @returns array Arreglo con la respuesta de recurso actualizado o una respuesta de no encontrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function update(int $id, array $data): array
    {
        $item = EcografiaProducto::find($id);

        if (! $item) {
            return $this->notFound('Producto de ecografía no encontrado');
        }

        $item->update($data);

        return $this->updated($item);
    }

    /**
     * Elimina un producto de ecografía del sistema según su identificador o retorna error si no existe.
     *
     * @param  int  $id  Identificador único del producto de ecografía a eliminar.
     *
     * @returns array Arreglo con la respuesta de recurso eliminado o una respuesta de no encontrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function delete(int $id): array
    {
        $item = EcografiaProducto::find($id);

        if (! $item) {
            return $this->notFound('Producto de ecografía no encontrado');
        }

        $item->delete();

        return $this->deleted('Producto de ecografía eliminado correctamente');
    }
}
