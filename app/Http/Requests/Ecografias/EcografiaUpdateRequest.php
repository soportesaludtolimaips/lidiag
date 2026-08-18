<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medico_id' => 'nullable|integer',
            'sede_id' => 'nullable|integer',
            'prioridad_id' => 'nullable|integer',
            'observaciones' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'medico_id.integer' => 'El médico debe ser un número entero.',
            'sede_id.integer' => 'La sede debe ser un número entero.',
            'prioridad_id.integer' => 'La prioridad debe ser un número entero.',
            'observaciones.string' => 'Las observaciones deben ser texto.',
        ];
    }
}
