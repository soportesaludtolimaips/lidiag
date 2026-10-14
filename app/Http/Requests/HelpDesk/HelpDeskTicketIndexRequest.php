<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los filtros de búsqueda y paginación de tickets de Help Desk.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class HelpDeskTicketIndexRequest extends FormRequest
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
     * Define las reglas de validación para el filtrado de tickets.
     *
     * @returns array reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "scope" => "sometimes|nullable|string|in:mine,assigned,all",
            "estado_id" => "sometimes|nullable|integer|exists:help_desk_estados,id",
            "categoria_id" => "sometimes|nullable|integer|exists:help_desk_categorias,id",
            "prioridad_id" => "sometimes|nullable|integer|exists:help_desk_prioridades,id",
            "sede_id" => "sometimes|nullable|integer|exists:config_sedes,id",
            "desde" => "sometimes|nullable|date",
            "hasta" => "sometimes|nullable|date",
            "fechaIni" => "sometimes|nullable|date",
            "fechaFin" => "sometimes|nullable|date",
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
            "scope.in" => "El alcance (scope) debe ser 'mine', 'assigned' o 'all'.",
            "estado_id.exists" => "El estado seleccionado no existe.",
            "categoria_id.exists" => "La categoría seleccionada no existe.",
            "prioridad_id.exists" => "La prioridad seleccionada no existe.",
            "sede_id.exists" => "La sede seleccionada no existe.",
            "per_page.min" => "La cantidad de registros por página debe ser al menos 1.",
            "per_page.max" => "La cantidad máxima de registros por página es 200.",
        ];
    }
}
