<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreConfigTipoDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('configuracion.tipos-documento.create');
    }

    public function rules(): array
    {
        return [
            'codigo' => 'required|string|max:20',
            'tipo_documento' => 'required|string|max:255',
            'estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
        ];
    }
}
