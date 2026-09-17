<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreConfigTipoDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('configuracion.tipos-documento.create');
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:20', Rule::unique('config_tipo_documento', 'codigo')],
            'tipo_documento' => ['required', 'string', 'max:255', Rule::unique('config_tipo_documento', 'tipo_documento')],
            'estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'Ya existe un tipo de documento con este código.',
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.unique' => 'Ya existe un tipo de documento con este nombre.',
        ];
    }
}
