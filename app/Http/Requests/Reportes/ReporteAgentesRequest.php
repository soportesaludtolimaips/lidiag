<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar la descarga del reporte de Salud y Telemetría de Agentes Periféricos.
 *
 * Flujo del proceso:
 * 1. Autoriza la petición a usuarios autenticados con permisos de reportes o auditoría.
 * 2. Valida el identificador opcional de sede hospitalaria.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
class ReporteAgentesRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene autorización para esta solicitud.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el reporte de telemetría de agentes hospitalarios.
     *
     * @return array<string, ValidationRule|array|string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function rules(): array
    {
        return [
            "sedeId" => "nullable|integer",
        ];
    }
}
