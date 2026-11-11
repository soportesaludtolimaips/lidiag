<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigPlantillaTipoResource extends JsonResource
{
    /**
     * Datos del tipo de plantilla en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nom_tipo' => $this->nom_tipo,
            'producto_id' => $this->producto_id,
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
