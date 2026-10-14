<?php

namespace App\Http\Requests\Contratacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar la sincronización y previsualización del detalle de médicos-producto en un contrato.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class ContratoSyncMedicosRequest extends FormRequest
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
     * Define las reglas de validación para el detalle de médicos-producto.
     *
     * @returns array reglas de validación: medicos, retirar_faltantes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "medicos" => "required|array",
            "medicos.*.medico_id" => "required|integer|exists:users,id",
            "medicos.*.producto_id" => "required|integer|exists:config_productos,id",
            "medicos.*.valor_consulta" => "required|numeric|min:0",
            "medicos.*.valor_urgencias" => "required|numeric|min:0",
            "medicos.*.fecha_ingreso" => "nullable|date",
            "retirar_faltantes" => "sometimes|boolean",
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
            "medicos.required" => "La lista de médicos es obligatoria.",
            "medicos.array" => "El formato de médicos debe ser un arreglo.",
            "medicos.*.medico_id.required" => "El identificador del médico es obligatorio.",
            "medicos.*.medico_id.exists" => "Uno o más médicos seleccionados no existen.",
            "medicos.*.producto_id.required" => "El identificador del producto es obligatorio.",
            "medicos.*.producto_id.exists" => "Uno o más productos seleccionados no existen.",
            "medicos.*.valor_consulta.required" => "El valor de consulta es obligatorio.",
            "medicos.*.valor_consulta.numeric" => "El valor de consulta debe ser un número.",
            "medicos.*.valor_urgencias.required" => "El valor de urgencias es obligatorio.",
            "medicos.*.valor_urgencias.numeric" => "El valor de urgencias debe ser un número.",
        ];
    }
}
