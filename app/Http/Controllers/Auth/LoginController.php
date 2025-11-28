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
            return redirect()->route('dashboard');
        } else {
            return redirect()->route('login')->withErrors(['Invalid credentials']);
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

        return response()->json(['code' => 204]);
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
