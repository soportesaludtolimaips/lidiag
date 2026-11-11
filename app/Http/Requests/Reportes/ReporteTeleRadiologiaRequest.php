<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReporteTeleRadiologiaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el reporte de tele-radiología.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function rules(): array
    {
        return [
            "sedeId" => "nullable|integer",
            "fechaIni" => "required|date",
            "fechaFin" => "required|date|after_or_equal:fechaIni",
        ];
    }

    /**
     * Mensajes de error en español para las reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function messages(): array
    {
        return [
            "fechaIni.required" => "Debes seleccionar la fecha inicio.",
            "fechaFin.required" => "Debes seleccionar la fecha fin.",
            "fechaFin.after_or_equal" => "La fecha fin no puede ser menor que la fecha inicio.",
        ];
    }
}
