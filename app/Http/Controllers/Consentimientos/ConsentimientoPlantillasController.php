<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consentimientos\ConsentimientoPlantillaIndexRequest;
use App\Http\Requests\Consentimientos\ConsentimientoPlantillaRequest;
use App\Services\Consentimientos\ConsentimientoPlantillaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsentimientoPlantillasController extends Controller
{
    public function __construct(
        private readonly ConsentimientoPlantillaService $service,
    ) {
        $this->middleware('auth');
        $this->middleware('auth:sanctum');
        $this->middleware('permission:ecografias.consentimientos.plantillas.index,web')->only(['index', 'show', 'versions', 'comparar']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.create,web')->only(['store']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.edit,web')->only(['update', 'setVigente']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.destroy,web')->only(['destroy']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.preview_pdf,web')->only(['previewPdf']);
    }

    public function index(ConsentimientoPlantillaIndexRequest $request): JsonResponse
    {
        $result = $this->service->list([
            'config_modulo_id' => $request->config_modulo_id,
            'q' => $request->query('q', ''),
            'vigentes' => $request->query('vigentes', '1'),
        ]);

        return response()->json($result, $result["status"]);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->find($id);

        return response()->json($result, $result["status"]);
    }

    public function store(ConsentimientoPlantillaRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated(), $request->user()->id);

        return response()->json($result, $result["status"]);
    }

    public function update(ConsentimientoPlantillaRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated(), $request->user()->id);

        return response()->json($result, $result["status"]);
    }

    public function versions(int $productoId, Request $request): JsonResponse
    {
        $moduloId = (int) $request->query('config_modulo_id', 0);
        if ($moduloId <= 0) {
            return response()->json(['message' => 'config_modulo_id es obligatorio'], 422);
        }
        $result = $this->service->versions($productoId, $moduloId);

        return response()->json($result, $result["status"]);
    }

    public function comparar(Request $request): JsonResponse
    {
        $v1 = (int) $request->query('v1', 0);
        $v2 = (int) $request->query('v2', 0);
        if ($v1 <= 0 || $v2 <= 0) {
            return response()->json(['message' => 'v1 y v2 son obligatorios'], 422);
        }

        $result = $this->service->comparar($v1, $v2);

        return response()->json($result, $result['status']);
    }

    public function setVigente(int $id): JsonResponse
    {
        $result = $this->service->setVigente($id);

        return response()->json($result, $result["status"]);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);

        return response()->json(['ok' => true], $result['status']);
    }

    public function previewPdf(int $id)
    {
        return $this->service->previewPdf($id);
    }
}
