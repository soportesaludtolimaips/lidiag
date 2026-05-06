<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class UserAgregarRequest extends FormRequest
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
        $userId = $this->route('user'); // Obtener el ID del usuario para las reglas de actualización

        return [
            'registro' => 'required|json',
            'registro.num_docu' => 'required|string|max:20',
            'registro.reg_med' => 'nullable|string|max:20',
            'registro.name' => 'required|string|max:50',
            'registro.email' => 'required|email|max:255|unique:users,email,'.$userId,
            'registro.tel' => 'nullable|string|max:50',
            'registro.facebook' => 'nullable|string|max:100',
            'registro.twitter' => 'nullable|string|max:100',
            'registro.youtube' => 'nullable|string|max:100',
            'registro.interface_id' => 'nullable|string|max:255',
            'registro.permitir_transcribir' => 'nullable|boolean',
            'registro.password' => 'sometimes|string|min:8',
            'registro.estado' => 'nullable|boolean',
            'firma' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'avatar' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'registro.required' => 'El campo registro es obligatorio.',
            'registro.json' => 'El campo registro debe ser un JSON válido.',
            'registro.num_docu.required' => 'El número de documento es obligatorio.',
            'registro.num_docu.max' => 'El número de documento no debe exceder los 20 caracteres.',
            'registro.num_docu.unique' => 'El número de documento ya está en uso.',
            'registro.reg_med.max' => 'El número de registro médico no debe exceder los 20 caracteres.',
            'registro.name.required' => 'El nombre es obligatorio.',
            'registro.name.max' => 'El nombre no debe exceder los 50 caracteres.',
            'registro.email.required' => 'El email es obligatorio.',
            'registro.email.email' => 'El email debe ser una dirección de correo válida.',
            'registro.email.unique' => 'El email ya está en uso.',
            'registro.tel.max' => 'El teléfono no debe exceder los 50 caracteres.',
            'registro.facebook.max' => 'El enlace de Facebook no debe exceder los 100 caracteres.',
            'registro.twitter.max' => 'El enlace de Twitter no debe exceder los 100 caracteres.',
            'registro.youtube.max' => 'El enlace de YouTube no debe exceder los 100 caracteres.',
            'registro.password.sometimes' => 'La contraseña es opcional.',
            'registro.password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'firma.file' => 'El campo firma debe ser un archivo.',
            'firma.mimes' => 'El campo firma debe ser un archivo de tipo: jpeg, png, jpg, gif, svg.',
            'firma.max' => 'El archivo de firma no debe exceder los 2048 kilobytes.',
            'avatar.file' => 'El campo avatar debe ser un archivo.',
            'avatar.mimes' => 'El campo avatar debe ser un archivo de tipo: jpeg, png, jpg, gif, svg.',
            'avatar.max' => 'El archivo de avatar no debe exceder los 2048 kilobytes.',
        ];
    }
}
