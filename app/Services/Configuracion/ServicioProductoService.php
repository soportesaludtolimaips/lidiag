<?php

namespace App\Services\Configuracion;

use App\Models\Citas\CitaPivot;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigServicioCitas;
use App\Services\BaseService;

class ServicioProductoService extends BaseService
{
    /**
     * Obtiene los servicios con sus productos vinculados, filtrados por servicio de cita.
     *
     * @param  int|null  $servicioCitaId  Identificador del servicio de cita para filtrar las vinculaciones.
     *
     * @returns array Arreglo con la lista de servicios o pivotes y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(?int $servicioCitaId = null): array
    {
        if ($servicioCitaId) {
            $pivots = CitaPivot::with('servicio', 'producto')
                ->where('servicio_cita_id', $servicioCitaId)
                ->get();

            return $this->success($pivots);
        }

        $items = ConfigServicioCitas::with('productos')->get();

        return $this->success($items);
    }

    /**
     * Crea o reactiva la vinculación entre un servicio de cita y un producto.
     *
     * @param  array  $data  Datos validados para la creación o reactivación de la vinculación.
     *
     * @returns array Arreglo con la vinculación creada o reactivada y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $existing = CitaPivot::where('servicio_cita_id', $data['servicio_cita_id'])
            ->where('producto_id', $data['producto_id'])
            ->first();

        if ($existing) {
            if ($existing->estado === false) {
                $existing->update(['estado' => true]);

                return $this->success($existing, 'Relación reactivada correctamente.');
            }

            return $this->error('El producto ya está vinculado a este servicio.', 400);
        }

        $item = CitaPivot::create($data);

        return $this->created($item->load('servicio', 'producto'));
    }

    /**
     * Desactiva la vinculación entre un servicio de cita y un producto.
     *
     * @param  int  $id  Identificador de la vinculación a desactivar.
     *
     * @returns array Arreglo con el resultado de la desactivación y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = CitaPivot::findOrFail($id);
        $item->update(['estado' => false]);

        return $this->deleted('Vinculación desactivada correctamente.');
    }

    /**
     * Lista los productos que aún no están vinculados al servicio de cita indicado.
     *
     * @param  int  $servicioCitaId  Identificador del servicio de cita.
     *
     * @returns array Arreglo con los productos no vinculados y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function productosNoVinculados(int $servicioCitaId): array
    {
        $vinculados = CitaPivot::where('servicio_cita_id', $servicioCitaId)
            ->where('estado', true)
            ->pluck('producto_id');

        $productos = ConfigProducto::with('recomendacionProducto')
            ->whereNotIn('id', $vinculados)
            ->get();

        return $this->success($productos);
    }
}
