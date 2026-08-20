<?php

namespace App\Http\Controllers\Contratacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contratacion\ContratoDetalleRequest;
use App\Http\Requests\Contratacion\ContratoRequest;
use App\Services\Contratacion\ContratoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContratoController extends Controller
{
    public function __construct(
        private readonly ContratoService $contratoService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:contratos.index')->only(['index']);
        $this->middleware('permission:contratos.create')->only(['store']);
        $this->middleware('permission:contratos.show')->only(['show']);
        $this->middleware('permission:contratos.edit')->only(['update']);
        $this->middleware('permission:contratos.destroy')->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->contratoService->list($request->only([
            'sede_id', 'estado', 'q', 'per_page', 'page',
        ]));

        return response()->json($result, $result['status']);
    }

    public function store(ContratoRequest $request): JsonResponse
    {
        $result = $this->contratoService->create($request->decoded());

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->contratoService->find($id);

        return response()->json($result, $result['status']);
    }

    public function update(ContratoRequest $request, int $id): JsonResponse
    {
        $result = $this->contratoService->update($id, $request->decoded());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->contratoService->delete($id);

        return response()->json($result, $result['status']);
    }

    public function toggleEstado(int $id): JsonResponse
    {
        $result = $this->contratoService->toggleEstado($id);

        return response()->json($result, $result['status']);
    }

    public function storeDetalle(ContratoDetalleRequest $request, int $contratoId): JsonResponse
    {
        $result = $this->contratoService->addDetalle($contratoId, $request->decoded());

        return response()->json($result, $result['status']);
    }

    public function updateDetalle(ContratoDetalleRequest $request, int $detalleId): JsonResponse
    {
        $result = $this->contratoService->updateDetalle($detalleId, $request->decoded());

        return response()->json($result, $result['status']);
    }

    public function destroyDetalle(int $detalleId): JsonResponse
    {
        $result = $this->contratoService->deleteDetalle($detalleId);

        return response()->json($result, $result['status']);
    }

    /**
     * Sincroniza los detalles de un contrato: reemplaza todos los detalles existentes
     * por una nueva lista enviada desde el frontend.
     *
     * @param  Request  $request  Petición HTTP con los datos validados.
     *                            - medico_id (int): Identificación del médico.
     *                            - detalles (array): Lista de productos con valores.
     * @param  int  $contratoId  Identificador del contrato a actualizar.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con el contrato actualizado o error.
     *
     * @author  Jhon Javer Lozano Arce
     *
     * @date    2026-08-20
     */
    public function syncDetalles(Request $request, int $contratoId): JsonResponse
    {
        $validated = $request->validate([
            'medico_id' => 'required|integer|exists:users,id',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|integer|exists:config_productos,id',
            'detalles.*.valor_contrato' => 'required|numeric|min:0',
            'detalles.*.valor_consulta' => 'required|numeric|min:0',
            'detalles.*.valor_urgencias' => 'required|numeric|min:0',
        ]);

        $result = $this->contratoService->syncDetalles($contratoId, $validated);

        return response()->json($result, $result['status']);
    }
}
