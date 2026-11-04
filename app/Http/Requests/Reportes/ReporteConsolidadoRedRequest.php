<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar filtros de consulta y descarga del reporte consolidado de la red.
 *
 * Flujo del proceso:
 * 1. Autoriza la petición a usuarios autenticados con permisos de reportes.
 * 2. Valida período predefinido o rango de fechas manual (fechaIni, fechaFin).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteConsolidadoRedRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene autorización para esta solicitud.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el consolidado de red hospitalaria.
     *
     * @return array<string, ValidationRule|array|string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function rules(): array
    {
        return [
            "periodo" => "sometimes|nullable|string",
            "fechaIni" => "sometimes|nullable|date",
            "fechaFin" => "sometimes|nullable|date",
        ];
    }
}
