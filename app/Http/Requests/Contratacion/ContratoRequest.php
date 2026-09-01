<?php

namespace App\Http\Requests\Contratacion;

use Illuminate\Foundation\Http\FormRequest;

class ContratoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registro' => 'required|json',
        ];
    }

    public function decoded(): array
    {
        return json_decode($this->input('registro'), true) ?? [];
    }

    public function messages(): array
    {
        return [
            'registro.required' => 'Los datos del contrato son obligatorios',
            'registro.json' => 'El formato de datos no es válido',
        ];
    }
}
