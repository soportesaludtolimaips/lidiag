<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\HelpDesk\HelpDeskEstado;
use App\Services\HelpDesk\HelpDeskEstadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpDeskEstadosController extends Controller
{
    /**
     * Construye el controlador inyectando el servicio de estados.
     *
     * @param  HelpDeskEstadoService  $estadoService  Servicio encargado de la lógica de estados.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function __construct(
        private readonly HelpDeskEstadoService $estadoService,
    ) {}

    /**
     * Lista todos los estados de help desk ordenados por orden y nombre.
     *
     * @returns JsonResponse Respuesta JSON con la lista de estados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function index(): JsonResponse
    {
        $result = $this->estadoService->all();

        return response()->json($result, $result["status"]);
    }

    /**
     * Intenta crear un estado (operación no permitida porque los estados son fijos).
     *
     * @param  Request  $request  Petición HTTP recibida.
     *
     * @returns JsonResponse Respuesta JSON con mensaje de error de operación no permitida.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function store(Request $request): JsonResponse
    {
        $result = $this->estadoService->create();

        return response()->json(['message' => $result['message']], $result['status']);
    }

    /**
     * Muestra el detalle de un estado específico.
     *
     * @param  HelpDeskEstado  $estado  Estado a consultar (inyectado por route model binding).
     *
     * @returns JsonResponse Respuesta JSON con el estado solicitado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function show(HelpDeskEstado $estado): JsonResponse
    {
        $result = $this->estadoService->find($estado->id);

        return response()->json($result, $result["status"]);
    }

    /**
     * Intenta actualizar un estado (operación no permitida porque los estados son fijos).
     *
     * @param  Request  $request  Petición HTTP recibida.
     * @param  HelpDeskEstado  $estado  Estado a editar (inyectado por route model binding).
     *
     * @returns JsonResponse Respuesta JSON con mensaje de error de operación no permitida.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function update(Request $request, HelpDeskEstado $estado): JsonResponse
    {
        $result = $this->estadoService->update($estado->id);

        return response()->json(['message' => $result['message']], $result['status']);
    }

    /**
     * Intenta eliminar un estado (operación no permitida porque los estados son fijos).
     *
     * @param  HelpDeskEstado  $estado  Estado a eliminar (inyectado por route model binding).
     *
     * @returns JsonResponse Respuesta JSON con mensaje de error de operación no permitida.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function destroy(HelpDeskEstado $estado): JsonResponse
    {
        $result = $this->estadoService->delete($estado->id);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
