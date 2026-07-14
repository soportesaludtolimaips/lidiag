<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigProductoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cod_cups' => $this->cod_cups,
            'nom_produc' => $this->nom_produc,
            'porcen' => $this->porcen,
            'val_produc' => $this->val_produc,
            'requi_lectura' => $this->requi_lectura,
            'tipo_oral' => $this->tipo_oral,
            'tipo_venoso' => $this->tipo_venoso,
            'factura' => $this->factura,
            'estado' => $this->estado,
            'servicio_id' => $this->servicio_id,
            'recomendacionProducto' => ConfigRecomendacionProductoResource::make($this->whenLoaded('recomendacionProducto')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
