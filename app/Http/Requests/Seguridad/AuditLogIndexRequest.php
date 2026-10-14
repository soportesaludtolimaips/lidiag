<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AuditLogIndexRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Reglas de validación para los filtros de auditoría.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "evento" => ["nullable", "string", "max:50"],
            "user_id" => ["nullable", "integer"],
            "search" => ["nullable", "string", "max:100"],
            "desde" => ["nullable", "date"],
            "hasta" => ["nullable", "date"],
            "per_page" => ["nullable", "integer", "min:1", "max:200"],
        ];
    }
}
