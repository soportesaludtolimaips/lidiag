<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoLiberarBloqueoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medico_id' => 'required|integer|exists:users,id',
            'horas' => 'nullable|integer|min:1|max:168',
            'motivo' => 'nullable|string|max:255',
        ];
    }
}
