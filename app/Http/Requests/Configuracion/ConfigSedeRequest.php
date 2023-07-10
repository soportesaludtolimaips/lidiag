<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class ConfigsedeRequest extends FormRequest
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
            'nom_sede' => 'required|min:2|max:70',
            'ip_dcm' => 'required|min:7|max:15',
            'bd_dcm' => 'required|min:2|max:30',
            'usuario_dcm' => 'required|min:2|max:20',
            //'password_dcm' => 'required|min:2|max:15',
            'usuario_oviyam' => 'required|min:2|max:20',
            'password_oviyam' => 'required|min:2|max:10',
            'url_oviyam' => 'required|min:10|max:100',
        ];
    }

    public function messages()
    {
        return [
            'nom_sede.required' => 'Te hizo falta el nombre de la sede',
            'nom_sede.min' => 'El nombre de la sede debe tener mínimo 2 caracteres',
            'nom_sede.max' => 'El nombre de la sede debe tener máximo 70 caracteres',
            'ip_dcm.required' => 'Te hizo falta la IP de la sede',
            'ip_dcm.min' => 'La IP de la sede debe tener mínimo 15 caracteres',
            'ip_dcm.max' => 'La IP de la sede debe tener máximo 15 caracteres',
            'usuario_dcm.required' => 'Te hizo falta el usuario de la Bd DCCHEEM de la sede',
            'usuario_dcm.min' => 'El usuario de la Bd DCCHEEM de la sede debe tener mínimo 2 caracteres',
            'usuario_dcm.max' => 'El usuario de la Bd DCCHEEM de la sede debe tener máximo 20 caracteres',
            //'password_dcm.required' => 'Te hizo falta el password de la Bd DCCHEEM de la sede',
            //'password_dcm.min' => 'El password de la Bd DCCHEEM de la sede debe tener mínimo 2 caracteres',
            //'password_dcm.max' => 'El password de la Bd DCCHEEM de la sede debe tener máximo 20 caracteres',
            'usuario_oviyam.required' => 'Te hizo falta el usuaro Oviyam de la sede',
            'usuario_oviyam.min' => 'El usuaro Oviyam de la sede debe tener mínimo 2 caracteres',
            'usuario_oviyam.max' => 'El usuaro Oviyam de la sede debe tener máximo 20 caracteres',
            'password_oviyam.required' => 'Te hizo falta el password Oviyam de la sede',
            'password_oviyam.min' => 'El password Oviyam de la sede debe tener mínimo 2 caracteres',
            'password_oviyam.max' => 'El password Oviyam de la sede debe tener máximo 20 caracteres',
            'url_oviyam.required' => 'Te hizo falta la URL de Oviyam de la sede',
            'url_oviyam.min' => 'La URL de Oviyam de la sede debe tener mínimo 10 caracteres',
            'url_oviyam.max' => 'La URL de Oviyam de la sede debe tener máximo 100 caracteres',
        ];
    }
}
