<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\HelpDesk\HelpDeskPrioridadRequest;
use App\Models\HelpDesk\HelpDeskPrioridad;
use App\Services\HelpDesk\HelpDeskPrioridadService;
use Illuminate\Http\JsonResponse;

class HelpDeskPrioridadesController extends Controller
{
    /**
     * Construye el controlador inyectando el servicio de prioridades.
     *
     * @param  HelpDeskPrioridadService  $prioridadService  Servicio encargado de la lógica de prioridades.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function __construct(
        private readonly HelpDeskPrioridadService $prioridadService,
    ) {}

    /**
     * Lista todas las prioridades de help desk ordenadas por orden y nombre.
     *
     * @returns JsonResponse Respuesta JSON con la lista de prioridades.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function index(): JsonResponse
    {
        $result = $this->prioridadService->all();

        return response()->json($result, $result["status"]);
    }

    /**
     * Crea una nueva prioridad de help desk validando los datos de entrada.
     *
     * @param  HelpDeskPrioridadRequest  $request  Petición validada con nombre, orden y estado.
     *
     * @returns JsonResponse Respuesta JSON con la prioridad creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function store(HelpDeskPrioridadRequest $request): JsonResponse
    {
        $result = $this->prioridadService->create($request->validated());

        return response()->json($result, $result["status"]);
    }

    /**
     * Muestra el detalle de una prioridad específica.
     *
     * @param  HelpDeskPrioridad  $prioridad  Prioridad a consultar (inyectada por route model binding).
     *
     * @returns JsonResponse Respuesta JSON con la prioridad solicitada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function show(HelpDeskPrioridad $prioridad): JsonResponse
    {
        $result = $this->prioridadService->find($prioridad->id);

        return response()->json($result, $result["status"]);
    }

    /**
     * Actualiza los datos de una prioridad existente.
     *
     * @param  HelpDeskPrioridadRequest  $request  Petición validada con los campos a actualizar.
     * @param  HelpDeskPrioridad  $prioridad  Prioridad a actualizar (inyectada por route model binding).
     *
     * @returns JsonResponse Respuesta JSON con la prioridad actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function update(HelpDeskPrioridadRequest $request, HelpDeskPrioridad $prioridad): JsonResponse
    {
        $result = $this->prioridadService->update($prioridad->id, $request->validated());

        return response()->json($result, $result["status"]);
    }

    /**
     * Desactiva (elimina lógicamente) una prioridad de help desk.
     *
     * @param  HelpDeskPrioridad  $prioridad  Prioridad a desactivar (inyectada por route model binding).
     *
     * @returns JsonResponse Respuesta JSON con resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function destroy(HelpDeskPrioridad $prioridad): JsonResponse
    {
        $result = $this->prioridadService->delete($prioridad->id);

        return response()->json(['ok' => true], $result['status']);
    }
}
