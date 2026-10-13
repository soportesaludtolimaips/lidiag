<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskTicketStoreRequest extends FormRequest
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
     * Reglas de validación para crear un ticket de HelpDesk.
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
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:50000',
            'categoria_id' => 'required|integer|exists:help_desk_categorias,id',
            'prioridad_id' => 'required|integer|exists:help_desk_prioridades,id',
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'asignado_id' => 'required|integer|exists:users,id',
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
            'titulo.required' => 'El título es obligatorio',
            'descripcion.required' => 'La descripción es obligatoria',
            'categoria_id.required' => 'La categoría es obligatoria',
            'prioridad_id.required' => 'La prioridad es obligatoria',
            'sede_id.required' => 'La sede es obligatoria',
            'asignado_id.required' => 'El usuario asignado es obligatorio',
        ];
    }
}
