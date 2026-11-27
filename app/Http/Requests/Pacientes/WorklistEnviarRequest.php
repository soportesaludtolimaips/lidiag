<?php

namespace App\Http\Requests\Pacientes;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud validada para enviar un paciente admitido (local o desde interfaz) a la Modality Worklist.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class WorklistEnviarRequest extends FormRequest
{
    /**
     * Autoriza la petición delegando el control de permisos al controlador.
     *
     * Flujo del proceso:
     * 1. Retorna true permitiendo validar las reglas de negocio.
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
     * Prepara los datos antes de la validación asegurando que sede_id exista si no vino en el request.
     *
     * Flujo del proceso:
     * 1. Si sede_id viene vacío, resuelve la sede activa de sesión o del usuario autenticado.
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
     * Reglas de validación para admisión y envío a Worklist (soportando paciente local o importado de interfaz).
     *
     * Flujo del proceso:
     * 1. Exige paciente_id existente o bloque demográfico paciente_interfaz para persistencia al admitir.
     * 2. Valida sede, procedimiento CUPS, accession_no, modalidad y estación DICOM.
     * 3. Declara explícitamente atributos asistenciales opcionales para admin_admisiones.
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
            "accession_no" => "required|string|max:50",
            "modality" => "nullable|string|in:CR,DX,US",
            "station_aet" => "nullable|string|max:50",
            "ecografia_id" => "nullable|integer|exists:ecografias,id",
            "ecografia_producto_id" => "nullable|integer|exists:ecografias_productos,id",
            "atencion" => "nullable|string|max:100",
            "eps" => "nullable|string|max:150",
            "contrato" => "nullable|string|max:150",
            "plan" => "nullable|string|max:150",
            "servicio" => "nullable|string|max:150",
            "ubicacion" => "nullable|string|max:150",
            "cama" => "nullable|string|max:50",
            "medico_ordena" => "nullable|string|max:150",
            "observaciones" => "nullable|string|max:500",
        ];
    }

    /**
     * Mensajes personalizados de validación en español.
     *
     * Flujo del proceso:
     * 1. Retorna los mensajes descriptivos para cada regla crítica.
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
            "paciente_id.required_without" => "El paciente es obligatorio.",
            "paciente_id.exists" => "El paciente no existe en el sistema.",
            "paciente_interfaz.required_without" => "Los datos del paciente son obligatorios.",
            "paciente_interfaz.num_docu.required_with" => "El número de documento del paciente es obligatorio.",
            "paciente_interfaz.nombres.required_with" => "El nombre del paciente es obligatorio.",
            "sede_id.required" => "La sede es obligatoria.",
            "sede_id.exists" => "La sede seleccionada no es válida.",
            "producto_id.required" => "Debe seleccionar el procedimiento a realizar.",
            "producto_id.exists" => "El procedimiento seleccionado no es válido.",
            "accession_no.required" => "El Accession Number es obligatorio.",
            "modality.in" => "La modalidad debe ser CR, DX o US.",
        ];
    }
}
