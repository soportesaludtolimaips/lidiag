<?php

namespace App\Http\Resources\Admisiones;

use App\Models\Admisiones\AdminAdmision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para serializar registros de AdminAdmision con datos demográficos y trazabilidad DICOM.
 *
 * @mixin AdminAdmision
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class AdminAdmisionResource extends JsonResource
{
    /**
     * Transforma la instancia de AdminAdmision en un arreglo estructurado para el frontend.
     *
     * Flujo del proceso:
     * 1. Extrae relaciones cargadas de paciente, sede, producto, orden de worklist y usuarios.
     * 2. Construye el nombre completo normalizado del paciente.
     * 3. Expone campos propios de admisión y alias compatibles con los visores y asignadores DICOM (pat_id, pat_name, study_datetime).
     *
     * @param  Request  $request  Solicitud HTTP actual.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function toArray(Request $request): array
    {
        $paciente = $this->whenLoaded("paciente") ? $this->paciente : null;
        $producto = $this->whenLoaded("producto") ? $this->producto : null;
        $sede = $this->whenLoaded("sede") ? $this->sede : null;
        $worklist = $this->whenLoaded("worklistOrden") ? $this->worklistOrden : null;
        $usuarioAdm = $this->whenLoaded("usuarioAdmision") ? $this->usuarioAdmision : null;
        $usuarioAsig = $this->whenLoaded("usuarioAsignacion") ? $this->usuarioAsignacion : null;

        $nombreCompleto = $paciente
            ? trim("{$paciente->pri_ape} {$paciente->seg_ape} {$paciente->nombres} {$paciente->seg_nom}")
            : "";
        if ($nombreCompleto === "" && $paciente) {
            $nombreCompleto = (string) $paciente->nombres;
        }

        $studyDesc = $this->study_desc
            ?: ($producto?->nom_produc ?: "");

        $modality = $this->modality
            ?: ($worklist?->modality ?: "DX");

        $studyDatetime = $this->fecha_toma
            ? $this->fecha_toma->format("Y-m-d H:i:s")
            : ($this->fecha_admision ? $this->fecha_admision->format("Y-m-d H:i:s") : null);

        return [
            "id" => $this->id,
            "paciente_id" => $this->paciente_id,
            "sede_id" => $this->sede_id,
            "worklist_orden_id" => $this->worklist_orden_id,
            "estudio_id" => $this->estudio_id,
            "producto_id" => $this->producto_id,
            "usuario_admision_id" => $this->usuario_admision_id,
            "usuario_asignacion_id" => $this->usuario_asignacion_id,
            "accession_no" => $this->accession_no,
            "study_iuid" => $this->study_iuid,
            "study_id" => $this->study_id,
            "study_pk" => $this->study_pk,
            "modality" => $modality,
            "mods_in_study" => $modality,
            "study_desc" => $studyDesc,
            "study_datetime" => $studyDatetime,
            "fecha_admision" => $this->fecha_admision?->format("Y-m-d H:i:s"),
            "fecha_toma" => $this->fecha_toma?->format("Y-m-d H:i:s"),
            "fecha_asignacion" => $this->fecha_asignacion?->format("Y-m-d H:i:s"),
            "estado" => $this->estado,
            "atencion" => $this->atencion,
            "eps" => $this->eps,
            "contrato" => $this->contrato,
            "plan" => $this->plan,
            "servicio" => $this->servicio,
            "ubicacion" => $this->ubicacion,
            "cama" => $this->cama,
            "medico_ordena" => $this->medico_ordena,
            "observaciones" => $this->observaciones,

            // Alias directos para compatibilidad con componentes de estudios / visores DICOM
            "pat_id" => $paciente?->num_docu ?? "",
            "pat_name" => $nombreCompleto,
            "pat_sex" => $paciente?->sexo ?? "",
            "pat_birthdate" => $paciente?->fec_naci ?? "",

            "paciente" => $paciente ? [
                "id" => $paciente->id,
                "tipo_documento_id" => $paciente->tipo_documento_id,
                "tipo_documento" => $paciente->relationLoaded("tipoDocumento") && $paciente->tipoDocumento ? [
                    "id" => $paciente->tipoDocumento->id,
                    "codigo" => $paciente->tipoDocumento->codigo,
                    "tipo_documento" => $paciente->tipoDocumento->tipo_documento,
                ] : null,
                "num_docu" => $paciente->num_docu,
                "nombres" => $paciente->nombres,
                "seg_nom" => $paciente->seg_nom,
                "pri_ape" => $paciente->pri_ape,
                "seg_ape" => $paciente->seg_ape,
                "nombre_completo" => $nombreCompleto,
                "sexo" => $paciente->sexo,
                "fec_naci" => $paciente->fec_naci,
                "direccion" => $paciente->direccion,
                "tel" => $paciente->tel,
                "cel" => $paciente->cel,
                "email" => $paciente->email,
            ] : null,

            "producto" => $producto ? [
                "id" => $producto->id,
                "cod_cups" => $producto->cod_cups,
                "nom_produc" => $producto->nom_produc,
            ] : null,

            "sede" => $sede ? [
                "id" => $sede->id,
                "nom_sede" => $sede->nom_sede,
            ] : null,

            "worklist_orden" => $worklist ? [
                "id" => $worklist->id,
                "accession_no" => $worklist->accession_no,
                "study_iuid" => $worklist->study_iuid,
                "modality" => $worklist->modality,
                "station_aet" => $worklist->station_aet,
                "estado" => $worklist->estado,
                "study_pk" => $worklist->study_pk,
                "adquirido_at" => $worklist->adquirido_at?->format("Y-m-d H:i:s"),
            ] : null,

            "usuario_admision" => $usuarioAdm ? [
                "id" => $usuarioAdm->id,
                "name" => $usuarioAdm->name,
            ] : null,

            "usuario_asignacion" => $usuarioAsig ? [
                "id" => $usuarioAsig->id,
                "name" => $usuarioAsig->name,
            ] : null,
        ];
    }
}
