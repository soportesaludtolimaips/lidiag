<?php

namespace App\Http\Resources\Estudios;

use Illuminate\Http\Resources\Json\JsonResource;

class EstudioProductoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'estudio_id' => $this->estudio_id,
            'producto_id' => $this->producto_id,
            'cod_cups' => $this->cod_cups,
            'nom_produc' => $this->nom_produc,
            'lectura' => $this->lectura,
            'fechor_lectura' => $this->fechor_lectura,
            'transcriptor_id' => $this->transcriptor_id,
            'fechor_trascrito' => $this->fechor_trascrito,
            'visto' => $this->visto,
            'activo' => $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
