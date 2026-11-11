<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class ReporteProduccionConsolidadaRequest extends FormRequest
{
    /**
     * Determina si el usuario puede autorizar la solicitud.
     *
     * @returns bool Siempre retorna true.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-09
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación para el reporte de producción consolidada.
     *
     * @returns array<string, string> Reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-09
     */
    public function rules(): array
    {
        return [
            "fechaIni" => "required|date",
            "fechaFin" => "required|date|after_or_equal:fechaIni",
            "sedeId" => "nullable",
            "modalidad" => "nullable|string|max:50",
            "destino" => "nullable|string|max:50",
        ];
    }

    /**
     * Define los mensajes de error personalizados para las reglas de validación.
     *
     * @returns array<string, string> Mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-09
     */
    public function messages(): array
    {
        return [
            "fechaIni.required" => "La fecha de inicio es obligatoria.",
            "fechaIni.date" => "La fecha de inicio debe ser una fecha válida.",
            "fechaFin.required" => "La fecha de fin es obligatoria.",
            "fechaFin.date" => "La fecha de fin debe ser una fecha válida.",
            "fechaFin.after_or_equal" => "La fecha de fin debe ser igual o posterior a la fecha de inicio.",
        ];
    }
}
