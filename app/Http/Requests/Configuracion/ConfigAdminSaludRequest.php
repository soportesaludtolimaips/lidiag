<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class ConfigAdminSaludRequest extends FormRequest
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
            'cod_admin_salud' => 'required|min:2|max:20',
            'nom_admin_salud' => 'required|min:5|max:150',
        ];
    }

    public function messages()
    {
        return [
            'cod_admin_salud.required' => 'Te hizo falta el código de la administradora de salud',
            'cod_admin_salud.min' => 'El código de la administradora de salud debe tener mínimo 2 caracteres',
            'cod_admin_salud.max' => 'El código de la administradora de salud debe tener máximo 150 caracteres',
            'nom_admin_salud.required' => 'Te hizo falta el nombre de la administradora de salud',
            'nom_admin_salud.min' => 'El nombre de la administradora de salud debe tener mínimo 2 caracteres',
            'nom_admin_salud.max' => 'El nombre de la administradora de salud debe tener máximo 150 caracteres',
        ];
    }
}
