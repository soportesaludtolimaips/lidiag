<?php

namespace App\Http\Resources\Configuracion;

use App\Http\Resources\SedeResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsentimientoInformadoResource extends JsonResource
{
    /**
     * Datos del consentimiento informado en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'sede_id' => $this->sede_id,
            'usuario_crea_id' => $this->usuario_crea_id,
            'nombre' => $this->nombre,
            'archivo' => $this->archivo,
            'estado' => $this->estado,
            'producto' => ConfigProductoResource::make($this->whenLoaded('producto')),
            'sede' => SedeResource::make($this->whenLoaded('sede')),
            'usuarioCrea' => UserResource::make($this->whenLoaded('usuarioCrea')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
