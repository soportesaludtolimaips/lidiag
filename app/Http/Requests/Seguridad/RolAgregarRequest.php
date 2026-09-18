<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class RolAgregarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|unique:roles|min:2|max:100',
            'permisosRol' => 'nullable|array',
            'permisosRol.*' => 'nullable|array',
            'permisosRol.*.id' => 'required_with:permisosRol.*|integer',
            'permisosRol.*.name' => 'required_with:permisosRol.*|string',
            'permisosRol.*.checked' => 'required_with:permisosRol.*|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El nombre del rol debe tener como mínimo 2 caracteres.',
            'name.max' => 'El nombre del rol debe tener como máximo 100 caracteres.',
            'name.unique' => 'El nombre del rol ya se encuentra registrado.',
        ];
    }
}
