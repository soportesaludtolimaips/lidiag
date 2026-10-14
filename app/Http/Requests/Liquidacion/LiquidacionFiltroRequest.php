<?php

namespace App\Http\Requests\Liquidacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los filtros del cálculo y exportación de liquidación.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class LiquidacionFiltroRequest extends FormRequest
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
     * Define las reglas de validación para los filtros de liquidación.
     *
     * @returns array reglas de validación: sede_id, fecha_inicio, fecha_fin, contrato_id, medico_id.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "sede_id" => "required|integer|exists:config_sedes,id",
            "fecha_inicio" => "required|date",
            "fecha_fin" => "required|date|after_or_equal:fecha_inicio",
            "contrato_id" => "sometimes|nullable|integer|exists:contratos,id",
            "medico_id" => "sometimes|nullable|integer|exists:users,id",
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
            "fecha_inicio.required" => "La fecha de inicio es obligatoria.",
            "fecha_inicio.date" => "La fecha de inicio no es una fecha válida.",
            "fecha_fin.required" => "La fecha de fin es obligatoria.",
            "fecha_fin.date" => "La fecha de fin no es una fecha válida.",
            "fecha_fin.after_or_equal" => "La fecha de fin debe ser igual o posterior a la fecha de inicio.",
            "contrato_id.exists" => "El contrato seleccionado no existe.",
            "medico_id.exists" => "El médico seleccionado no existe.",
        ];
    }
}
