<?php

namespace App\Http\Requests\Seguridad;

use App\Helpers\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     * Solo puede editar su propio perfil.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        $targetId = (int) $this->route("id");

        return Auth::check() && ((int) Auth::id() === $targetId);
    }

    /**
     * Reglas de validación para la actualización del perfil del usuario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        $userId = (int) $this->route("id");

        return [
            "num_docu" => ["required", "string", "max:20"],
            "name" => ["required", "string", "max:50"],
            "email" => ["required", "email", "max:255", "unique:users,email,".$userId],
            "password" => ["nullable", "string", PasswordRules::nueva()],
            "current_password" => ["required_with:password", "nullable", "string", "max:255"],
            "imagen_perfil" => ["nullable", "image", "max:2048"],
            "firma" => ["nullable", "image", "max:2048"],
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
        return PasswordRules::mensajes() + [
            "num_docu.required" => "El número de documento es obligatorio.",
            "num_docu.max" => "El número de documento no debe exceder 20 caracteres.",
            "name.required" => "El nombre es obligatorio.",
            "name.max" => "El nombre no debe exceder 50 caracteres.",
            "email.required" => "El correo electrónico es obligatorio.",
            "email.email" => "El correo electrónico no es válido.",
            "email.unique" => "El email ya está registrado por otro usuario.",
            "current_password.required_with" => "La contraseña actual es obligatoria para cambiar la contraseña.",
            "imagen_perfil.image" => "La imagen de perfil debe ser un archivo de imagen válido.",
            "imagen_perfil.max" => "La imagen de perfil no debe superar 2 MB.",
            "firma.image" => "La firma debe ser un archivo de imagen válido.",
            "firma.max" => "La firma no debe superar 2 MB.",
        ];
    }
}
