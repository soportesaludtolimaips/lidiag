<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstudioDiagnosticoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cod_diagnos' => 'nullable|string|max:50',
            'nom_diagnos' => 'nullable|string|max:255',
        ];
    }
}
