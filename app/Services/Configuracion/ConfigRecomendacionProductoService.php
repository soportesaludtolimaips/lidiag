<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigRecomendacionProducto;
use App\Services\BaseService;

class ConfigRecomendacionProductoService extends BaseService
{
    /**
     * Obtiene las recomendaciones de producto, filtrando por producto y estado si se indican.
     *
     * @param  int|null  $productoId  Identificador del producto para filtrar (opcional).
     * @param  bool|null  $estado  Estado de la recomendación para filtrar (opcional).
     *
     * @returns array Respuesta con la lista de recomendaciones y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(?int $productoId = null, ?bool $estado = null): array
    {
        $query = ConfigRecomendacionProducto::with('producto');

        if ($productoId) {
            $query->where('producto_id', $productoId);
        }

        if ($estado !== null) {
            $query->where('estado', $estado);
        }

        return $this->success($query->get());
    }

    /**
     * Busca una recomendación de producto por su identificador.
     *
     * @param  int  $id  Identificador único de la recomendación.
     *
     * @returns array Respuesta con los datos de la recomendación o un error si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConfigRecomendacionProducto::with('producto')->find($id);

        if (! $item) {
            return $this->notFound('Recomendación de producto no encontrada');
        }

        return $this->success($item);
    }

    /**
     * Crea una recomendación o la actualiza si ya existe para el producto indicado.
     *
     * @param  array  $data  Datos validados de la recomendación (producto_id, recomendacion, estado).
     *
     * @returns array Respuesta con la recomendación creada o actualizada y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function createOrUpdate(array $data): array
    {
        $item = ConfigRecomendacionProducto::updateOrCreate(
            ['producto_id' => $data['producto_id']],
            [
                'recomendacion' => $data['recomendacion'],
                'estado' => filter_var($data['estado'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]
        );

        $verb = $item->wasRecentlyCreated ? 'creada' : 'actualizada';

        return $this->success(
            $item->load('producto'),
            "Recomendación {$verb} correctamente.",
            $item->wasRecentlyCreated ? 201 : 200
        );
    }

    /**
     * Actualiza una recomendación de producto existente con los datos proporcionados.
     *
     * @param  int  $id  Identificador único de la recomendación.
     * @param  array  $data  Datos validados a actualizar (recomendacion, estado).
     *
     * @returns array Respuesta con la recomendación actualizada y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigRecomendacionProducto::findOrFail($id);
        $item->update([
            'recomendacion' => $data['recomendacion'],
            'estado' => filter_var($data['estado'] ?? $item->estado, FILTER_VALIDATE_BOOLEAN),
        ]);

        return $this->updated($item->load('producto'));
    }

    /**
     * Elimina una recomendación de producto existente según su identificador.
     *
     * @param  int  $id  Identificador único de la recomendación a eliminar.
     *
     * @returns array Respuesta con el estado de la operación de eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigRecomendacionProducto::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }
}
