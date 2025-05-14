<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ecografia_id' => 'required|exists:ecografias,id',
            'medico_id' => 'required|exists:users,id',
            'producto_id' => 'required|exists:config_productos,id',
            'lectura' => 'nullable|string|max:255',
            'opinion' => 'nullable|string|max:255',
            'fechor_lectura' => 'nullable|date',
            'estado' => 'required|boolean',
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'ecografia_id.required' => 'El campo ecografía es obligatorio.',
            'ecografia_id.exists' => 'La ecografía seleccionada no es válida.',
            'medico_id.required' => 'El campo médico es obligatorio.',
            'medico_id.exists' => 'El médico seleccionado no es válido.',
            'producto_id.required' => 'El campo producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no es válido.',
            'lectura.max' => 'El campo lectura no debe exceder los 255 caracteres.',
            'opinion.max' => 'El campo opinión no debe exceder los 255 caracteres.',
            'fechor_lectura.date' => 'La fecha y hora de lectura debe ser una fecha válida.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.boolean' => 'El campo estado debe ser verdadero o falso.',
        ];
    }
}
