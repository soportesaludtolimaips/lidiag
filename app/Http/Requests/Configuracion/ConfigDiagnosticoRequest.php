<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class ConfigDiagnosticoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'cod_diagnos' => 'required|min:2|max:20',
            'nom_diagnos' => 'required|min:2|max:200'
        ];
    }

    public function messages()
    {
        return [
            'cod_diagnos.required' => 'Te hizo falta código del diagnósticos',
            'cod_diagnos.min' => 'El código del diagnósticos debe tener mínimo 2 caracteres',
            'cod_diagnos.max' => 'El código del diagnósticos debe tener máximo 20 caracteres',
            'nom_diagnos.required' => 'Te hizo falta nombre del diagnósticos',
            'nom_diagnos.min' => 'El nombre del diagnósticos debe tener mínimo 2 caracteres',
            'nom_diagnos.max' => 'El nombre del diagnósticos debe tener máximo 20 caracteres',
        ];
    }
}
