<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Services\Estudios\EstudioDiagnosticoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudioDiagnosticoController extends Controller
{
    public function __construct(
        private readonly EstudioDiagnosticoService $estudioDiagnosticoService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->all();

        return response()->json($result, $result['status']);
    }

    public function store(Request $request): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->create($request->all());

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->find($id);

        return response()->json($result, $result['status']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->update($id, $request->all());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
