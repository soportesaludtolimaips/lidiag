<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar filtros de consulta y descarga de estadísticas de citas y canal WhatsApp.
 *
 * Flujo del proceso:
 * 1. Autoriza la petición a usuarios autenticados con permisos de citas o reportes.
 * 2. Valida período, sede opcional y rango de fechas.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteCitasWhatsAppRequest extends FormRequest
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
     * Reglas de validación para estadísticas de citas WhatsApp.
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
            "sedeId" => "sometimes|nullable|integer",
            "fechaIni" => "sometimes|nullable|date",
            "fechaFin" => "sometimes|nullable|date",
        ];
    }
}
