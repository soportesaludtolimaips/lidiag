<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateConsentimientoInformadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('configuracion.consentimientos.edit');
    }

    public function rules(): array
    {
        return [
            'producto_id' => 'required|integer|exists:config_productos,id',
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'nombre' => 'required|string|max:255',
            'archivo' => 'nullable|file|mimes:pdf|max:20480',
            'estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'producto_id.required' => 'El producto es obligatorio.',
            'sede_id.required' => 'La sede es obligatoria.',
            'nombre.required' => 'El nombre del consentimiento es obligatorio.',
            'archivo.mimes' => 'El archivo debe ser un PDF.',
            'archivo.max' => 'El archivo no debe exceder los 20 MB.',
        ];
    }
}
