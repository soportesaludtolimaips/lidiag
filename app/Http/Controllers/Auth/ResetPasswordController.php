<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\PasswordRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/dashboard';

    /**
     * Restringe el acceso a invitados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Reglas de validación para el restablecimiento de contraseñas.
     *
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-19
     */
    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRules::nueva()],
        ];
    }

    /**
     * Mensajes de error personalizados para la validación de contraseñas.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-19
     */
    protected function validationErrorMessages()
    {
        return [
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ] + PasswordRules::mensajes();
    }

    /**
     * Asigna la contraseña cifrada al usuario y actualiza password_changed_at y estado.
     *
     * @param  User  $user
     * @param  string  $password
     * @return void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-19
     */
    protected function setUserPassword($user, $password)
    {
        $user->password = Hash::make($password);
        $user->password_changed_at = now();
        $user->estado = true;
    }
}
