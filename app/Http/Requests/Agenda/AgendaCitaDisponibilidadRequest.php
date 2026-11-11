<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los datos de consulta de disponibilidad de horarios.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */
class AgendaCitaDisponibilidadRequest extends FormRequest
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
     * Define las reglas de validación para la consulta de disponibilidad.
     *
     * @returns array reglas de validación: sede_id, servicio_id, fecha, producto_id.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function rules(): array
    {
        return [
            'producto_id' => 'nullable|exists:config_productos,id',
            'sede_id' => 'required|exists:config_sedes,id',
            'servicio_id' => 'required|exists:config_servicios,id',
            'fecha' => 'required|date',
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
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'servicio_id.required' => 'El servicio de cita es obligatorio.',
            'servicio_id.exists' => 'El servicio de cita seleccionado no existe.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser válida.',
        ];
    }
}
