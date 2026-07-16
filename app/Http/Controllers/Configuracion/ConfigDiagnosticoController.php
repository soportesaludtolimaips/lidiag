<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigDiagnosticoRequest;
use App\Services\Configuracion\ConfigDiagnosticoService;
use Illuminate\Http\JsonResponse;

class ConfigDiagnosticoController extends Controller
{
    public function __construct(
        private readonly ConfigDiagnosticoService $configDiagnosticoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
        $this->middleware('permission:config.diagnosticos.index')->only(['index']);
        $this->middleware('permission:config.diagnosticos.create')->only(['store']);
        $this->middleware('permission:config.diagnosticos.edit')->only(['update']);
        $this->middleware('permission:config.diagnosticos.destroy')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->configDiagnosticoService->all();

        return response()->json($result, $result['status']);
    }

    public function store(ConfigDiagnosticoRequest $request): JsonResponse
    {
        $result = $this->configDiagnosticoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(ConfigDiagnosticoRequest $request, int $id): JsonResponse
    {
        $result = $this->configDiagnosticoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configDiagnosticoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
