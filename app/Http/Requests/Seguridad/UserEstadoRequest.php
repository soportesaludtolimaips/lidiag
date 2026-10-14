<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UserEstadoRequest extends FormRequest
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
     * Reglas de validación para cambiar el estado de un usuario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "id" => ["required", "integer", "exists:users,id"],
            "estado" => ["required", "boolean"],
        ];
    }

    /**
     * Mensajes de error personalizados para la validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages(): array
    {
        return [
            "id.required" => "El identificador del usuario es obligatorio.",
            "id.exists" => "El usuario especificado no existe.",
            "estado.required" => "El estado es obligatorio.",
            "estado.boolean" => "El estado debe ser verdadero o falso.",
        ];
    }
}
