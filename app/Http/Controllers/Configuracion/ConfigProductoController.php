<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigProductoRequest;
use App\Services\Configuracion\ConfigProductoService;
use Illuminate\Http\JsonResponse;

class ConfigProductoController extends Controller
{
    public function __construct(
        private readonly ConfigProductoService $configProductoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
        $this->middleware('permission:config.productos.index')->only(['index']);
        $this->middleware('permission:config.productos.create')->only(['store']);
        $this->middleware('permission:config.productos.edit')->only(['update']);
        $this->middleware('permission:config.productos.destroy')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->configProductoService->all();

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->configProductoService->find($id);

        return response()->json($result, $result['status']);
    }

    public function store(ConfigProductoRequest $request): JsonResponse
    {
        $result = $this->configProductoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(ConfigProductoRequest $request, int $id): JsonResponse
    {
        $result = $this->configProductoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configProductoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
