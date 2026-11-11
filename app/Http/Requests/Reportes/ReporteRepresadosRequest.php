<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar filtros de consulta y descarga del semáforo de estudios represados y urgencias.
 *
 * Flujo del proceso:
 * 1. Autoriza la petición a usuarios autenticados con permisos asistenciales o de reportes.
 * 2. Valida el filtro opcional por sede hospitalaria.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteRepresadosRequest extends FormRequest
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
     * Reglas de validación para el semáforo de represamiento.
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
            "sedeId" => "sometimes|nullable|integer",
        ];
    }
}
