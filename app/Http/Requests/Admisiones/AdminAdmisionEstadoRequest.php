<?php

namespace App\Http\Requests\Admisiones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud validada para actualizar el estado y trazabilidad DICOM de una admisión en admin_admisiones.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class AdminAdmisionEstadoRequest extends FormRequest
{
    /**
     * Autoriza la petición delegando permisos al controlador.
     *
     * Flujo del proceso:
     * 1. Retorna true para continuar a la validación del cuerpo.
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
     * Reglas de validación para la transición de estado de una admisión.
     *
     * Flujo del proceso:
     * 1. Exige el campo estado y valida identificadores opcionales de estudio PACS (study_iuid, study_id, study_pk).
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
            "estado" => "required|string|max:40",
            "study_iuid" => "nullable|string|max:128",
            "study_id" => "nullable|string|max:50",
            "study_pk" => "nullable|integer",
            "fecha_toma" => "nullable|date",
            "observaciones" => "nullable|string|max:1000",
        ];
    }

    /**
     * Mensajes personalizados de validación.
     *
     * Flujo del proceso:
     * 1. Retorna mensajes claros en español.
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
            "estado.required" => "El nuevo estado de la admisión es obligatorio.",
        ];
    }
}
