<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskReassignRequest extends FormRequest
{
    /**
     * Autoriza la petición (sin restricciones adicionales).
     *
     * @return bool
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Reglas de validación para reasignar un ticket.
     *
     * @return array
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules()
    {
        return [
            'asignado_id' => 'required|integer|exists:users,id',
            'comentario' => 'required|string|max:10000',
        ];
    }

    /**
     * Mensajes en español para las reglas de validación.
     *
     * @return array
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages()
    {
        return [
            'asignado_id.required' => 'El usuario asignado es obligatorio',
            'comentario.required' => 'El comentario es obligatorio',
        ];
    }
}
