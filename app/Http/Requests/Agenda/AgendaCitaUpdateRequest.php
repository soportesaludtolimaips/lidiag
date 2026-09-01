<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los datos de actualización de una cita médica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */
class AgendaCitaUpdateRequest extends FormRequest
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
     * Define las reglas de validación para la actualización de una cita.
     *
     * @returns array reglas de validación: observaciones.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function rules(): array
    {
        return [
            'observaciones' => 'nullable|string',
        ];
    }
}
