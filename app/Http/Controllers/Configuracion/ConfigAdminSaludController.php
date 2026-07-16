<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigAdminSaludRequest;
use App\Services\Configuracion\ConfigAdminSaludService;
use Illuminate\Http\JsonResponse;

class ConfigAdminSaludController extends Controller
{
    public function __construct(
        private readonly ConfigAdminSaludService $configAdminSaludService,
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
        $this->middleware('permission:config.admin.salud.index')->only(['index']);
        $this->middleware('permission:config.admin.salud.create')->only(['store']);
        $this->middleware('permission:config.admin.salud.edit')->only(['update']);
        $this->middleware('permission:config.admin.salud.destroy')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->configAdminSaludService->all();

        return response()->json($result, $result['status']);
    }

    public function store(ConfigAdminSaludRequest $request): JsonResponse
    {
        $result = $this->configAdminSaludService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(ConfigAdminSaludRequest $request, int $id): JsonResponse
    {
        $result = $this->configAdminSaludService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configAdminSaludService->delete($id);

        return response()->json($result, $result['status']);
    }
}
