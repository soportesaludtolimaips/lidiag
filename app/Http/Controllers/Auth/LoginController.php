<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\loginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Procesa el inicio de sesión del usuario.
     *
     * @param loginRequest $request Email y contraseña del usuario.
     * @return \Illuminate\Http\RedirectResponse Redirección al dashboard o al login con errores.
     */
    public function login(loginRequest $request)
    {
        $email = $request->email;
        $password = $request->password;

        $res = Auth::attempt(['email' => $email, 'password' => $password, 'estado' => 1]);

        if ($res) {
            // Evita session fixation: regenerar el ID de sesión tras login
            $request->session()->regenerate();

            $redirectTo = route('dashboard');

            // Si es llamada AJAX/JSON (Axios), devolver JSON (evita que Axios "trague" un HTML redirect)
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'redirect' => $redirectTo,
                ], 200);
            }

            // En este proyecto queremos SIEMPRE ir al dashboard luego del login.
            // (Evita redirecciones inesperadas a /home por "intended url")
            return redirect()->to($redirectTo);
        } else {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Credenciales inválidas',
                ], 401);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Credenciales inválidas'])
                ->withInput($request->only('email'));
        }
    }

    /**
     * Cierra la sesión del usuario autenticado.
     *
     * @param Request $request Solicitud HTTP.
     * @return \Illuminate\Http\JsonResponse Código de respuesta.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalida la sesión y regenera el token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('login');
    }

    /**
     * Muestra el formulario de inicio de sesión.
     *
     * @return \Illuminate\View\View Vista del login.
     */
    public function showLoginForm()
    {
        return view('auth/login');
    }
}
