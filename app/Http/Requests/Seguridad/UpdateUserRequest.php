<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:users,id',
            'registro' => 'required|json',
            'firma' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'avatar' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'registro.required' => 'Los datos del usuario son obligatorios.',
            'registro.json' => 'Los datos del usuario deben tener un formato válido.',
            'firma.mimes' => 'La firma debe ser una imagen de tipo: jpeg, png, jpg, gif, svg.',
            'firma.max' => 'La firma no debe exceder los 10 MB.',
            'avatar.mimes' => 'El avatar debe ser una imagen de tipo: jpeg, png, jpg, gif, svg.',
            'avatar.max' => 'El avatar no debe exceder los 10 MB.',
        ];
    }

    public function decoded(): array
    {
        return json_decode($this->input('registro'), true) ?? [];
    }

    public function targetId(): int
    {
        return (int) $this->input('id');
    }
}
