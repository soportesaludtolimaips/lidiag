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

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route() && in_array($request->route()->uri(), $this->except, true)) {
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
