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

    /**
     * Lista todos los módulos registrados en el sistema.
     *
     * @returns JsonResponse Respuesta JSON con la lista de módulos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-04-11
     */
    public function index(): JsonResponse
    {
        $result = $this->configModuloService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Lista todos los módulos disponibles en el sistema.
     *
     * @returns JsonResponse Respuesta JSON con la lista de módulos disponibles.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-04-11
     */
    public function listarModulos(): JsonResponse
    {
        $result = $this->configModuloService->all();

        return response()->json($result, $result['status']);
    }
}
