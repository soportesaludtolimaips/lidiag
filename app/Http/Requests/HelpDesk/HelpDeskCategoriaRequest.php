<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskCategoriaRequest extends FormRequest
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
     * Reglas de validación de categorías de HelpDesk.
     *
     * @return array
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules()
    {
        $rules = [
            'nombre' => 'required|string|max:100',
        ];

        if ($this->isMethod('POST')) {
            $rules['activo'] = 'nullable|boolean';
        } else {
            $rules['activo'] = 'required|boolean';
        }

        return $rules;
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
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.max' => 'El nombre no debe exceder 100 caracteres',
        ];
    }
}
