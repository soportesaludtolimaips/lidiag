<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar filtros de búsqueda y paginación en el listado de citas.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class AgendaCitaIndexRequest extends FormRequest
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
     * Define las reglas de validación para el filtrado de citas.
     *
     * @returns array reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "estado" => "sometimes|nullable|string|in:solicitada,asignada,confirmada,cumplida,no_asistio,cancelada,rechazada",
            "fecha_inicio" => "sometimes|nullable|date_format:Y-m-d",
            "fecha_fin" => "sometimes|nullable|date_format:Y-m-d",
            "servicio_id" => "sometimes|nullable|integer|exists:config_servicios,id",
            "sede_id" => "sometimes|nullable|integer|exists:config_sedes,id",
            "busqueda" => "sometimes|nullable|string|max:100",
            "per_page" => "sometimes|nullable|integer|min:1|max:100",
            "page" => "sometimes|nullable|integer|min:1",
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
            "estado.in" => "El estado seleccionado no es válido.",
            "fecha_inicio.date_format" => "La fecha de inicio debe tener formato YYYY-MM-DD.",
            "fecha_fin.date_format" => "La fecha de fin debe tener formato YYYY-MM-DD.",
            "servicio_id.exists" => "El servicio seleccionado no existe.",
            "sede_id.exists" => "La sede seleccionada no existe.",
            "per_page.integer" => "La cantidad de registros por página debe ser un número entero.",
            "per_page.min" => "La cantidad de registros por página debe ser al menos 1.",
            "per_page.max" => "La cantidad máxima de registros por página es 100.",
        ];
    }
}
