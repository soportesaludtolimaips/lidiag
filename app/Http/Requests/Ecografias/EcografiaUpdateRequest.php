<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaUpdateRequest extends FormRequest
{
    /**
     * Autoriza la petición (sin restricciones adicionales).
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
     * Reglas de validación para actualizar una ecografía.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'medico_id' => 'nullable|integer',
            'sede_id' => 'nullable|integer',
            'prioridad_id' => 'nullable|integer',
            'observaciones' => 'nullable|string',
        ];
    }

    /**
     * Mensajes en español para las reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages(): array
    {
        return [
            'medico_id.integer' => 'El médico debe ser un número entero.',
            'sede_id.integer' => 'La sede debe ser un número entero.',
            'prioridad_id.integer' => 'La prioridad debe ser un número entero.',
            'observaciones.string' => 'Las observaciones deben ser texto.',
        ];
    }
}
