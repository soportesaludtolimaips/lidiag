<?php

namespace App\Http\Requests\Interfaces;

use Illuminate\Foundation\Http\FormRequest;

class EnviarOrdenTeleRadiologiaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'orden' => ['required', 'array'],
        ];
    }

    public function messages()
    {
        return [
            'orden.required' => 'No se recibieron datos de la orden.',
            'orden.array' => 'La orden debe ser un arreglo.',
        ];
    }
}
