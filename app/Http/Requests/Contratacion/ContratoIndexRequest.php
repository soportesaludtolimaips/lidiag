<?php

namespace App\Http\Requests\Contratacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar filtros de búsqueda y paginación en contratos.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class ContratoIndexRequest extends FormRequest
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
     * Define las reglas de validación para listar contratos.
     *
     * @returns array reglas de validación: sede_id, estado, q, per_page, page.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "sede_id" => "sometimes|nullable|integer|exists:config_sedes,id",
            "estado" => "sometimes|nullable|in:0,1",
            "q" => "sometimes|nullable|string|max:100",
            "per_page" => "sometimes|nullable|integer|min:1|max:200",
            "page" => "sometimes|nullable|integer|min:1",
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
            "sede_id.exists" => "La sede seleccionada no existe.",
            "estado.in" => "El estado debe ser activo (1) o inactivo (0).",
            "per_page.min" => "La cantidad de registros por página debe ser al menos 1.",
            "per_page.max" => "La cantidad máxima de registros por página es 200.",
        ];
    }
}
