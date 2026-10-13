<?php

namespace App\Http\Resources\Estudios;

use Illuminate\Http\Resources\Json\JsonResource;

class EstudioDiagnosticoResource extends JsonResource
{
    /**
     * Datos del diagnóstico del estudio en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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
