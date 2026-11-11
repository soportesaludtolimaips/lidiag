<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskChangeStatusRequest extends FormRequest
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
     * Reglas de validación para cambiar el estado de un ticket.
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
            'estado_id' => 'required|integer|exists:help_desk_estados,id',
            'comentario' => 'nullable|string|max:10000',
            'descripcion_solucion' => 'nullable|string|max:10000',
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
            'estado_id.required' => 'El estado es obligatorio',
        ];
    }
}
