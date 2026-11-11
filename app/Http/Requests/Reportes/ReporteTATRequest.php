<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar la descarga del reporte de Calidad y Oportunidad (Turnaround Time - TAT).
 *
 * Flujo del proceso:
 * 1. Autoriza la petición a usuarios autenticados con permisos de reportes.
 * 2. Normaliza alias de parámetros en caso de requerirse.
 * 3. Valida fechas de rango obligatorio, sede opcional y modalidad.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
class ReporteTATRequest extends FormRequest
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
     * Reglas de validación para el reporte de tiempos de respuesta y oportunidad.
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
            "modalidad" => "nullable|string|max:20",
            "fechaIni" => "required|date",
            "fechaFin" => "required|date|after_or_equal:fechaIni",
        ];
    }

    /**
     * Mensajes descriptivos en caso de fallo de validación.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function messages(): array
    {
        return [
            "fechaIni.required" => "La fecha inicial es obligatoria.",
            "fechaIni.date" => "La fecha inicial debe tener un formato de fecha válido.",
            "fechaFin.required" => "La fecha final es obligatoria.",
            "fechaFin.date" => "La fecha final debe tener un formato de fecha válido.",
            "fechaFin.after_or_equal" => "La fecha final no puede ser menor a la fecha inicial.",
        ];
    }
}
