<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Services\Estudios\PlantillaEstudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantillaEstudioController extends Controller
{
    public function __construct(
        private readonly PlantillaEstudioService $plantillaEstudioService,
    ) {}

    public function editWord(Request $request): JsonResponse
    {
        $result = $this->plantillaEstudioService->editWord($request->all());

        return response()->json($result, $result['status']);
    }

    public function convertDocxToPdf($nomPlantilla): bool
    {
        return $this->plantillaEstudioService->convertDocxToPdf($nomPlantilla);
    }
}
