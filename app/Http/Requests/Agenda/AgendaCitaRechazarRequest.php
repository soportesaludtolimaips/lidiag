<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

class AgendaCitaRechazarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rechazo_motivo_id' => 'required|exists:config_cita_rechazo_motivos,id',
            'rechazo_observacion' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'rechazo_motivo_id.required' => 'El motivo de rechazo es obligatorio.',
            'rechazo_motivo_id.exists' => 'El motivo de rechazo seleccionado no existe.',
            'rechazo_observacion.max' => 'La observación no debe exceder 500 caracteres.',
        ];
    }
}
