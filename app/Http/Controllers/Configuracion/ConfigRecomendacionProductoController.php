<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigRecomendacionProductoRequest;
use App\Http\Requests\Configuracion\UpdateConfigRecomendacionProductoRequest;
use App\Services\Configuracion\ConfigRecomendacionProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigRecomendacionProductoController extends Controller
{
    public function __construct(
        private readonly ConfigRecomendacionProductoService $configRecomendacionProductoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
        $this->middleware('permission:config.recomendaciones.index')->only(['index']);
        $this->middleware('permission:config.recomendaciones.create')->only(['store']);
        $this->middleware('permission:config.recomendaciones.edit')->only(['update']);
        $this->middleware('permission:config.recomendaciones.destroy')->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->configRecomendacionProductoService->all(
            $request->integer('producto_id'),
            $request->boolean('estado'),
        );

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->configRecomendacionProductoService->find($id);

        return response()->json($result, $result['status']);
    }

    public function store(StoreConfigRecomendacionProductoRequest $request): JsonResponse
    {
        $result = $this->configRecomendacionProductoService->createOrUpdate($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(UpdateConfigRecomendacionProductoRequest $request, int $id): JsonResponse
    {
        $result = $this->configRecomendacionProductoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configRecomendacionProductoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
