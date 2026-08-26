<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoAnularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observacion' => 'required|string|max:255',
        ];
    }
}
