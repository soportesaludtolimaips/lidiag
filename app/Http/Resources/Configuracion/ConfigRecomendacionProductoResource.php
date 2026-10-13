<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigRecomendacionProductoResource extends JsonResource
{
    /**
     * Datos de la recomendación del producto en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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
