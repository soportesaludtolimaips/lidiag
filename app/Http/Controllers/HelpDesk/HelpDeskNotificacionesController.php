<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\HelpDesk\HelpDeskNotificacionIndexRequest;
use App\Services\HelpDesk\HelpDeskNotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpDeskNotificacionesController extends Controller
{
    /**
     * Construye el controlador inyectando el servicio de notificaciones del help desk.
     *
     * @param  HelpDeskNotificacionService  $notificacionService  servicio encargado de la lógica de notificaciones.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function __construct(
        private readonly HelpDeskNotificacionService $notificacionService,
    ) {}

    /**
     * Lista las notificaciones del usuario autenticado con paginación y filtro de no leídas.
     *
     * @param  HelpDeskNotificacionIndexRequest  $request  petición validada con limit, page y unread_only.
     *
     * @returns JsonResponse respuesta JSON con las notificaciones y el conteo de no leídas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function index(HelpDeskNotificacionIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $limit = (int) ($validated['limit'] ?? 20);
        $page = (int) ($validated['page'] ?? 1);
        $unreadOnly = (bool) ($validated['unread_only'] ?? false);
        $result = $this->notificacionService->list($request->user(), $limit, $page, $unreadOnly);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Marca como leída una notificación específica del usuario autenticado.
     *
     * @param  string  $id  identificador de la notificación a marcar como leída.
     * @param  Request  $request  petición HTTP del usuario autenticado.
     *
     * @returns JsonResponse respuesta JSON con el resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function markRead(string $id, Request $request): JsonResponse
    {
        $result = $this->notificacionService->markRead($request->user(), $id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Marca como leídas todas las notificaciones pendientes del usuario autenticado.
     *
     * @param  Request  $request  petición HTTP del usuario autenticado.
     *
     * @returns JsonResponse respuesta JSON con el resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $result = $this->notificacionService->markAllRead($request->user());

        return response()->json($result, (int) $result["status"]);
    }
}
