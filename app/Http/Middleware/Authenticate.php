<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    protected function unauthenticated($request, array $guards)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            $sessionExists = false;
            if (session()->isStarted()) {
                try {
                    $sessionExists = DB::table('sessions')
                        ->where('id', session()->getId())
                        ->exists();
                } catch (\Throwable $e) {
                    $sessionExists = false;
                }
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
