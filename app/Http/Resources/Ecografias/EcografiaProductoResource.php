<?php

namespace App\Http\Resources\Ecografias;

use App\Http\Resources\Configuracion\ConfigProductoResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EcografiaProductoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ecografia_id' => $this->ecografia_id,
            'usua_regis_id' => $this->usua_regis_id,
            'producto_id' => $this->producto_id,
            'lectura' => $this->lectura,
            'opinion' => $this->opinion,
            'fechor_lectura' => $this->fechor_lectura,
            'estado' => $this->estado,
            'estado_envio_sahi' => $this->estado_envio_sahi,
            'fechor_envio_sahi' => $this->fechor_envio_sahi,
            'sahi_esquema_149' => $this->sahi_esquema_149,
            'sahi_esquema_150' => $this->sahi_esquema_150,
            'error_envio_sahi' => $this->error_envio_sahi,
            'ecografia' => $this->whenLoaded('ecografia'),
            'productos' => ConfigProductoResource::make($this->whenLoaded('productos')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
