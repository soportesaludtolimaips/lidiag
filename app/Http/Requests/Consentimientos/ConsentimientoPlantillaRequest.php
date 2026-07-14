<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoPlantillaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
