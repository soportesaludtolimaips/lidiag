<?php

namespace App\Http\Resources\Ecografias;

use Illuminate\Http\Resources\Json\JsonResource;

class EcografiaResource extends JsonResource
{
    /**
     * Datos de la ecografía en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'medico_id' => $this->medico_id,
            'paciente_id' => $this->paciente_id,
            'usua_regis_id' => $this->usua_regis_id,
            'admin_salud_id' => $this->admin_salud_id,
            'atencion_id' => $this->atencion_id,
            'fec_cita_sahi' => $this->fec_cita_sahi,
            'fec_regis_cita_sahi' => $this->fec_regis_cita_sahi,
            'contrato' => $this->contrato,
            'plan' => $this->plan,
            'quien_ordena' => $this->quien_ordena,
            'estado' => $this->estado,
            'motivo_anula' => $this->motivo_anula,
            'usua_anula_id' => $this->usua_anula_id,
            'fechor_anula' => $this->fechor_anula,
            'paciente' => $this->whenLoaded('paciente'),
            'usuarioRegistrador' => $this->whenLoaded('usuarioRegistrador'),
            'adminSalud' => $this->whenLoaded('adminSalud'),
            'medico' => $this->whenLoaded('medico'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
