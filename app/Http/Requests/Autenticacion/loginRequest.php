<?php

namespace App\Http\Requests\Autenticacion;

use Illuminate\Foundation\Http\FormRequest;

class loginRequest extends FormRequest
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
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Te hizo falta el E-Mail',
            'email.email' => 'El E-Mail no tiene un formato válido.',
            'email.max' => 'El E-Mail no debe exceder 255 caracteres.',
            'password.required' => 'Te hizo falta el password',
            'password.max' => 'El password no debe exceder 255 caracteres.',
        ];
    }
}
