<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigPrioridadRequest;
use App\Services\Configuracion\ConfigPrioridadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigPrioridadController extends Controller
{
    public function __construct(
        private readonly ConfigPrioridadService $configPrioridadService,
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
        $this->middleware('permission:config.prioridades.index')->only(['index']);
        $this->middleware('permission:config.prioridades.create')->only(['store']);
        $this->middleware('permission:config.prioridades.edit')->only(['update']);
        $this->middleware('permission:config.prioridades.destroy')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->configPrioridadService->all();

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->configPrioridadService->find($id);

        return response()->json($result, $result['status']);
    }

    public function store(ConfigPrioridadRequest $request): JsonResponse
    {
        $result = $this->configPrioridadService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'nom_priori' => 'required|string|max:255',
            'nivel' => 'nullable|integer',
            'tiempo' => 'nullable|integer',
            'estado' => 'nullable|boolean',
        ]);

        $result = $this->configPrioridadService->update($id, $data);

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configPrioridadService->delete($id);

        return response()->json($result, $result['status']);
    }
}
