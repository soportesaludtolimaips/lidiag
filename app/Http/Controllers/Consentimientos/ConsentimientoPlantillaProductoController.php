<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consentimientos\ConsentimientoPlantillaProductoStoreRequest;
use App\Services\Consentimientos\ConsentimientoPlantillaProductoService;
use Illuminate\Http\JsonResponse;

class ConsentimientoPlantillaProductoController extends Controller
{
    public function __construct(
        private readonly ConsentimientoPlantillaProductoService $service,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:consentimientos.configurar,web');
    }

    public function index(int $plantillaId): JsonResponse
    {
        $result = $this->service->index($plantillaId);

        return response()->json($result, $result['status']);
    }

    public function store(ConsentimientoPlantillaProductoStoreRequest $request, int $plantillaId): JsonResponse
    {
        $result = $this->service->store($plantillaId, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->destroy($id);

        return response()->json($result, $result['status']);
    }
}
