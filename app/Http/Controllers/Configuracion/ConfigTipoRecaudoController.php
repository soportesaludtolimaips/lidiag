<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Services\Configuracion\ConfigTipoRecaudoService;
use Illuminate\Http\JsonResponse;

class ConfigTipoRecaudoController extends Controller
{
    /**
     * Inyecta el servicio de tipos de recaudo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function __construct(
        private readonly ConfigTipoRecaudoService $tipoRecaudoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'listarActivos']);
    }

    /**
     * Lista todos los tipos de recaudo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function index(): JsonResponse
    {
        $result = $this->tipoRecaudoService->all();

        return response()->json($result, (int) $result['status']);
    }

    /**
     * Lista los tipos de recaudo activos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function listarActivos(): JsonResponse
    {
        $result = $this->tipoRecaudoService->listarActivos();

        return response()->json($result, (int) $result['status']);
    }
}
