<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los datos de creación y actualización de disponibilidad de agenda.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */
class AgendaDisponibilidaRequest extends FormRequest
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
     * Define las reglas de validación para la disponibilidad de agenda.
     *
     * @returns array reglas de validación: sede_id, servicio_id, fecha_hora_ini, total_citas, dura_cita, estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function rules(): array
    {
        $rules = [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'servicio_id' => 'nullable|integer|exists:config_servicios,id',
            'fecha_hora_ini' => 'required|date',
            'total_citas' => 'required|integer|min:1',
            'dura_cita' => 'required|integer|min:1',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['estado'] = 'nullable|in:Activo,Inactivo,Completado,Pendiente';
        }

        return $rules;
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
            'sede_id.required' => 'La sede es obligatoria.',
            'fecha_hora_ini.required' => 'La fecha/hora inicio es obligatoria.',
            'total_citas.required' => 'El total de citas es obligatorio.',
            'dura_cita.required' => 'La duración por cita es obligatoria.',
        ];
    }
}
