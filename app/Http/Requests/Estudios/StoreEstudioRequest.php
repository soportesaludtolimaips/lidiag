<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registro' => 'required|json',
            'archivo1' => 'nullable|file|max:10240',
            'archivo2' => 'nullable|file|max:10240',
            'archivo3' => 'nullable|file|max:10240',
        ];
    }

    public function decoded(): array
    {
        return json_decode($this->input('registro'), true) ?? [];
    }

    public function messages(): array
    {
        return [
            'registro.required' => 'Los datos del registro son obligatorios.',
            'registro.json' => 'El registro debe ser un JSON válido.',
        ];
    }
}
