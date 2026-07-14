<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaConsentimientoFirmarPacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firma_png' => 'required|string',
        ];
    }
}
