<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consentimientos\ConsentimientoMarcaStoreRequest;
use App\Http\Requests\Consentimientos\ConsentimientoMarcaUpdateRequest;
use App\Services\Consentimientos\ConsentimientoMarcaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsentimientoMarcaController extends Controller
{
    public function __construct(
        private readonly ConsentimientoMarcaService $service,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:consentimientos.show,web')->only(['index']);
        $this->middleware('permission:consentimientos.marcar,web')->only(['store', 'update', 'destroy']);
    }

    public function index(int $documentoId): JsonResponse
    {
        $result = $this->service->index($documentoId);

        return response()->json($result, $result['status']);
    }

    public function store(ConsentimientoMarcaStoreRequest $request, int $documentoId): JsonResponse
    {
        $result = $this->service->store($documentoId, $request->validated(), $request->user(), $request->ip());

        return response()->json($result, $result['status']);
    }

    public function update(ConsentimientoMarcaUpdateRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated(), $request->user(), $request->ip());

        return response()->json($result, $result['status']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $result = $this->service->destroy($id, $request->user(), $request->ip());

        return response()->json($result, $result['status']);
    }
}
