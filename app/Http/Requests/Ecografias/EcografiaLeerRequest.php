<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaLeerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accion' => 'required|string|in:IMPRIMIR_LECTURA,TERMINAR_LECTURA,GUARDAR_BORRADOR',
            'registro' => 'required|array',
            'registro.lectura' => 'nullable|string',
            'registro.opinion' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'accion.required' => 'La acción es obligatoria.',
            'accion.in' => 'La acción seleccionada no es válida.',
            'registro.required' => 'El registro es obligatorio.',
            'registro.array' => 'El registro debe ser un arreglo.',
        ];
    }
}
