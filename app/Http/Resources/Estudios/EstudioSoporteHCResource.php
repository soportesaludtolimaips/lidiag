<?php

namespace App\Http\Resources\Estudios;

use Illuminate\Http\Resources\Json\JsonResource;

class EstudioSoporteHCResource extends JsonResource
{
    /**
     * Datos del soporte de historia clínica del estudio en formato array.
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
            'archivo_original' => $this->archivo_original,
            'archivo_encrip' => $this->archivo_encrip,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
