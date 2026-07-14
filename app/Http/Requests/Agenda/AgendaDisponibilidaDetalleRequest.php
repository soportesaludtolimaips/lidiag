<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

class AgendaDisponibilidaDetalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agenda_disponibilidad_id' => 'required|integer',
            'total_citas' => 'required|integer|min:1',
            'dura_cita' => 'required|integer|min:1',
            'fecha_hora_inicio' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'agenda_disponibilidad_id.required' => 'La disponibilidad es obligatoria.',
            'total_citas.required' => 'El total de citas es obligatorio.',
            'total_citas.min' => 'El total de citas debe ser al menos 1.',
            'dura_cita.required' => 'La duración por cita es obligatoria.',
            'dura_cita.min' => 'La duración por cita debe ser al menos 1 minuto.',
            'fecha_hora_inicio.required' => 'La fecha/hora de inicio es obligatoria.',
        ];
    }
}
