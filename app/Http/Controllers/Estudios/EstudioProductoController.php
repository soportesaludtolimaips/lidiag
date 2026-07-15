<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\StoreEstudioProductoRequest;
use App\Http\Requests\Estudios\UpdateEstudioProductoRequest;
use App\Services\Estudios\EstudioProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudioProductoController extends Controller
{
    public function __construct(
        private readonly EstudioProductoService $estudioProductoService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->estudioProductoService->listarPorEstudio($request->integer('id'));

        return response()->json($result, $result['status']);
    }

    public function store(StoreEstudioProductoRequest $request): JsonResponse
    {
        $result = $this->estudioProductoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(UpdateEstudioProductoRequest $request, int $id): JsonResponse
    {
        $result = $this->estudioProductoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->estudioProductoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
