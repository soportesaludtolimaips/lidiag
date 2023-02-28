<?php

namespace App\Http\Requests\Segudiada;

use Illuminate\Foundation\Http\FormRequest;

class RolAgregarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|unique:roles|min:2|max:100',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Te hizo falta el nombre del rol',
            'name.min' => 'El nombre del rol debe tener como mínimo 2 caractrere',
            'name.min' => 'El nombre del rol debe tener como maxímo 100 caractrere',
            'name.unique' => 'El nombre del rol ya se encuentra registrado',
        ];
    }
}
