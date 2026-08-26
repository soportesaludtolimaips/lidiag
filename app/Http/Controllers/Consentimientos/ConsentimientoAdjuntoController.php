<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consentimientos\ConsentimientoAdjuntoStoreRequest;
use App\Services\Consentimientos\ConsentimientoAdjuntoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsentimientoAdjuntoController extends Controller
{
    public function __construct(
        private readonly ConsentimientoAdjuntoService $service,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:consentimientos.show,web')->only(['index', 'download']);
        $this->middleware('permission:consentimientos.adjuntos,web')->only(['store', 'destroy']);
    }

    public function index(int $documentoId): JsonResponse
    {
        $result = $this->service->index($documentoId);

        return response()->json($result, $result['status']);
    }

    public function store(ConsentimientoAdjuntoStoreRequest $request, int $documentoId): JsonResponse
    {
        $result = $this->service->store(
            $documentoId,
            $request->file('archivo'),
            $request->validated(),
            $request->user(),
            $request->ip(),
        );

        return response()->json($result, $result['status']);
    }

    public function download(Request $request, int $id)
    {
        $result = $this->service->download($id);

        if ($result['status'] !== 200) {
            return response()->json($result, $result['status']);
        }

        return response()
            ->download($result['data']['tmp_path'], $result['data']['download_name'])
            ->deleteFileAfterSend(true);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $result = $this->service->destroy($id, $request->user(), $request->ip());

        return response()->json($result, $result['status']);
    }
}
