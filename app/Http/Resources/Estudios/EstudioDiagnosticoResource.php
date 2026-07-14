<?php

namespace App\Http\Resources\Estudios;

use Illuminate\Http\Resources\Json\JsonResource;

class EstudioDiagnosticoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'estudio_id' => $this->estudio_id,
            'cod_diagnos' => $this->cod_diagnos,
            'nom_diagnos' => $this->nom_diagnos,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
