<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\PlantillaEcografiaEditWordRequest;
use App\Services\Ecografias\PlantillaEcografiaService;
use Illuminate\Http\JsonResponse;

class PlantillaEcografiaController extends Controller
{
    public function __construct(
        private readonly PlantillaEcografiaService $plantillaService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:ecografias.reporte,web')->only(['editWord']);
    }

    public function editWord(PlantillaEcografiaEditWordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->plantillaService->editWord($data);

        return response()->json($result, $result['status']);
    }
}
