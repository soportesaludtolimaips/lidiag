<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\StoreEstudioDiagnosticoRequest;
use App\Http\Requests\Estudios\UpdateEstudioDiagnosticoRequest;
use App\Services\Estudios\EstudioDiagnosticoService;
use Illuminate\Http\JsonResponse;

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

    public function store(StoreEstudioDiagnosticoRequest $request): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->find($id);

        return response()->json($result, $result['status']);
    }

    public function update(UpdateEstudioDiagnosticoRequest $request, int $id): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
