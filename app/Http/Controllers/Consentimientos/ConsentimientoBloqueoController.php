<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consentimientos\ConsentimientoLiberarBloqueoRequest;
use App\Services\Consentimientos\ConsentimientoBloqueoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsentimientoBloqueoController extends Controller
{
    public function __construct(
        private readonly ConsentimientoBloqueoService $service,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:consentimientos.liberar_bloqueo,web')->only(['liberar']);
    }

    /**
     * Estado de bloqueo del médico autenticado (para el modal al iniciar sesión).
     */
    public function estado(Request $request): JsonResponse
    {
        $result = $this->service->estadoPara($request->user());

        return response()->json($result, $result['status']);
    }

    /**
     * Liberación temporal del bloqueo (solo administradores).
     */
    public function liberar(ConsentimientoLiberarBloqueoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->service->liberar((int) $data['medico_id'], $data, $request->user());

        return response()->json($result, $result['status']);
    }
}
