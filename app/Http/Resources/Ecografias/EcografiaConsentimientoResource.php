<?php

namespace App\Http\Resources\Ecografias;

use Illuminate\Http\Resources\Json\JsonResource;

class EcografiaConsentimientoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ecografia_producto_id' => $this->ecografia_producto_id,
            'producto_id' => $this->producto_id,
            'sede_id' => $this->sede_id,
            'config_modulo_id' => $this->config_modulo_id,
            'plantilla_id' => $this->plantilla_id,
            'plantilla_version' => $this->plantilla_version,
            'vigente' => $this->vigente,
            'estado' => $this->estado,
            'firmante_tipo' => $this->firmante_tipo,
            'token_expires_at' => $this->token_expires_at,
            'token_used_at' => $this->token_used_at,
            'firma_paciente_at' => $this->firma_paciente_at,
            'firma_medico_at' => $this->firma_medico_at,
            'medico_id' => $this->medico_id,
            'pdf_path' => $this->pdf_path,
            'pdf_generated_at' => $this->pdf_generated_at,
            'anulada_at' => $this->anulada_at,
            'anulacion_observacion' => $this->anulacion_observacion,
            'producto' => $this->whenLoaded('producto'),
            'ecografiaProducto' => $this->whenLoaded('ecografiaProducto'),
            'sede' => $this->whenLoaded('sede'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
