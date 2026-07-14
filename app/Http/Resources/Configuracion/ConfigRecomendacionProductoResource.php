<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigRecomendacionProductoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'recomendacion' => $this->recomendacion,
            'estado' => $this->estado,
            'producto' => ConfigProductoResource::make($this->whenLoaded('producto')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
