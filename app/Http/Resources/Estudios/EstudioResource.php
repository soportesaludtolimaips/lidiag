<?php

namespace App\Http\Resources\Estudios;

use Illuminate\Http\Resources\Json\JsonResource;

class EstudioResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sede_id' => $this->sede_id,
            'study_pk' => $this->study_pk,
            'study_iuid' => $this->study_iuid,
            'study_id' => $this->study_id,
            'fec_estudio' => $this->fec_estudio,
            'accession_no' => $this->accession_no,
            'study_desc' => $this->study_desc,
            'mods_in_study' => $this->mods_in_study,
            'email_reportar' => $this->email_reportar,
            'paciente_id' => $this->paciente_id,
            'medico_id' => $this->medico_id,
            'quien_registro_id' => $this->quien_registro_id,
            'prioridad_id' => $this->prioridad_id,
            'observaciones' => $this->observaciones,
            'motivo_examen' => $this->motivo_examen,
            'atencion' => $this->atencion,
            'servicio' => $this->servicio,
            'eps' => $this->eps,
            'contrato' => $this->contrato,
            'plan' => $this->plan,
            'destino_estudio' => $this->destino_estudio,
            'destino_estudio_id' => $this->destino_estudio_id,
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'productos' => EstudioProductoResource::collection($this->whenLoaded('productos')),
            'diagnosticos' => EstudioDiagnosticoResource::collection($this->whenLoaded('diagnosticos')),
            'soportes' => EstudioSoporteHCResource::collection($this->whenLoaded('soportes')),
        ];
    }
}
