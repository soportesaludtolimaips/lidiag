<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class ConfigPrioridadRequest extends FormRequest
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
            'nom_priori' => 'required|min:2|max:50',
        ];
    }

    public function messages()
    {
        return [
            'nom_priori.required' => 'Te hizo falta la prioridad',
            'nom_priori.min' => 'La prioridad debe tener mínimo 2 caracteres',
            'nom_priori.max' => 'La prioridad debe tener máximo 50 caracteres',
        ];
    }
}
