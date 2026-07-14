<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoPlantillaIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'config_modulo_id' => 'required|integer|exists:config_modulos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'config_modulo_id.required' => 'El módulo es obligatorio.',
        ];
    }
}
