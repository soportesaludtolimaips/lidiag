<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConsentimientoInformadoRequest;
use App\Http\Requests\Configuracion\UpdateConsentimientoInformadoRequest;
use App\Services\Configuracion\ConsentimientoInformadoService;
use Illuminate\Http\JsonResponse;

class ConsentimientoInformadoController extends Controller
{
    public function __construct(
        private readonly ConsentimientoInformadoService $consentimientoInformadoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'listarActivos']);
        $this->middleware('permission:configuracion.consentimientos.index')->only(['index', 'listarActivos', 'show']);
        $this->middleware('permission:configuracion.consentimientos.create')->only(['store']);
        $this->middleware('permission:configuracion.consentimientos.edit')->only(['update']);
        $this->middleware('permission:configuracion.consentimientos.destroy')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->consentimientoInformadoService->all();

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->consentimientoInformadoService->find($id);

        return response()->json($result, $result['status']);
    }

    public function listarActivos(): JsonResponse
    {
        $result = $this->consentimientoInformadoService->listarActivos();

        return response()->json($result, $result['status']);
    }

    public function store(StoreConsentimientoInformadoRequest $request): JsonResponse
    {
        $result = $this->consentimientoInformadoService->create(
            $request->validated(),
            $request->file('archivo'),
        );

        return response()->json($result, $result['status']);
    }

    public function update(UpdateConsentimientoInformadoRequest $request, int $id): JsonResponse
    {
        $result = $this->consentimientoInformadoService->update(
            $id,
            $request->validated(),
            $request->file('archivo'),
        );

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->consentimientoInformadoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
