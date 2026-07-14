<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\ConfigCitaRechazoMotivoRequest;
use App\Services\Agenda\ConfigCitaRechazoMotivoService;
use Illuminate\Http\JsonResponse;

class ConfigCitaRechazoMotivoController extends Controller
{
    public function __construct(
        private readonly ConfigCitaRechazoMotivoService $service,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->service->all();

        return response()->json($result['data'], $result['status']);
    }

    public function store(ConfigCitaRechazoMotivoRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        return response()->json($result['data'], $result['status']);
    }

    public function update(ConfigCitaRechazoMotivoRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());

        return response()->json($result['data'], $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);

        return response()->json($result['message'], $result['status']);
    }
}
