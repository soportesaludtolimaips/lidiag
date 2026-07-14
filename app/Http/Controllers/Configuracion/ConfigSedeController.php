<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigSedeRequest;
use App\Http\Requests\Configuracion\UpdateConfigSedeRequest;
use App\Services\Configuracion\ConfigSedeService;
use Illuminate\Http\JsonResponse;

class ConfigSedeController extends Controller
{
    public function __construct(
        private readonly ConfigSedeService $configSedeService,
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
        $this->middleware('permission:configuracion.sedes.index')->only(['index', 'buscarPorId']);
        $this->middleware('permission:configuracion.sedes.create')->only(['store']);
        $this->middleware('permission:configuracion.sedes.edit')->only(['update']);
        $this->middleware('permission:configuracion.sedes.destroy')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->configSedeService->all();

        return response()->json($result, $result['status']);
    }

    public function buscarPorId(int $id): JsonResponse
    {
        $result = $this->configSedeService->buscarPorId($id);

        return response()->json($result, $result['status']);
    }

    public function listarSedesActivas(): JsonResponse
    {
        $result = $this->configSedeService->listarActivas();

        return response()->json($result, $result['status']);
    }

    public function store(StoreConfigSedeRequest $request): JsonResponse
    {
        $result = $this->configSedeService->create(
            $request->decoded(),
            $request->file('plantilla_rx'),
            $request->file('plantilla_rx_ecografia'),
        );

        return response()->json($result, $result['status']);
    }

    public function update(UpdateConfigSedeRequest $request): JsonResponse
    {
        $result = $this->configSedeService->update(
            $request->decoded(),
            $request->file('plantilla_rx'),
            $request->file('plantilla_rx_ecografia'),
        );

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configSedeService->delete($id);

        return response()->json($result, $result['status']);
    }
}
