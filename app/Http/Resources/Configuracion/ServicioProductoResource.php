<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ServicioProductoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'servicio_cita_id' => $this->servicio_cita_id,
            'producto_id' => $this->producto_id,
            'estado' => $this->estado,
            'servicio' => ConfigServicioCitaResource::make($this->whenLoaded('servicio')),
            'producto' => ConfigProductoResource::make($this->whenLoaded('producto')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
