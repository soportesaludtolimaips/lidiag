<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreConfigPlantillaTipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('config.plantillas.tipos.create');
    }

    public function rules(): array
    {
        return [
            'nom_tipo' => 'required|string|max:255',
            'producto_id' => 'nullable|integer|exists:config_productos,id',
            'estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nom_tipo.required' => 'El nombre del tipo de plantilla es obligatorio.',
        ];
    }
}
