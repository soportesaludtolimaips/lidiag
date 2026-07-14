<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateConfigRecomendacionProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('configuracion.recomendaciones.edit');
    }

    public function rules(): array
    {
        return [
            'recomendacion' => 'required|string',
            'estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'recomendacion.required' => 'La recomendación es obligatoria.',
        ];
    }
}
