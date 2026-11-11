<?php

namespace App\Http\Requests\Interfaces;

use Illuminate\Foundation\Http\FormRequest;

class EnviarEcografiaRequest extends FormRequest
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
     * Reglas de validación para enviar una ecografía a transcripción.
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
            'ecografia_producto_id' => ['required', 'integer'],
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
            'ecografia_producto_id.required' => 'El ID del producto de ecografía es obligatorio.',
            'ecografia_producto_id.integer' => 'El ID del producto de ecografía debe ser un número entero.',
        ];
    }
}
