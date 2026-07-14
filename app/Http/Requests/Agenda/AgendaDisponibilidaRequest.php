<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

class AgendaDisponibilidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'servicio_cita_id' => 'nullable|integer|exists:config_servicio_citas,id',
            'fecha_hora_ini' => 'required|date',
            'total_citas' => 'required|integer|min:1',
            'dura_cita' => 'required|integer|min:1',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['estado'] = 'nullable|in:Activo,Inactivo,Completado,Pendiente';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'sede_id.required' => 'La sede es obligatoria.',
            'fecha_hora_ini.required' => 'La fecha/hora inicio es obligatoria.',
            'total_citas.required' => 'El total de citas es obligatorio.',
            'dura_cita.required' => 'La duración por cita es obligatoria.',
        ];
    }
}
