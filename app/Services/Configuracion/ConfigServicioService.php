<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigServicio;
use App\Services\BaseService;

class ConfigServicioService extends BaseService
{
    /**
     * Obtiene los servicios de citas, filtrando por sede si se indica.
     *
     * @param  int|null  $sedeId  Identificador de la sede para filtrar (opcional).
     *
     * @returns array Respuesta con la lista de servicios de citas y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(?int $sedeId = null): array
    {
        $query = ConfigServicio::with('sede');

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        return $this->success($query->get());
    }

    /**
     * Busca un servicio de cita por su identificador.
     *
     * @param  int  $id  Identificador único del servicio de cita.
     *
     * @returns array Respuesta con los datos del servicio de cita o un error si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConfigServicio::with('sede')->find($id);

        if (! $item) {
            return $this->notFound('Servicio de cita no encontrado');
        }

        return $this->success($item);
    }

    /**
     * Crea un nuevo servicio de cita con los datos proporcionados.
     *
     * @param  array  $data  Datos validados del servicio de cita a crear.
     *
     * @returns array Respuesta con el servicio de cita creado y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigServicio::create($data);

        return $this->created($item->load('sede'));
    }

    /**
     * Actualiza un servicio de cita existente con los datos proporcionados.
     *
     * @param  int  $id  Identificador único del servicio de cita.
     * @param  array  $data  Datos validados a actualizar.
     *
     * @returns array Respuesta con el servicio de cita actualizado y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigServicio::findOrFail($id);
        $item->update($data);

        return $this->updated($item->load('sede'));
    }

    /**
     * Elimina un servicio de cita existente según su identificador.
     *
     * @param  int  $id  Identificador único del servicio de cita a eliminar.
     *
     * @returns array Respuesta con el estado de la operación de eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigServicio::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }
}
