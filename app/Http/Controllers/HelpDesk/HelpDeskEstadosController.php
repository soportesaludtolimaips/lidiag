<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\HelpDesk\HelpDeskEstado;
use App\Services\HelpDesk\HelpDeskEstadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpDeskEstadosController extends Controller
{
    public function __construct(
        private readonly HelpDeskEstadoService $estadoService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->estadoService->all();

        return response()->json($result['data'], $result['status']);
    }

    public function store(Request $request): JsonResponse
    {
        $result = $this->estadoService->create();

        return response()->json(['message' => $result['message']], $result['status']);
    }

    public function show(HelpDeskEstado $estado): JsonResponse
    {
        $result = $this->estadoService->find($estado->id);

        return response()->json($result['data'], $result['status']);
    }

    public function update(Request $request, HelpDeskEstado $estado): JsonResponse
    {
        $result = $this->estadoService->update($estado->id);

        return response()->json(['message' => $result['message']], $result['status']);
    }

    public function destroy(HelpDeskEstado $estado): JsonResponse
    {
        $result = $this->estadoService->delete($estado->id);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
