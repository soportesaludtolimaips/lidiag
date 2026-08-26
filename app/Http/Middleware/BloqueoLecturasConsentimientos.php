<?php

namespace App\Http\Middleware;

use App\Services\Consentimientos\ConsentimientoBloqueoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea los endpoints de lectura cuando el médico autenticado tiene
 * consentimientos pendientes de su firma (sin liberación vigente).
 */
class BloqueoLecturasConsentimientos
{
    public function __construct(
        private readonly ConsentimientoBloqueoService $bloqueoService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $this->bloqueoService->estaBloqueado((int) $user->id)) {
            return response()->json([
                'success' => false,
                'data' => ['bloqueado' => true],
                'message' => 'Tiene consentimientos pendientes de firma. Debe firmarlos (o solicitar liberación a un administrador) para continuar con las lecturas.',
                'status' => 423,
            ], 423);
        }

        return $next($request);
    }
}
