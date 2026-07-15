<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstudioDiagnosticoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estudio_id' => 'required|integer|exists:estudios,id',
            'cod_diagnos' => 'required|string|max:50',
            'nom_diagnos' => 'required|string|max:255',
        ];
    }
}
