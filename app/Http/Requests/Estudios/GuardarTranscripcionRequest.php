<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class GuardarTranscripcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estudio_producto_id' => 'required|integer|exists:estudios_productos,id',
            'id_estudio' => 'nullable|integer',
            'lectura' => 'required|string|min:3',
            'num_docu' => 'required|string|max:50',
            'nom_pacien' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'tel' => 'nullable|string|max:50',
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'cod_cups' => 'required|string|max:50',
            'nom_produc' => 'required|string|max:255',
            'medico_id' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'lectura.required' => 'Debe ingresar la lectura.',
            'lectura.min' => 'La lectura debe tener al menos 3 caracteres.',
        ];
    }
}
