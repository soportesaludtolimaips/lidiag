<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoCrearTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'documento_id' => 'required|integer|exists:consentimiento_documentos,id',
            'firmante_tipo' => 'required|in:PACIENTE,ACOMPANANTE',
            'acompanante' => 'nullable|array',
            'acompanante.cedula' => 'nullable|string|max:30|required_if:firmante_tipo,ACOMPANANTE',
            'acompanante.nombres' => 'nullable|string|max:200|required_if:firmante_tipo,ACOMPANANTE',
            'acompanante.email' => 'nullable|email|max:150',
            'acompanante.direccion' => 'nullable|string|max:255',
            'acompanante.telefono' => 'nullable|string|max:50',
            'minutos' => 'nullable|integer|min:1|max:1440',
            'usos_maximos' => 'nullable|integer|min:1|max:100',
        ];
    }
}
