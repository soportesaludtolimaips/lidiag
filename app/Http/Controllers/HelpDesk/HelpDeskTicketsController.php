<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\HelpDesk\HelpDeskChangeStatusRequest;
use App\Http\Requests\HelpDesk\HelpDeskCommentRequest;
use App\Http\Requests\HelpDesk\HelpDeskReassignRequest;
use App\Http\Requests\HelpDesk\HelpDeskTicketIndexRequest;
use App\Http\Requests\HelpDesk\HelpDeskTicketStoreRequest;
use App\Http\Requests\HelpDesk\HelpDeskTicketUpdateRequest;
use App\Models\HelpDesk\HelpDeskTickets;
use App\Services\HelpDesk\HelpDeskTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpDeskTicketsController extends Controller
{
    /**
     * Construye el controlador inyectando el servicio de gestión de tickets.
     *
     * @param  HelpDeskTicketService  $ticketService  Servicio encargado de la lógica de tickets.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function __construct(
        private readonly HelpDeskTicketService $ticketService,
    ) {}

    /**
     * Lista los tickets de help desk aplicando filtros y paginación según el alcance solicitado.
     *
     * @param  HelpDeskTicketIndexRequest  $request  Petición validada con los filtros de consulta.
     *
     * @returns JsonResponse Respuesta JSON con la lista paginada de tickets.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function index(HelpDeskTicketIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $filters = [
            'scope' => $validated['scope'] ?? 'mine',
            'estado_id' => $validated['estado_id'] ?? null,
            'categoria_id' => $validated['categoria_id'] ?? null,
            'prioridad_id' => $validated['prioridad_id'] ?? null,
            'sede_id' => $validated['sede_id'] ?? null,
            'desde' => $validated['desde'] ?? ($validated['fechaIni'] ?? null),
            'hasta' => $validated['hasta'] ?? ($validated['fechaFin'] ?? null),
            'q' => $validated['q'] ?? null,
            'per_page' => (int) ($validated['per_page'] ?? 50),
            'page' => (int) ($validated['page'] ?? 1),
        ];

        $result = $this->ticketService->list($filters, $request->user());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Crea un nuevo ticket de help desk validando los datos de entrada.
     *
     * @param  HelpDeskTicketStoreRequest  $request  Petición validada con los datos del ticket a crear.
     *
     * @returns JsonResponse Respuesta JSON con el ticket creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function store(HelpDeskTicketStoreRequest $request): JsonResponse
    {
        $result = $this->ticketService->create($request->validated(), $request->user());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Muestra el detalle de un ticket específico con sus relaciones.
     *
     * @param  HelpDeskTickets  $ticket  Ticket a consultar (inyectado por route model binding).
     * @param  Request  $request  Petición HTTP del usuario autenticado.
     *
     * @returns JsonResponse Respuesta JSON con el detalle del ticket.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function show(HelpDeskTickets $ticket, Request $request): JsonResponse
    {
        $result = $this->ticketService->find($ticket, $request->user());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Actualiza los datos de un ticket existente (título, descripción, categoría o prioridad).
     *
     * @param  HelpDeskTicketUpdateRequest  $request  Petición validada con los campos a actualizar.
     * @param  HelpDeskTickets  $ticket  Ticket a actualizar (inyectado por route model binding).
     *
     * @returns JsonResponse Respuesta JSON con el ticket actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function update(HelpDeskTicketUpdateRequest $request, HelpDeskTickets $ticket): JsonResponse
    {
        $result = $this->ticketService->update($ticket, $request->validated(), $request->user());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Elimina un ticket de help desk (solo permitido en estado Abierto o Cerrado y para administradores).
     *
     * @param  HelpDeskTickets  $ticket  Ticket a eliminar (inyectado por route model binding).
     * @param  Request  $request  Petición HTTP del usuario autenticado.
     *
     * @returns JsonResponse Respuesta JSON con resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function destroy(HelpDeskTickets $ticket, Request $request): JsonResponse
    {
        $result = $this->ticketService->delete($ticket, $request->user());

        return response()->json(['ok' => true], (int) $result['status']);
    }

    /**
     * Agrega un comentario a un ticket y notifica a las partes involucradas.
     *
     * @param  HelpDeskTickets  $ticket  Ticket al que se comenta (inyectado por route model binding).
     * @param  HelpDeskCommentRequest  $request  Petición validada con el comentario.
     *
     * @returns JsonResponse Respuesta JSON con resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function comment(HelpDeskTickets $ticket, HelpDeskCommentRequest $request): JsonResponse
    {
        $result = $this->ticketService->comment($ticket, $request->validated(), $request->user());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Reasigna un ticket a otro usuario de soporte registrando el cambio en el historial.
     *
     * @param  HelpDeskTickets  $ticket  Ticket a reasignar (inyectado por route model binding).
     * @param  HelpDeskReassignRequest  $request  Petición validada con el nuevo responsable y comentario.
     *
     * @returns JsonResponse Respuesta JSON con resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function reassign(HelpDeskTickets $ticket, HelpDeskReassignRequest $request): JsonResponse
    {
        $result = $this->ticketService->reassign($ticket, $request->validated(), $request->user());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Cambia el estado de un ticket validando la transición permitida y notificando a las partes.
     *
     * @param  HelpDeskTickets  $ticket  Ticket a cambiar de estado (inyectado por route model binding).
     * @param  HelpDeskChangeStatusRequest  $request  Petición validada con el nuevo estado y comentario.
     *
     * @returns JsonResponse Respuesta JSON con resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function changeStatus(HelpDeskTickets $ticket, HelpDeskChangeStatusRequest $request): JsonResponse
    {
        $result = $this->ticketService->changeStatus($ticket, $request->validated(), $request->user());

        return response()->json($result, (int) $result["status"]);
    }
}
