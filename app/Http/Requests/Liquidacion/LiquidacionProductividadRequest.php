<?php

namespace App\Http\Requests\Liquidacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar la consulta del reporte de productividad mensual por médico.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class LiquidacionProductividadRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene permiso para realizar esta petición.
     *
     * @returns bool siempre retorna true.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación para productividad.
     *
     * @returns array reglas de validación: sede_id, periodo (YYYY-MM).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "sede_id" => "required|integer|exists:config_sedes,id",
            "periodo" => ["required", "regex:/^\\d{4}-(0[1-9]|1[0-2])$/"],
        ];
    }

    /**
     * Define los mensajes de error personalizados para las reglas de validación.
     *
     * @returns array mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages(): array
    {
        return [
            "sede_id.required" => "La sede es obligatoria.",
            "sede_id.exists" => "La sede seleccionada no existe.",
            "periodo.required" => "El periodo es obligatorio.",
            "periodo.regex" => "El periodo debe tener el formato YYYY-MM (ej. 2026-10).",
        ];
    }
}
