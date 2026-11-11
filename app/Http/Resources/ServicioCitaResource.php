<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource JSON para la transformación de datos de un servicio de cita.
 *
 * Expone únicamente los campos esenciales (id y nombre) del servicio asociado
 * a una cita, utilizado en las respuestas de la API para listar servicios disponibles.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */
class ServicioCitaResource extends JsonResource
{
    /**
     * Transforma el recurso en un array asociativo.
     *
     * @param  Request  $request  Solicitud HTTP que origina la transformación.
     * @return array{ id: int, nombre: string } Datos serializados del servicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}
