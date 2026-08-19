<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Services\Configuracion\ConfigModuloService;
use Illuminate\Http\JsonResponse;

class ConfigModulosController extends Controller
{
    public function __construct(
        private readonly ConfigModuloService $configModuloService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->configModuloService->all();

        return response()->json($result, $result['status']);
    }

    public function listarModulos(): JsonResponse
    {
        $result = $this->configModuloService->all();

        return response()->json($result, $result['status']);
    }
}
