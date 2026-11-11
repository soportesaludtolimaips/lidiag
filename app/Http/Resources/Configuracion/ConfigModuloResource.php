<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigModuloResource extends JsonResource
{
    /**
     * Datos del módulo de configuración en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'modulo' => $this->modulo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
