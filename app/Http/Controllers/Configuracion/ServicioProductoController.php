<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreServicioProductoRequest;
use App\Services\Configuracion\ServicioProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicioProductoController extends Controller
{
    public function __construct(
        private readonly ServicioProductoService $servicioProductoService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->servicioProductoService->all($request->integer('servicio_cita_id'));

        return response()->json($result, $result['status']);
    }

    public function store(StoreServicioProductoRequest $request): JsonResponse
    {
        $result = $this->servicioProductoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->servicioProductoService->delete($id);

        return response()->json($result, $result['status']);
    }

    public function productosNoVinculados(Request $request): JsonResponse
    {
        $result = $this->servicioProductoService->productosNoVinculados(
            (int) $request->input('servicio_cita_id')
        );

        return response()->json($result, $result['status']);
    }
}
