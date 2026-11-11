<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\loginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
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
     * Autentica al usuario con email y contraseña, regenerando la sesión.
     *
     * @param  loginRequest  $request  Solicitud validada con las credenciales del usuario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function login(loginRequest $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validated();
        $result = $this->authService->login(
            $credentials['email'],
            $credentials['password'],
            $request->expectsJson(),
            $request->ip(),
        );

        if ($result['success'] && ! ($result['data']['type'] ?? null)) {
            // Mitigar fijación de sesión: nuevo ID tras autenticar
            $request->session()->regenerate();

            if ($request->expectsJson()) {
                return response()->json($result, $result["status"]);
            }

            return redirect()->to($result['data']['redirect']);
        }

        if ($request->expectsJson()) {
            return response()->json($result, $result["status"]);
        }

        return redirect()
            ->route('login')
            ->withErrors($result['errors'] ?? ['email' => $result['message']])
            ->withInput($request->only('email'));
    }

    /**
     * Cierra la sesión del usuario autenticado.
     *
     * @param  Request  $request  Solicitud HTTP actual.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        $result = $this->authService->logout($request);

        if ($request->expectsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('login');
    }

    /**
     * Muestra la vista del formulario de inicio de sesión.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function showLoginForm(): View
    {
        return view('auth/login');
    }
}
