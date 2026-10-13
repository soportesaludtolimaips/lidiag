<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // Si la petición es para la API (comienza por /api)
        // o si el cliente envió el header 'Accept: application/json',
        // NO redirigimos, para que Laravel lance el error 401 en JSON.
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        // Solo para usuarios navegando en la web normal (URL sin /api)
        return route('login');
    }

    // Add new method
    // protected function unauthenticated($request, array $guards) {}

    /**
     * Maneja la respuesta cuando el usuario no está autenticado.
     *
     * En peticiones API/JSON registra el motivo (incluida la sesión expirada
     * si existe) y responde 401 en JSON; en el resto delega en Laravel.
     *
     * @param  Request  $request  Petición HTTP actual.
     * @param  array  $guards  Guardias de autenticación evaluadas.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function unauthenticated($request, array $guards)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            $sessionExists = false;
            $sessionData = null;
            if (session()->isStarted()) {
                try {
                    $sessionData = DB::table('sessions')
                        ->where('id', session()->getId())
                        ->first();
                    $sessionExists = (bool) $sessionData;
                } catch (\Throwable $e) {
                    $sessionExists = false;
                }
            }

            if ($sessionExists && $sessionData && $sessionData->user_id) {
                Log::warning('SESION_EXPIRADA', [
                    'user_id' => $sessionData->user_id,
                    'session_id' => session()->getId(),
                    'ultima_actividad' => $sessionData->last_activity
                        ? date('Y-m-d H:i:s', $sessionData->last_activity)
                        : null,
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'fecha' => now()->toDateTimeString(),
                ]);
            }

            logger()->warning('401 - Request no autenticado', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'session_exists' => $sessionExists,
                'cookies' => collect($request->cookies->all())->keys()->toArray(),
            ]);

            abort(response()->json(['message' => 'Unauthenticated.'], 401));
        }

        parent::unauthenticated($request, $guards);
    }
}
