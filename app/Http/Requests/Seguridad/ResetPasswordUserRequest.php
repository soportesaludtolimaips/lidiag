<?php

namespace App\Http\Requests\Seguridad;

use App\Helpers\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ResetPasswordUserRequest extends FormRequest
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
     * Reglas de validación para restablecer la contraseña de un usuario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function rules(): array
    {
        return [
            "password" => ["required", "string", PasswordRules::nueva()],
            "confirmarPassword" => ["required", "string", "same:password"],
            "forzar_cambio" => ["sometimes", "boolean"],
        ];
    }

    /**
     * Mensajes de error personalizados para la validación de la contraseña temporal.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function messages(): array
    {
        return [
            "password.required" => "La contraseña temporal es obligatoria.",
            "confirmarPassword.required" => "Debe confirmar la contraseña temporal.",
            "confirmarPassword.same" => "La confirmación de la contraseña no coincide.",
        ] + PasswordRules::mensajes();
    }
}
