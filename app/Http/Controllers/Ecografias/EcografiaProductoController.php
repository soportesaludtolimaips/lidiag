<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaProductoRequest;
use App\Services\Ecografias\EcografiaProductoService;
use Illuminate\Http\JsonResponse;

class EcografiaProductoController extends Controller
{
    public function __construct(
        private readonly EcografiaProductoService $productoService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->productoService->all();

        return response()->json($result, $result['status']);
    }

    public function store(EcografiaProductoRequest $request): JsonResponse
    {
        $result = $this->productoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->productoService->find($id);

        return response()->json($result, $result['status']);
    }

    public function update(EcografiaProductoRequest $request, int $id): JsonResponse
    {
        $result = $this->productoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->productoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
