<?php

namespace App\Http\Requests\Interfaces;

use Illuminate\Foundation\Http\FormRequest;

class EnviarEcografiaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'ecografia_producto_id' => ['required', 'integer'],
        ];
    }

    public function messages()
    {
        return [
            'ecografia_producto_id.required' => 'El ID del producto de ecografía es obligatorio.',
            'ecografia_producto_id.integer' => 'El ID del producto de ecografía debe ser un número entero.',
        ];
    }
}
