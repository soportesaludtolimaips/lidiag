<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

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

    // Sobrescribimos este método para asegurar que la API no use sesiones
    protected function unauthenticated($request, array $guards)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            abort(response()->json(['message' => 'Unauthenticated.'], 401));
        }

        parent::unauthenticated($request, $guards);
    }
}
