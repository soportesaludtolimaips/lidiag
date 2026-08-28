<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigProducto;
use App\Services\BaseService;

class ConfigProductoService extends BaseService
{
    /**
     * Retorna todos los productos ordenados por nombre.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(ConfigProducto::orderBy('nom_produc')->get());
    }

    /**
     * Busca un producto por su identificador.
     *
     * @param  int  $id  Identificador del producto.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConfigProducto::find($id);

        if (! $item) {
            return $this->notFound('Producto no encontrado');
        }

        return $this->success($item);
    }

    /**
     * Crea un nuevo producto de configuración.
     *
     * @param  array  $data  Datos del producto.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigProducto::create($data);
        AuditLogger::crear($item, 'Producto creado: '.($data['nom_produc'] ?? ''));

        return $this->created($item);
    }

    /**
     * Actualiza un producto de configuración existente.
     *
     * @param  int  $id  Identificador del producto.
     * @param  array  $data  Datos a actualizar.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigProducto::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Producto actualizado: '.$item->nom_produc);

        return $this->updated($item);
    }

    /**
     * Elimina un producto de configuración existente.
     *
     * @param  int  $id  Identificador del producto.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigProducto::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Producto eliminado: '.$item->nom_produc);

        return $this->deleted();
    }
}
