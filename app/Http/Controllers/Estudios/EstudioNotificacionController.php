<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\ListarEstudiosNotificarRequest;
use App\Services\Estudios\EstudioNotificacionService;
use Illuminate\Http\JsonResponse;

class EstudioNotificacionController extends Controller
{
    /**
     * Constructor del controlador de notificaciones de estudio
     *
     * @param  EstudioNotificacionService  $estudioNotificacionService  Servicio de notificaciones
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function __construct(
        private readonly EstudioNotificacionService $estudioNotificacionService,
    ) {
        ini_set('max_execution_time', -1);
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:estudios.notificar.index')->only(['store', 'update', 'destroy']);
    }

    /**
     * Lista los estudios para notificación con filtros de búsqueda
     *
     * @param  ListarEstudiosNotificarRequest  $request  Solicitud con filtros de búsqueda
     *
     * @returns JsonResponse Respuesta con la lista de estudios para notificar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarEstudios(ListarEstudiosNotificarRequest $request): JsonResponse
    {
        $result = $this->estudioNotificacionService->listarEstudios($request->validated());

        return response()->json($result, $result['status']);
    }
}
