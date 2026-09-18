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

    public function estadoPorEcografiaProducto(int $ecografia_producto_id): JsonResponse
    {
        $result = $this->sahiEcografiasEnviarService->estadoPorEcografiaProducto($ecografia_producto_id);

        return response()->json($result, $result['status']);
    }

    public function enviarPorEcografiaProducto(EnviarEcografiaRequest $request): JsonResponse
    {
        $result = $this->sahiEcografiasEnviarService->enviarPorEcografiaProducto($request->validated('ecografia_producto_id'));

        return response()->json($result, $result['status']);
    }
}
