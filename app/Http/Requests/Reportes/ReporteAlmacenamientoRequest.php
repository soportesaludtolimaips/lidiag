<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar filtros de consulta y descarga del monitor de almacenamiento y servidores.
 *
 * Flujo del proceso:
 * 1. Autoriza la petición a usuarios autenticados con permisos de seguridad, infraestructura o reportes.
 * 2. Valida el filtro opcional de sede.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteAlmacenamientoRequest extends FormRequest
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
     * Reglas de validación para monitor de almacenamiento.
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
