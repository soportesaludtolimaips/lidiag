<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->password_changed_at === null) {
            return response()->json(['success' => false, 'message' => 'Debe cambiar su contraseña.'], 403);
        }

        return $next($request);
    }
}
