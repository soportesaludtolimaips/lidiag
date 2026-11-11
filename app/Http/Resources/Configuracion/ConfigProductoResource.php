<?php

namespace App\Http\Resources\Configuracion;

use App\Models\Configuracion\ConfigProducto;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Datos del producto de configuración en formato array.
 *
 * @mixin ConfigProducto
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-11
 */
class ConfigProductoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cod_cups' => $this->cod_cups,
            'nom_produc' => $this->nom_produc,
            'requi_lectura' => $this->requi_lectura,
            'tipo_oral' => $this->tipo_oral,
            'tipo_venoso' => $this->tipo_venoso,
            'factura' => $this->factura,
            'estado' => $this->estado,
            'servicio_id' => $this->servicio_id,
            'servicio' => ConfigServicioResource::make($this->whenLoaded('servicio')),
            'recomendacionProducto' => ConfigRecomendacionProductoResource::make($this->whenLoaded('recomendacionProducto')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
