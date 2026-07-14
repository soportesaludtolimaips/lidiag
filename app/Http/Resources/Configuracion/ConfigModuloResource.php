<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigModuloResource extends JsonResource
{
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
