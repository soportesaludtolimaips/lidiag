<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskTicketUpdateRequest extends FormRequest
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
     * Reglas de validación para actualizar un ticket de HelpDesk.
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
            'titulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'nullable|integer|exists:help_desk_categorias,id',
            'prioridad_id' => 'nullable|integer|exists:help_desk_prioridades,id',
        ];
    }
}
