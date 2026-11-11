<?php

namespace App\Services\Configuracion;

use App\Models\Citas\CitaPivot;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigServicio;
use App\Services\BaseService;

class ServicioProductoService extends BaseService
{
    /**
     * Obtiene los productos vinculados, filtrados por sede y opcionalmente por servicio.
     *
     * @param  int|null  $sedeId  Identificador de la sede para filtrar las vinculaciones.
     * @param  int|null  $servicioId  Identificador del servicio para filtrar las vinculaciones.
     *
     * @returns array Arreglo con la lista de pivotes y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(?int $sedeId = null, ?int $servicioId = null): array
    {
        if ($sedeId) {
            $query = CitaPivot::with('sede', 'servicio', 'producto')
                ->where('sede_id', $sedeId);

            if ($servicioId) {
                $query->where('servicio_id', $servicioId);
            }

            return $this->success($query->get());
        }

        if ($servicioId) {
            $pivots = CitaPivot::with('servicio', 'producto')
                ->where('servicio_id', $servicioId)
                ->get();

            return $this->success($pivots);
        }

        $items = ConfigServicio::with('productos')->get();

        return $this->success($items);
    }

    /**
     * Crea o reactiva la vinculación entre una sede, un servicio y un producto.
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
        $existing = CitaPivot::where('sede_id', $data['sede_id'])
            ->where('servicio_id', $data['servicio_id'])
            ->where('producto_id', $data['producto_id'])
            ->first();

        if ($existing) {
            if ($existing->estado === false) {
                $existing->update(['estado' => true]);

                return $this->success($existing, 'Relación reactivada correctamente.');
            }

            return $this->error('El producto ya está vinculado a este servicio en esta sede.', 400);
        }

        $item = CitaPivot::create($data);

        return $this->created($item->load('sede', 'servicio', 'producto'));
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
     * Lista los productos que aún no están vinculados en la sede (y servicio) indicados.
     *
     * @param  int  $sedeId  Identificador de la sede.
     * @param  int|null  $servicioId  Identificador del servicio (opcional).
     *
     * @returns array Arreglo con los productos no vinculados y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function productosNoVinculados(int $sedeId, ?int $servicioId = null): array
    {
        $query = CitaPivot::where('sede_id', $sedeId)
            ->where('estado', true);

        if ($servicioId) {
            $query->where('servicio_id', $servicioId);
        }

        $vinculados = $query->pluck('producto_id');

        $productos = ConfigProducto::with('recomendacionProducto')
            ->whereNotIn('id', $vinculados)
            ->get();

        return $this->success($productos);
    }
}
