<?php

namespace App\Http\Requests\Seguridad;

use App\Helpers\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CambiarPasswordRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Reglas de validación para el cambio de contraseña del propio usuario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function rules(): array
    {
        return [
            "current_password" => ["required", "string"],
            "password" => ["required", "string", PasswordRules::nueva()],
            "confirmarPassword" => ["required", "string", "same:password"],
        ];
    }

    /**
     * Mensajes de error personalizados para la validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function messages(): array
    {
        return [
            "current_password.required" => "La contraseña actual es obligatoria.",
            "password.required" => "La nueva contraseña es obligatoria.",
            "confirmarPassword.required" => "Debe confirmar la nueva contraseña.",
            "confirmarPassword.same" => "La confirmación de la nueva contraseña no coincide.",
        ] + PasswordRules::mensajes();
    }
}
