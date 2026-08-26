<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consentimientos\ConsentimientoEstadisticaRequest;
use App\Services\Consentimientos\ConsentimientoEstadisticaService;
use Illuminate\Http\JsonResponse;

class ConsentimientoEstadisticaController extends Controller
{
    public function __construct(
        private readonly ConsentimientoEstadisticaService $service,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:consentimientos.estadisticas,web');
    }

    public function resumen(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->resumen($request->validated());

        return response()->json($result, $result['status']);
    }

    public function porSede(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->porSede($request->validated());

        return response()->json($result, $result['status']);
    }

    public function porProducto(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->porProducto($request->validated());

        return response()->json($result, $result['status']);
    }

    public function porMedico(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->porMedico($request->validated());

        return response()->json($result, $result['status']);
    }

    public function tiemposPromedio(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->tiemposPromedio($request->validated());

        return response()->json($result, $result['status']);
    }

    public function productosPopulares(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->productosPopulares($request->validated());

        return response()->json($result, $result['status']);
    }
}
