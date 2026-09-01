<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los datos de creación de detalles de disponibilidad de agenda.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */
class AgendaDisponibilidaDetalleRequest extends FormRequest
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
     * Define las reglas de validación para la creación de detalles de disponibilidad.
     *
     * @returns array reglas de validación: agenda_disponibilidad_id, total_citas, dura_cita, fecha_hora_inicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function rules(): array
    {
        return [
            'agenda_disponibilidad_id' => 'required|integer',
            'total_citas' => 'required|integer|min:1',
            'dura_cita' => 'required|integer|min:1',
            'fecha_hora_inicio' => 'required|date',
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
            'agenda_disponibilidad_id.required' => 'La disponibilidad es obligatoria.',
            'total_citas.required' => 'El total de citas es obligatorio.',
            'total_citas.min' => 'El total de citas debe ser al menos 1.',
            'dura_cita.required' => 'La duración por cita es obligatoria.',
            'dura_cita.min' => 'La duración por cita debe ser al menos 1 minuto.',
            'fecha_hora_inicio.required' => 'La fecha/hora de inicio es obligatoria.',
        ];
    }
}
