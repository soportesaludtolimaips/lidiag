<?php

namespace App\Http\Resources\Configuracion;

use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigPlantillaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'plantilla_tipo_id' => $this->plantilla_tipo_id,
            'medico_id' => $this->medico_id,
            'nom_plantilla' => $this->nom_plantilla,
            'plantilla' => $this->plantilla,
            'opinion' => $this->opinion,
            'estado' => $this->estado,
            'plantillaTipo' => ConfigPlantillaTipoResource::make($this->whenLoaded('plantillaTipo')),
            'medico' => UserResource::make($this->whenLoaded('medico')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
