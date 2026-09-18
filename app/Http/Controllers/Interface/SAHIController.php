<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\SAHIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SAHIController extends Controller
{
    public function __construct(
        private readonly SAHIService $sahiService,
    ) {}

    public function listarAtenciones(Request $request): JsonResponse
    {
        $numDocu = $request->input('numDocu') ?? $request->route('numDocu');

        $result = $this->sahiService->listarAtenciones($numDocu);

        return response()->json($result, $result['status']);
    }

    public function listarProductosDiagnosticos(Request $request): JsonResponse
    {
        $idAtencion = $request->input('idAtencion') ?? $request->route('idAtencion');

        $result = $this->sahiService->listarProductosDiagnosticos($idAtencion ? (int) $idAtencion : null);

        return response()->json($result, $result['status']);
    }

    public function listarProductosAtencion(Request $request): JsonResponse
    {
        $result = $this->sahiService->listarProductosAtencion($request->input('bus_nom_num_docu'));

        return response()->json($result, $result['status']);
    }
}
