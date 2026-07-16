<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\HelpDesk\HelpDeskCategoriaRequest;
use App\Models\HelpDesk\HelpDeskCategoria;
use App\Services\HelpDesk\HelpDeskCategoriaService;
use Illuminate\Http\JsonResponse;

class HelpDeskCategoriasController extends Controller
{
    public function __construct(
        private readonly HelpDeskCategoriaService $categoriaService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->categoriaService->all();

        return response()->json($result, $result["status"]);
    }

    public function store(HelpDeskCategoriaRequest $request): JsonResponse
    {
        $result = $this->categoriaService->create($request->validated());

        return response()->json($result, $result["status"]);
    }

    public function show(HelpDeskCategoria $categoria): JsonResponse
    {
        $result = $this->categoriaService->find($categoria->id);

        return response()->json($result, $result["status"]);
    }

    public function update(HelpDeskCategoriaRequest $request, HelpDeskCategoria $categoria): JsonResponse
    {
        $result = $this->categoriaService->update($categoria->id, $request->validated());

        return response()->json($result, $result["status"]);
    }

    public function destroy(HelpDeskCategoria $categoria): JsonResponse
    {
        $result = $this->categoriaService->delete($categoria->id);

        return response()->json(['ok' => true], $result['status']);
    }
}
