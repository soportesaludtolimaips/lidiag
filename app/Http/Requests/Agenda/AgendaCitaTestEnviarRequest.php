<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar el envío de mensajes de prueba de WhatsApp.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class AgendaCitaTestEnviarRequest extends FormRequest
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
     * Define las reglas de validación para enviar un mensaje de prueba.
     *
     * @returns array reglas de validación: telefono, mensaje.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "telefono" => "required|string|max:20",
            "mensaje" => "required|string|max:1000",
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
            "telefono.required" => "El número de teléfono es obligatorio.",
            "telefono.max" => "El número de teléfono no puede superar 20 caracteres.",
            "mensaje.required" => "El mensaje es obligatorio.",
            "mensaje.max" => "El mensaje no puede superar 1000 caracteres.",
        ];
    }
}
