<?php

namespace App\Http\Resources\Configuracion;

use App\Http\Resources\SedeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigWhatsappResource extends JsonResource
{
    /**
     * Datos de la configuración de WhatsApp en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sede_id' => $this->sede_id,
            'servicio_id' => $this->servicio_id,
            'mensaje_bienvenida' => $this->mensaje_bienvenida,
            'activo' => $this->activo,
            'sede' => SedeResource::make($this->whenLoaded('sede')),
            'servicio' => ConfigServicioResource::make($this->whenLoaded('servicio')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
