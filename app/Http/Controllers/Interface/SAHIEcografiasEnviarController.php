<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interfaces\EnviarEcografiaRequest;
use App\Services\Interfaces\SAHIEcografiasEnviarService;
use Illuminate\Http\JsonResponse;

class SAHIEcografiasEnviarController extends Controller
{
    public function __construct(
        private readonly SAHIEcografiasEnviarService $sahiEcografiasEnviarService,
    ) {}

    /**
     * Consulta el estado de envío a SAHI de un producto de ecografía.
     *
     * @param  int  $ecografia_producto_id  Identificador del producto de ecografía.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-29
     */
    public function estadoPorEcografiaProducto(int $ecografia_producto_id): JsonResponse
    {
        $result = $this->sahiEcografiasEnviarService->estadoPorEcografiaProducto($ecografia_producto_id);

        return response()->json($result, $result['status']);
    }

    /**
     * Encola el envío de un producto de ecografía al sistema SAHI.
     *
     * @param  EnviarEcografiaRequest  $request  Solicitud validada con el id del producto de ecografía.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-29
     */
    public function enviarPorEcografiaProducto(EnviarEcografiaRequest $request): JsonResponse
    {
        $result = $this->sahiEcografiasEnviarService->enviarPorEcografiaProducto($request->validated('ecografia_producto_id'));

        return response()->json($result, $result['status']);
    }
}
