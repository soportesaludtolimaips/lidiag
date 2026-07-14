<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigWhatsappRequest;
use App\Http\Requests\Configuracion\UpdateConfigWhatsappRequest;
use App\Services\Configuracion\ConfigWhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigWhatsappController extends Controller
{
    public function __construct(
        private readonly ConfigWhatsappService $configWhatsappService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->configWhatsappService->all($request->integer('sede_id'));

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->configWhatsappService->find($id);

        return response()->json($result, $result['status']);
    }

    public function store(StoreConfigWhatsappRequest $request): JsonResponse
    {
        $result = $this->configWhatsappService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(UpdateConfigWhatsappRequest $request, int $id): JsonResponse
    {
        $result = $this->configWhatsappService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configWhatsappService->delete($id);

        return response()->json($result, $result['status']);
    }

    public function buscar(Request $request): JsonResponse
    {
        $result = $this->configWhatsappService->buscarPorSedeYServicio(
            (int) $request->input('sede_id'),
            (int) $request->input('servicio_cita_id'),
        );

        return response()->json($result, $result['status']);
    }
}
