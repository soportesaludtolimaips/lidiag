<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoAdjuntoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
            'categoria' => 'required|in:documento_identidad,orden_medica,historia_clinica,laboratorio,imagenes,otro',
        ];
    }
}
