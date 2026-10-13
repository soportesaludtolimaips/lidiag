<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\PasswordRules;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class PasswordChangeController extends Controller
{
    /**
     * Inyecta el servicio de autenticación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Muestra la vista del formulario de cambio de contraseña.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function showForm()
    {
        return view('auth.passwords.cambiar');
    }

    /**
     * Procesa el cambio de la contraseña del usuario autenticado.
     *
     * @param  Request  $request  Solicitud con la contraseña actual y la nueva contraseña.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'string', PasswordRules::nueva(), 'confirmed'],
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ] + PasswordRules::mensajes());

        $result = $this->authService->changePassword(
            $request->user(),
            $request->current_password,
            $request->password
        );

        if (! $result['success']) {
            return back()->withErrors(['current_password' => $result['message']]);
        }

        $intended = session()->pull('url.intended');
        $intendedPath = $intended !== null ? trim((string) parse_url($intended, PHP_URL_PATH), '/') : null;
        $formPath = trim((string) parse_url(route('password.cambiar'), PHP_URL_PATH), '/');

        if ($intendedPath === null || $intendedPath === '' || $intendedPath === $formPath) {
            $intended = null;
        }

        return redirect()->to($intended ?: route('dashboard'))->with('success', 'Contraseña actualizada correctamente.');
    }
}
