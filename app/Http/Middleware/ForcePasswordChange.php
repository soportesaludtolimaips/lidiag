<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    protected array $except = [
        'password/cambiar',
    ];

    /**
     * Fuerza al usuario a cambiar su contraseña si aún no la ha realizado.
     *
     * Omite la validación para las rutas excluidas; si el usuario no tiene
     * registrada la fecha de cambio devuelve 403 en JSON (API) o redirige
     * al formulario de cambio de contraseña.
     *
     * @param  Request  $request  Petición HTTP actual.
     * @param  Closure  $next  Siguiente middleware de la cadena.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route() && in_array($request->route()->uri(), $this->except, true)) {
            return $next($request);
        }

        // Si la petición está autenticada mediante Personal Access Token (Sanctum / Agentes Edge M2M),
        // se omite la exigencia de cambio interactivo de contraseña.
        if ($request->user() && method_exists($request->user(), 'currentAccessToken') && $request->user()->currentAccessToken()) {
            return $next($request);
        }

        if ($request->user() && $request->user()->password_changed_at === null) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Debe cambiar su contraseña.'], 403);
            }

            return redirect()->route('password.cambiar');
        }

        return $next($request);
    }
}
