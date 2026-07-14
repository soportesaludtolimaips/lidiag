<?php

namespace App\Http\Resources\Estudios;

use Illuminate\Http\Resources\Json\JsonResource;

class EstudioPendienteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
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
            'sede_id' => $this->sede_id,
            'prioridad_id' => $this->prioridad_id,
            'observaciones' => $this->observaciones,
            'motivo_examen' => $this->motivo_examen,
            'atencion' => $this->atencion,
            'servicio' => $this->servicio,
            'eps' => $this->eps,
            'contrato' => $this->contrato,
            'plan' => $this->plan,
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'num_docu' => $this->num_docu,
            'nom_pacien' => $this->nom_pacien,
            'fec_naci' => $this->fec_naci,
            'sexo' => $this->sexo,
            'estudio_producto_id' => $this->estudio_producto_id,
            'cod_cups' => $this->cod_cups,
            'nom_produc' => $this->nom_produc,
            'fechor_lectura' => $this->fechor_lectura,
            'quien_registro' => $this->quien_registro,
            'nom_priori' => $this->nom_priori,
            'priori_nivel' => $this->priori_nivel,
            'priori_tiempo' => $this->priori_tiempo,
            'nom_sede' => $this->nom_sede,
            'url_oviyam' => $this->url_oviyam,
            'tap_oviyam' => $this->tap_oviyam,
        ];
    }
}
