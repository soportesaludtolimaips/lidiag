<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar la cancelación de una cita médica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class AgendaCitaCancelarRequest extends FormRequest
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
     * Define las reglas de validación para cancelar una cita.
     *
     * @returns array reglas de validación: motivo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "motivo" => "sometimes|nullable|string|max:1000",
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
            "motivo.max" => "El motivo de cancelación no puede exceder 1000 caracteres.",
        ];
    }
}
