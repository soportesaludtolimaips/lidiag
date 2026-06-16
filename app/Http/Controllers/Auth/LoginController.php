<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\loginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Procesa el inicio de sesión del usuario.
     *
     * @param  loginRequest  $request  Email y contraseña del usuario.
     * @return RedirectResponse Redirección al dashboard o al login con errores.
     */
    public function login(loginRequest $request)
    {
        $email = $request->email;
        $password = $request->password;

        $res = Auth::attempt(['email' => $email, 'password' => $password, 'estado' => 1]);

        if ($res) {
            // Detectar si el usuario tenía una sesión anterior que terminó abruptamente
            try {
                $sesionAnterior = DB::table('sessions')
                    ->where('user_id', Auth::id())
                    ->where('id', '!=', session()->getId())
                    ->first();

                if ($sesionAnterior) {
                    Log::warning('SESION_ABRUPTA', [
                        'user_id' => Auth::id(),
                        'email' => $email,
                        'sesion_anterior_id' => $sesionAnterior->id,
                        'ultima_actividad' => $sesionAnterior->last_activity
                            ? date('Y-m-d H:i:s', $sesionAnterior->last_activity)
                            : null,
                        'ip_actual' => $request->ip(),
                        'login_at' => now()->toDateTimeString(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('SESION_ABRUPTA_ERROR', ['error' => $e->getMessage()]);
            }

            // Evita session fixation: regenerar el ID de sesión tras login
            $request->session()->regenerate();

            Log::info('SESION_LOGIN', [
                'user_id' => Auth::id(),
                'email' => $email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
                'login_at' => now()->toDateTimeString(),
            ]);

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
     * @param  Request  $request  Solicitud HTTP.
     * @return JsonResponse Código de respuesta.
     */
    public function logout(Request $request)
    {
        Log::info('SESION_LOGOUT', [
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'logout_at' => now()->toDateTimeString(),
        ]);

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
     * @return View Vista del login.
     */
    public function showLoginForm()
    {
        return view('auth/login');
    }
}
