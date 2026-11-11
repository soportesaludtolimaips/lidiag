<?php

namespace App\Http\Resources\Configuracion;

use App\Http\Resources\SedeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigServicioResource extends JsonResource
{
    /**
     * Datos del servicio de configuración en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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
