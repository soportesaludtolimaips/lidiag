<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaConsentimientoFirmarMedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consentimiento_id' => 'required|integer|exists:ecografias_consentimientos,id',
        ];
    }
}
