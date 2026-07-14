<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigServicioCitaRequest;
use App\Http\Requests\Configuracion\UpdateConfigServicioCitaRequest;
use App\Services\Configuracion\ConfigServicioCitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigServicioCitasController extends Controller
{
    public function __construct(
        private readonly ConfigServicioCitaService $configServicioCitaService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->configServicioCitaService->all($request->integer('sede_id'));

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->configServicioCitaService->find($id);

        return response()->json($result, $result['status']);
    }

    public function store(StoreConfigServicioCitaRequest $request): JsonResponse
    {
        $result = $this->configServicioCitaService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(UpdateConfigServicioCitaRequest $request, int $id): JsonResponse
    {
        $result = $this->configServicioCitaService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configServicioCitaService->delete($id);

        return response()->json($result, $result['status']);
    }
}
