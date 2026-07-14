<?php

namespace App\Http\Resources\Configuracion;

use App\Http\Resources\SedeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigSedeWhatsappNumberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sede_id' => $this->sede_id,
            'phone_number_id' => $this->phone_number_id,
            'numero' => $this->numero,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
            'sede' => SedeResource::make($this->whenLoaded('sede')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
