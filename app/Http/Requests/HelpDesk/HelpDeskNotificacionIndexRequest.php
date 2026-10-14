<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los parámetros de paginación y filtrado de notificaciones de Help Desk.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class HelpDeskNotificacionIndexRequest extends FormRequest
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
     * Define las reglas de validación para las notificaciones.
     *
     * @returns array reglas de validación: limit, page, unread_only.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "limit" => "sometimes|nullable|integer|min:1|max:100",
            "page" => "sometimes|nullable|integer|min:1",
            "unread_only" => "sometimes|nullable|boolean",
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
            "limit.integer" => "El límite debe ser un número entero.",
            "limit.min" => "El límite mínimo es 1.",
            "limit.max" => "El límite máximo es 100.",
            "page.integer" => "La página debe ser un número entero.",
            "unread_only.boolean" => "El campo unread_only debe ser verdadero o falso.",
        ];
    }
}
