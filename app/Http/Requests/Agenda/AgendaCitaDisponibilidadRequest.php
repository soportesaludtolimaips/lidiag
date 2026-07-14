<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

class AgendaCitaDisponibilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id' => 'nullable|exists:config_productos,id',
            'sede_id' => 'required|exists:config_sedes,id',
            'servicio_cita_id' => 'required|exists:config_servicio_citas,id',
            'fecha' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'servicio_cita_id.required' => 'El servicio de cita es obligatorio.',
            'servicio_cita_id.exists' => 'El servicio de cita seleccionado no existe.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser válida.',
        ];
    }
}
