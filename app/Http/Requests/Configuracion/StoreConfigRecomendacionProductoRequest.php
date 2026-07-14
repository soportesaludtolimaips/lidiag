<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreConfigRecomendacionProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('configuracion.recomendaciones.create');
    }

    public function rules(): array
    {
        return [
            'producto_id' => 'required|integer|exists:config_productos,id',
            'recomendacion' => 'required|string',
            'estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'producto_id.required' => 'El producto es obligatorio.',
            'recomendacion.required' => 'La recomendación es obligatoria.',
        ];
    }
}
