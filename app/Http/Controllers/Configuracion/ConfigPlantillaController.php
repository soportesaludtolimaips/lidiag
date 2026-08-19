<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigPlantillaRequest;
use App\Http\Requests\Configuracion\UpdateConfigPlantillaRequest;
use App\Services\Configuracion\ConfigPlantillaService;
use Illuminate\Http\JsonResponse;

class ConfigPlantillaController extends Controller
{
    public function __construct(
        private readonly ConfigPlantillaService $configPlantillaService,
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'listarActivos', 'listarActivosPorTipo']);
        $this->middleware('permission:config.plantillas.create')->only(['store']);
        $this->middleware('permission:config.plantillas.edit')->only(['update']);
        $this->middleware('permission:config.plantillas.delete')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->configPlantillaService->all();

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->configPlantillaService->find($id);

        return response()->json($result, $result['status']);
    }

    public function listarActivos(): JsonResponse
    {
        $result = $this->configPlantillaService->listarActivos();

        return response()->json($result, $result['status']);
    }

    public function listarActivosPorTipo(int $tipoId, int $medicoId): JsonResponse
    {
        $result = $this->configPlantillaService->listarActivosPorTipo($tipoId, $medicoId);

        return response()->json($result, $result['status']);
    }

    public function store(StoreConfigPlantillaRequest $request): JsonResponse
    {
        $result = $this->configPlantillaService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(UpdateConfigPlantillaRequest $request, int $id): JsonResponse
    {
        $result = $this->configPlantillaService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configPlantillaService->delete($id);

        return response()->json($result, $result['status']);
    }
}
