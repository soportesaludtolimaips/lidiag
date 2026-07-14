<?php

namespace App\Http\Resources\Configuracion;

use App\Http\Resources\SedeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigServicioCitaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'sede_id' => $this->sede_id,
            'estado' => $this->estado,
            'sede' => SedeResource::make($this->whenLoaded('sede')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
