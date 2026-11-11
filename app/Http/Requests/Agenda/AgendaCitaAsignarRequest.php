<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los datos de asignación de una cita médica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */
class AgendaCitaAsignarRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene permiso para realizar esta petición.
     *
     * @returns bool siempre retorna true.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación para la asignación de una cita.
     *
     * @returns array reglas de validación: producto_id, sede_id, agenda_disponibilidad_detalle_id, fecha_hora_inicio, duracion_minutos, observaciones.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function rules(): array
    {
        return [
            'producto_id' => 'required|exists:config_productos,id',
            'sede_id' => 'required|exists:config_sedes,id',
            'agenda_disponibilidad_detalle_id' => 'nullable|exists:agenda_disponibilidad_detalle,id',
            'fecha_hora_inicio' => 'required|date',
            'duracion_minutos' => 'nullable|integer|min:15',
            'observaciones' => 'nullable|string',
        ];
    }

    /**
     * Define los mensajes de error personalizados para las reglas de validación.
     *
     * @returns array mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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
            'duracion_minutos.min' => 'La duración mínima es de 15 minutos.',
        ];
    }
}
