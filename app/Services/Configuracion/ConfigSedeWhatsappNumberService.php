<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigSedeWhatsappNumber;
use App\Services\BaseService;

class ConfigSedeWhatsappNumberService extends BaseService
{
    /**
     * Obtiene los números de WhatsApp, filtrando por sede si se indica.
     *
     * @param  int|null  $sedeId  Identificador de la sede para filtrar (opcional).
     *
     * @returns array Respuesta con la lista de números de WhatsApp y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(?int $sedeId = null): array
    {
        $query = ConfigSedeWhatsappNumber::with('sede');

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        return $this->success($query->get());
    }

    /**
     * Crea un nuevo número de WhatsApp con los datos proporcionados.
     *
     * @param  array  $data  Datos validados del número de WhatsApp a crear.
     *
     * @returns array Respuesta con el número de WhatsApp creado y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigSedeWhatsappNumber::create($data);

        return $this->created($item->load('sede'));
    }

    /**
     * Actualiza un número de WhatsApp existente con los datos proporcionados.
     *
     * @param  int  $id  Identificador único del número de WhatsApp.
     * @param  array  $data  Datos validados a actualizar.
     *
     * @returns array Respuesta con el número de WhatsApp actualizado y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigSedeWhatsappNumber::findOrFail($id);
        $item->update($data);

        return $this->updated($item->load('sede'));
    }

    /**
     * Elimina un número de WhatsApp existente según su identificador.
     *
     * @param  int  $id  Identificador único del número de WhatsApp a eliminar.
     *
     * @returns array Respuesta con el estado de la operación de eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigSedeWhatsappNumber::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }

    /**
     * Busca un número de WhatsApp por su identificador.
     *
     * @param  int  $id  Identificador único del número de WhatsApp.
     *
     * @returns array Respuesta con los datos del número de WhatsApp o un error si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConfigSedeWhatsappNumber::with('sede')->find($id);

        if (! $item) {
            return $this->notFound('Número de WhatsApp no encontrado');
        }

        return $this->success($item);
    }
}
