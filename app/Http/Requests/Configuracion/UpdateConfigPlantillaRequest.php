<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateConfigPlantillaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('config.plantillas.edit');
    }

    public function rules(): array
    {
        return [
            'plantilla_tipo_id' => 'required|integer|exists:config_plantillas_tipos,id',
            'medico_id' => 'nullable|integer|exists:users,id',
            'nom_plantilla' => 'required|string|max:255',
            'plantilla' => 'required|string',
            'opinion' => 'nullable|string',
            'estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'plantilla_tipo_id.required' => 'El tipo de plantilla es obligatorio.',
            'nom_plantilla.required' => 'El nombre de la plantilla es obligatorio.',
            'plantilla.required' => 'El contenido de la plantilla es obligatorio.',
        ];
    }
}
