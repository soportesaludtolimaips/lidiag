<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigModulo;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use App\Models\Configuracion\ConfigServicio;
use App\Services\BaseService;

class ConfigRecomendacionProductoService extends BaseService
{
    /**
     * Obtiene las recomendaciones de producto, filtrando por producto, estado, sede y servicios habilitados para el usuario.
     *
     * Flujo del proceso:
     * 1. Determina el usuario autenticado y sus servicios de cita asignados (si aplica restricción).
     * 2. Si se proporciona una sede, filtra los servicios activos de dicha sede y los cruza con los habilitados para el usuario.
     * 3. Resuelve los módulos clínicos correspondientes a dichos servicios activos.
     * 4. Filtra las recomendaciones cuyos productos pertenezcan a los módulos o servicios habilitados.
     * 5. Aplica filtros opcionales de producto y estado, retornando la colección con su relación de producto y módulo.
     *
     * @param  int|null  $productoId  Identificador del producto para filtrar (opcional).
     * @param  bool|null  $estado  Estado de la recomendación para filtrar (opcional).
     * @param  int|null  $sedeId  Identificador de la sede seleccionada (opcional).
     * @param  int|null  $servicioId  Identificador del servicio específico (opcional).
     *
     * @returns array Respuesta con la lista de recomendaciones y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function all(
        ?int $productoId = null,
        ?bool $estado = null,
        ?int $sedeId = null,
        ?int $servicioId = null,
    ): array {
        $user = auth("sanctum")->user() ?: auth()->user();

        $query = ConfigRecomendacionProducto::with(["producto.modulo", "producto.servicio.sede"]);

        // Filtrar por sede y servicios habilitados para el usuario logueado
        if ($sedeId || ($user && $user->serviciosCita()->exists())) {
            $serviciosQuery = ConfigServicio::where("estado", true);

            if ($sedeId) {
                $serviciosQuery->where("sede_id", $sedeId);
            }

            // Si el usuario no es Administrador y tiene servicios específicos asignados, restringir a ellos
            if ($user && ! $user->hasRole("Administrador")) {
                $userServiciosIds = $user->serviciosCita()->pluck("config_servicios.id")->toArray();
                if (! empty($userServiciosIds)) {
                    $serviciosQuery->whereIn("id", $userServiciosIds);
                }
            }

            if ($servicioId) {
                $serviciosQuery->where("id", $servicioId);
            }

            $servicios = $serviciosQuery->get();

            if ($servicios->isEmpty()) {
                return $this->success([]);
            }

            // Resolver los módulos correspondientes a los servicios
            $modulos = ConfigModulo::all();
            $moduloIds = [];
            foreach ($servicios as $srv) {
                $nombre = mb_strtolower($srv->nombre);
                foreach ($modulos as $mod) {
                    $modNombre = mb_strtolower($mod->modulo);
                    if ($nombre === $modNombre
                        || (str_contains($nombre, "rayo") && $modNombre === "rx")
                        || (str_contains($nombre, "rx") && $modNombre === "rx")
                        || (str_contains($nombre, "ecograf") && str_contains($modNombre, "ecograf"))
                        || (str_contains($nombre, "tomograf") && str_contains($modNombre, "tomograf"))
                        || (str_contains($nombre, "resonanc") && str_contains($modNombre, "resonanc"))
                        || (str_contains($nombre, "mamograf") && str_contains($modNombre, "mamograf"))
                        || (str_contains($nombre, "oftalmo") && str_contains($modNombre, "oftalmo"))
                    ) {
                        $moduloIds[] = $mod->id;
                    }
                }
            }
            $moduloIds = array_unique($moduloIds);
            $servicioIds = $servicios->pluck("id")->toArray();

            $query->whereHas("producto", function ($pq) use ($moduloIds, $servicioIds) {
                $pq->where(function ($sub) use ($moduloIds, $servicioIds) {
                    $applied = false;
                    if (! empty($moduloIds)) {
                        $sub->whereIn("modulo_id", $moduloIds);
                        $applied = true;
                    }
                    if (! empty($servicioIds)) {
                        if ($applied) {
                            $sub->orWhereIn("servicio_id", $servicioIds);
                        } else {
                            $sub->whereIn("servicio_id", $servicioIds);
                        }
                    }
                });
            });
        }

        if ($productoId) {
            $query->where("producto_id", $productoId);
        }

        if ($estado !== null) {
            $query->where("estado", $estado);
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
