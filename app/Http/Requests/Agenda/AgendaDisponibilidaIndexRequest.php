<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los filtros del listado de disponibilidades de agenda.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class AgendaDisponibilidaIndexRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene permiso para realizar esta petición.
     *
     * @returns bool siempre retorna true.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación para filtrar disponibilidades.
     *
     * @returns array reglas de validación: sede_id, servicio_id, bus_fech_ini, bus_fecha_fin.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "sede_id" => "sometimes|nullable|integer|exists:config_sedes,id",
            "servicio_id" => "sometimes|nullable|integer|exists:config_servicios,id",
            "bus_fech_ini" => "sometimes|nullable|date",
            "bus_fecha_fin" => "sometimes|nullable|date|after_or_equal:bus_fech_ini",
        ];
    }

    /**
     * Define los mensajes de error personalizados para las reglas de validación.
     *
     * @returns array mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages(): array
    {
        return [
            "sede_id.exists" => "La sede seleccionada no existe.",
            "servicio_id.exists" => "El servicio seleccionado no existe.",
            "bus_fech_ini.date" => "La fecha inicial debe ser una fecha válida.",
            "bus_fecha_fin.date" => "La fecha final debe ser una fecha válida.",
            "bus_fecha_fin.after_or_equal" => "La fecha final debe ser igual o posterior a la fecha inicial.",
        ];
    }
}
