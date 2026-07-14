<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

class AsignarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id' => 'required|exists:config_productos,id',
            'sede_id' => 'required|exists:config_sedes,id',
            'agenda_disponibilidad_detalle_id' => 'nullable|exists:agenda_disponibilidad_detalle,id',
            'fecha_hora_inicio' => 'required|date',
            'duracion_minutos' => 'nullable|integer|min:15',
        ];
    }

    public function messages(): array
    {
        return [
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'fecha_hora_inicio.required' => 'La fecha y hora de inicio es obligatoria.',
            'fecha_hora_inicio.date' => 'La fecha y hora de inicio debe ser una fecha válida.',
            'duracion_minutos.integer' => 'La duración debe ser un número entero.',
            'duracion_minutos.min' => 'La duración mínima es 15 minutos.',
        ];
    }
}
