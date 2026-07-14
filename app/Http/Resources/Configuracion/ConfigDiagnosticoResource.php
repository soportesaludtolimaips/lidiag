<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigDiagnosticoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cod_diagnos' => $this->cod_diagnos,
            'nom_diagnos' => $this->nom_diagnos,
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
