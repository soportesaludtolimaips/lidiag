<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoPlantillaRequest extends FormRequest
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
     * Reglas de validación de plantillas de consentimientos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        $rules = [
            'config_modulo_id' => 'required|integer|exists:config_modulos,id',
            'producto_id' => 'required|integer|exists:config_productos,id',
            'nombre' => 'required|string|max:255',
            'html' => 'required|string',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            unset($rules['config_modulo_id'], $rules['producto_id']);
        }

        return $rules;
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
            'config_modulo_id.required' => 'El módulo es obligatorio.',
            'producto_id.required' => 'El producto es obligatorio.',
            'nombre.required' => 'El nombre es obligatorio.',
            'html.required' => 'El contenido HTML es obligatorio.',
        ];
    }
}
