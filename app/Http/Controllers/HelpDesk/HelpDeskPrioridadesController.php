<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\HelpDesk\HelpDeskPrioridadRequest;
use App\Models\HelpDesk\HelpDeskPrioridad;
use App\Services\HelpDesk\HelpDeskPrioridadService;
use Illuminate\Http\JsonResponse;

class HelpDeskPrioridadesController extends Controller
{
    public function __construct(
        private readonly HelpDeskPrioridadService $prioridadService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->prioridadService->all();

        return response()->json($result, $result["status"]);
    }

    public function store(HelpDeskPrioridadRequest $request): JsonResponse
    {
        $result = $this->prioridadService->create($request->validated());

        return response()->json($result, $result["status"]);
    }

    public function show(HelpDeskPrioridad $prioridad): JsonResponse
    {
        $result = $this->prioridadService->find($prioridad->id);

        return response()->json($result, $result["status"]);
    }

    public function update(HelpDeskPrioridadRequest $request, HelpDeskPrioridad $prioridad): JsonResponse
    {
        $result = $this->prioridadService->update($prioridad->id, $request->validated());

        return response()->json($result, $result["status"]);
    }

    public function destroy(HelpDeskPrioridad $prioridad): JsonResponse
    {
        $result = $this->prioridadService->delete($prioridad->id);

        return response()->json(['ok' => true], $result['status']);
    }
}
