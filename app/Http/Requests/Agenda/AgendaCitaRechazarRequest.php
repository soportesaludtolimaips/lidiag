<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los datos de rechazo de una cita médica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */
class AgendaCitaRechazarRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene permiso para realizar esta petición.
     *
     * @returns bool siempre retorna true.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación para el rechazo de una cita.
     *
     * @returns array reglas de validación: rechazo_motivo_id, rechazo_observacion.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function rules(): array
    {
        return [
            'rechazo_motivo_id' => 'required|exists:config_cita_rechazo_motivos,id',
            'rechazo_observacion' => 'nullable|string|max:500',
        ];
    }

    /**
     * Define los mensajes de error personalizados para las reglas de validación.
     *
     * @returns array mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function messages(): array
    {
        return [
            'rechazo_motivo_id.required' => 'El motivo de rechazo es obligatorio.',
            'rechazo_motivo_id.exists' => 'El motivo de rechazo seleccionado no existe.',
            'rechazo_observacion.max' => 'La observación no debe exceder 500 caracteres.',
        ];
    }
}
