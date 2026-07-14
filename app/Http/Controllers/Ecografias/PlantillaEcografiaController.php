<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Services\Ecografias\PlantillaEcografiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantillaEcografiaController extends Controller
{
    public function __construct(
        private readonly PlantillaEcografiaService $plantillaService,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:ecografias.reporte,web')->only(['editWord']);
    }

    public function editWord(Request $request): JsonResponse
    {
        $result = $this->plantillaService->editWord($request->all());

        return response()->json($result, $result['status']);
    }
}
