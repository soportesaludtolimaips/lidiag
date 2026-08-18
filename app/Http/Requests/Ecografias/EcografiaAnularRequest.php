<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaAnularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_anula' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_anula.required' => 'El motivo de anulación es obligatorio.',
            'motivo_anula.max' => 'El motivo de anulación no debe exceder 255 caracteres.',
        ];
    }
}
