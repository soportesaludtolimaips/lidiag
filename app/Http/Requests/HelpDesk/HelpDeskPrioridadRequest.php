<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskPrioridadRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'nombre' => 'required|string|max:50',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['orden'] = 'required|integer|min:0';
            $rules['activo'] = 'required|boolean';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.max' => 'El nombre no debe exceder 50 caracteres',
        ];
    }
}
