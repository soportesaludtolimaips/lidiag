<?php

namespace App\Http\Requests\Admisiones;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud validada para registrar una admisión clínica y enviarla al workflow de Rayos X.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class AdminAdmisionStoreRequest extends FormRequest
{
    /**
     * Autoriza la ejecución del request.
     *
     * Flujo del proceso:
     * 1. Retorna true permitiendo que el middleware del controlador verifique los permisos Spatie.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Asegura la presencia de sede_id antes de validar reglas.
     *
     * Flujo del proceso:
     * 1. Determina sede_id desde sesión o usuario cuando no se envía en el cuerpo.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->sede_id)) {
            $sedeId = session("sede_id")
                ?? auth()->user()?->sedes()?->first()?->id
                ?? ConfigSede::where("estado", true)->value("id");

            if ($sedeId) {
                $this->merge(["sede_id" => (int) $sedeId]);
            }
        }
    }

    /**
     * Reglas de validación para la creación de admisión (con paciente local o proveniente de interfaz).
     *
     * Flujo del proceso:
     * 1. Exige paciente_id o estructura demográfica paciente_interfaz.
     * 2. Valida sede, procedimiento CUPS, accession_no, modalidad y estación DICOM.
     * 3. Incluye campos asistenciales opcionales (atención, EPS, contrato, plan, ubicación, cama, médico ordenante).
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function rules(): array
    {
        return [
            "paciente_id" => "nullable|integer|exists:pacientes,id|required_without:paciente_interfaz",
            "paciente_interfaz" => "nullable|array|required_without:paciente_id",
            "paciente_interfaz.tipo_documento_id" => "nullable|integer|exists:config_tipos_documento,id",
            "paciente_interfaz.num_docu" => "required_with:paciente_interfaz|string|max:30",
            "paciente_interfaz.nombres" => "required_with:paciente_interfaz|string|max:150",
            "paciente_interfaz.seg_nom" => "nullable|string|max:100",
            "paciente_interfaz.pri_ape" => "nullable|string|max:100",
            "paciente_interfaz.seg_ape" => "nullable|string|max:100",
            "paciente_interfaz.sexo" => "nullable|string|max:5",
            "paciente_interfaz.fec_naci" => "nullable|date",
            "paciente_interfaz.direccion" => "nullable|string|max:200",
            "paciente_interfaz.tel" => "nullable|string|max:30",
            "paciente_interfaz.cel" => "nullable|string|max:30",
            "paciente_interfaz.email" => "nullable|email|max:120",
            "sede_id" => "required|integer|exists:config_sedes,id",
            "producto_id" => "required|integer|exists:config_productos,id",
            "accession_no" => "nullable|string|max:50",
            "modality" => "nullable|string|in:CR,DX,US",
            "station_aet" => "nullable|string|max:50",
            "atencion" => "nullable|string|max:100",
            "eps" => "nullable|string|max:150",
            "contrato" => "nullable|string|max:150",
            "plan" => "nullable|string|max:150",
            "servicio" => "nullable|string|max:150",
            "ubicacion" => "nullable|string|max:150",
            "cama" => "nullable|string|max:50",
            "medico_ordena" => "nullable|string|max:150",
            "observaciones" => "nullable|string|max:1000",
        ];
    }

    /**
     * Mensajes personalizados de validación en español.
     *
     * Flujo del proceso:
     * 1. Retorna los textos claros para el usuario ante campos faltantes o inválidos.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function messages(): array
    {
        return [
            "paciente_id.required_without" => "El paciente es obligatorio para registrar la admisión.",
            "paciente_interfaz.required_without" => "Los datos del paciente son obligatorios.",
            "sede_id.required" => "La sede asistencial es obligatoria.",
            "producto_id.required" => "Debe seleccionar el procedimiento radiológico a realizar.",
        ];
    }
}
