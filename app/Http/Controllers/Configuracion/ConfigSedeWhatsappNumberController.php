<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigSedeWhatsappNumberRequest;
use App\Http\Requests\Configuracion\UpdateConfigSedeWhatsappNumberRequest;
use App\Services\Configuracion\ConfigSedeWhatsappNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigSedeWhatsappNumberController extends Controller
{
    public function __construct(
        private readonly ConfigSedeWhatsappNumberService $configSedeWhatsappNumberService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->configSedeWhatsappNumberService->all($request->integer('sede_id'));

        return response()->json($result, $result['status']);
    }

    public function store(StoreConfigSedeWhatsappNumberRequest $request): JsonResponse
    {
        $result = $this->configSedeWhatsappNumberService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function update(UpdateConfigSedeWhatsappNumberRequest $request, int $id): JsonResponse
    {
        $result = $this->configSedeWhatsappNumberService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->configSedeWhatsappNumberService->delete($id);

        return response()->json($result, $result['status']);
    }
}
