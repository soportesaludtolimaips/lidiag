<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\ListarEstudiosNotificarRequest;
use App\Services\Estudios\EstudioNotificacionService;
use Illuminate\Http\JsonResponse;

class EstudioNotificacionController extends Controller
{
    public function __construct(
        private readonly EstudioNotificacionService $estudioNotificacionService,
    ) {
        ini_set('max_execution_time', -1);
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:estudios.notificar.index');
    }

    public function listarEstudios(ListarEstudiosNotificarRequest $request): JsonResponse
    {
        $result = $this->estudioNotificacionService->listarEstudios($request->validated());

        return response()->json($result, $result['status']);
    }
}
