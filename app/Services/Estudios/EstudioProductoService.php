<?php

namespace App\Services\Estudios;

use App\Models\Estudio\EstudioProducto;
use App\Services\BaseService;

class EstudioProductoService extends BaseService
{
    /**
     * Lista los productos asociados a un estudio específico
     *
     * @param  int  $estudioId  ID del estudio
     *
     * @returns array Respuesta con la lista de productos del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarPorEstudio(int $estudioId): array
    {
        $productos = EstudioProducto::where('estudio_id', $estudioId)->get();

        return $this->success(['productos' => $productos]);
    }

    /**
     * Crea un nuevo producto asociado a un estudio
     *
     * @param  array  $data  Datos del producto (estudio_id, producto_id, cod_cups, nom_produc)
     *
     * @returns array Respuesta con el producto creado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = EstudioProducto::create($data);

        return $this->created($item);
    }

    /**
     * Actualiza un producto de estudio existente
     *
     * @param  int  $id  ID del producto a actualizar
     * @param  array  $data  Datos a actualizar del producto
     *
     * @returns array Respuesta con el producto actualizado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = EstudioProducto::findOrFail($id);
        $item->update($data);

        return $this->updated($item);
    }

    /**
     * Elimina un producto de estudio existente
     *
     * @param  int  $id  ID del producto a eliminar
     *
     * @returns array Respuesta de confirmación de eliminación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = EstudioProducto::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }
}
