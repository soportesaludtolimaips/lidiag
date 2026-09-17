<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateConfigServicioCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'sede_id' => 'nullable|integer|exists:config_sedes,id',
            'estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del servicio es obligatorio.',
            'sede_id.exists' => 'La sede seleccionada no es válida.',
        ];
    }
}
