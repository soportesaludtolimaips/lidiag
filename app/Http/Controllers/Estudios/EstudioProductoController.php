<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
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

    public function store(Request $request): JsonResponse
    {
        $result = $this->estudioProductoService->create($request->all());

        return response()->json($result, $result['status']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $result = $this->estudioProductoService->update($id, $request->all());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->estudioProductoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
