<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar filtros de consulta de citas pendientes.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class AgendaCitaPendientesRequest extends FormRequest
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
     * Define las reglas de validación para filtrar citas pendientes.
     *
     * @returns array reglas de validación: servicio_id, sede_id, busqueda.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "servicio_id" => "sometimes|nullable|integer|exists:config_servicios,id",
            "sede_id" => "sometimes|nullable|integer|exists:config_sedes,id",
            "busqueda" => "sometimes|nullable|string|max:100",
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
            "servicio_id.exists" => "El servicio seleccionado no existe.",
            "sede_id.exists" => "La sede seleccionada no existe.",
            "busqueda.max" => "El término de búsqueda no puede superar los 100 caracteres.",
        ];
    }
}
