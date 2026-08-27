<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoPlantillaImagenStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imagen' => 'required|file|image|mimes:jpg,jpeg,png|max:5120',
            'nombre' => 'nullable|string|max:255',
            'orden' => 'nullable|integer|min:0',
            'es_para_marcar' => 'nullable|boolean',
        ];
    }
}
