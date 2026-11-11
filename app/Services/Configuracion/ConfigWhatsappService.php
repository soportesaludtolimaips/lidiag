<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigWhatsapp;
use App\Services\BaseService;

class ConfigWhatsappService extends BaseService
{
    /**
     * Obtiene la lista de configuraciones de WhatsApp, filtradas por sede si se indica.
     *
     * @param  int|null  $sedeId  Identificador de la sede para filtrar las configuraciones.
     *
     * @returns array Arreglo con la lista de configuraciones y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(?int $sedeId = null): array
    {
        $query = ConfigWhatsapp::with(['sede', 'servicio']);

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        return $this->success($query->get());
    }

    /**
     * Busca una configuración de WhatsApp por su identificador.
     *
     * @param  int  $id  Identificador de la configuración de WhatsApp.
     *
     * @returns array Arreglo con la configuración encontrada o un mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConfigWhatsapp::with(['sede', 'servicio'])->find($id);

        if (! $item) {
            return $this->notFound('Configuración de WhatsApp no encontrada');
        }

        return $this->success($item);
    }

    /**
     * Crea una nueva configuración de WhatsApp con los datos proporcionados.
     *
     * @param  array  $data  Datos validados para la creación de la configuración.
     *
     * @returns array Arreglo con la configuración creada y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigWhatsapp::create($data);

        return $this->created($item->load(['sede', 'servicio']));
    }

    /**
     * Actualiza una configuración de WhatsApp existente con los datos proporcionados.
     *
     * @param  int  $id  Identificador de la configuración de WhatsApp a actualizar.
     * @param  array  $data  Datos validados para la actualización de la configuración.
     *
     * @returns array Arreglo con la configuración actualizada y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigWhatsapp::findOrFail($id);
        $item->update($data);

        return $this->updated($item->load(['sede', 'servicio']));
    }

    /**
     * Elimina una configuración de WhatsApp por su identificador.
     *
     * @param  int  $id  Identificador de la configuración de WhatsApp a eliminar.
     *
     * @returns array Arreglo con el resultado de la eliminación y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigWhatsapp::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }

    /**
     * Busca la configuración de WhatsApp para una sede y un servicio de cita específicos.
     *
     * @param  int  $sedeId  Identificador de la sede.
     * @param  int  $servicioCitaId  Identificador del servicio de cita.
     *
     * @returns array Arreglo con la configuración encontrada o un mensaje de bienvenida por defecto.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function buscarPorSedeYServicio(int $sedeId, int $servicioCitaId): array
    {
        $config = ConfigWhatsapp::paraSedeYservicio($sedeId, $servicioCitaId)->first();

        if (! $config) {
            return $this->success([
                'mensaje_bienvenida' => 'Bienvenido a Lidiag, te notificaremos cuando tu resultado esté listo.',
            ]);
        }

        return $this->success($config);
    }
}
