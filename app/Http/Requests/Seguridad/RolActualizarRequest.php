<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class RolActualizarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|min:2|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El nombre del rol debe tener como mínimo 2 caracteres.',
            'name.max' => 'El nombre del rol debe tener como máximo 100 caracteres.',
        ];
    }
}
