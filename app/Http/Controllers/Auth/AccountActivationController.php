<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AuditLogger;
use App\Helpers\PasswordRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AccountActivationController extends Controller
{
    /**
     * Inyecta dependencias y restringe el acceso solo a usuarios no autenticados (guest).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-19
     */
    public function __construct()
    {
        $this->middleware("guest");
    }

    /**
     * Muestra la vista con el formulario de activación de cuenta y definición de contraseña.
     *
     * Flujo del proceso:
     * 1. Extrae el token y el correo del usuario proporcionados en la URL.
     * 2. Si se proporciona correo, verifica preliminarmente la existencia del usuario y la validez del token.
     * 3. Si el token expiró o es inválido, redirige al login con advertencia informativa.
     * 4. Retorna la vista auth.activar con los parámetros requeridos.
     *
     * @param  Request  $request  Petición HTTP con query params opcionales.
     * @param  string  $token  Token de activación.
     * @return View|RedirectResponse Formulario de activación o redirección al login.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-19
     */
    public function showForm(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query("email");

        if ($email) {
            $user = User::where("email", $email)->first();

            if (! $user || ! Password::broker()->tokenExists($user, $token)) {
                return redirect()->route("login")->with("error", "El enlace de activación es inválido o ha expirado. Por favor, solicita al administrador un nuevo enlace.");
            }
        }

        return view("auth.activar", [
            "token" => $token,
            "email" => $email,
        ]);
    }

    /**
     * Procesa la activación de la cuenta estableciendo la contraseña definitiva.
     *
     * Flujo del proceso:
     * 1. Valida el token, correo electrónico y las políticas de contraseña institucional.
     * 2. Verifica criptográficamente que el token coincida y se encuentre vigente en password_resets.
     * 3. Asigna la contraseña cifrada con Hash::make.
     * 4. Activa la cuenta (estado = 1), marca email_verified_at y password_changed_at con la fecha actual.
     * 5. Invalida y elimina el token de activación consumido para evitar reutilización.
     * 6. Revoca sesiones previas y tokens Sanctum residuales.
     * 7. Registra el evento en la bitácora de auditoría ISO 27001.
     * 8. Redirige a la pantalla de inicio de sesión con notificación de éxito.
     *
     * @param  Request  $request  Petición HTTP con token, email y nueva contraseña.
     * @return RedirectResponse Redirección a la vista de login o retorno con errores.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-19
     */
    public function activate(Request $request): RedirectResponse
    {
        $request->validate([
            "token" => "required|string",
            "email" => "required|email|exists:users,email",
            "password" => ["required", "string", "confirmed", PasswordRules::nueva()],
        ], [
            "token.required" => "El token de activación es obligatorio.",
            "email.required" => "El correo electrónico es obligatorio.",
            "email.exists" => "No se encontró ningún usuario con el correo especificado.",
            "password.required" => "La contraseña es obligatoria.",
            "password.confirmed" => "La confirmación de la contraseña no coincide.",
        ] + PasswordRules::mensajes());

        $user = User::where("email", $request->input("email"))->first();

        if (! $user || ! Password::broker()->tokenExists($user, $request->input("token"))) {
            return back()
                ->withInput($request->only("email"))
                ->withErrors(["email" => "El enlace de activación es inválido o ha expirado. Solicita un nuevo enlace al administrador."]);
        }

        DB::transaction(function () use ($user, $request) {
            $user->forceFill([
                "password" => Hash::make($request->input("password")),
                "estado" => true,
                "email_verified_at" => now(),
                "password_changed_at" => now(),
            ])->save();

            Password::broker()->deleteToken($user);

            DB::table("sessions")->where("user_id", $user->id)->delete();
            $user->tokens()->delete();

            AuditLogger::actualizar(
                $user,
                ["estado" => 1, "email_verified_at" => now()],
                "Cuenta de usuario activada y contraseña establecida por correo: ".$user->name
            );
        });

        return redirect()->route("login")->with("success", "¡Tu cuenta ha sido activada con éxito! Ya puedes iniciar sesión con tu nueva contraseña.");
    }
}
