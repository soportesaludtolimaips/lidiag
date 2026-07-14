<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Services\Ecografias\EcografiaLecturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ecografiaLecturaController extends Controller
{
    public function __construct(
        private readonly EcografiaLecturaService $lecturaService,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:ecografias.leer,web')->only(['leer', 'listarPendientesPorLectura']);
    }

    public function leer(Request $request, int $id): JsonResponse
    {
        $result = $this->lecturaService->leer($request->all(), $id);

        return response()->json($result, $result['status']);
    }

    public function listarPendientesPorLectura(int $idMedico): JsonResponse
    {
        $result = $this->lecturaService->listarPendientesPorLectura($idMedico);

        return response()->json($result, $result['status']);
    }
}
