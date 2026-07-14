<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskCategoriaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

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

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.max' => 'El nombre no debe exceder 100 caracteres',
        ];
    }
}
