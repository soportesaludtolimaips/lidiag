<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigPlantillaTipoRequest;
use App\Http\Requests\Configuracion\UpdateConfigPlantillaTipoRequest;
use App\Services\Configuracion\ConfigPlantillaTipoService;
use Illuminate\Http\JsonResponse;

class ConfigPlantillaTipoController extends Controller
{
    public function __construct(
        private readonly ConfigPlantillaTipoService $configPlantillaTipoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'listarActivos']);
        $this->middleware('permission:configuracion.plantillas-tipos.create')->only(['store']);
        $this->middleware('permission:configuracion.plantillas-tipos.edit')->only(['update']);
        $this->middleware('permission:configuracion.plantillas-tipos.destroy')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->configPlantillaTipoService->all();

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->configPlantillaTipoService->find($id);

        return response()->json($result, $result['status']);
    }

    public function listarActivos(): JsonResponse
    {
        $result = $this->configPlantillaTipoService->listarActivos();

        return response()->json($result, $result['status']);
    }

    public function store(StoreConfigPlantillaTipoRequest $request): JsonResponse
    {
        $result = $this->configPlantillaTipoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(UpdateConfigPlantillaTipoRequest $request, int $id): JsonResponse
    {
        $result = $this->configPlantillaTipoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configPlantillaTipoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
