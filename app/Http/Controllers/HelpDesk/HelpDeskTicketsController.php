<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\HelpDesk\HelpDeskChangeStatusRequest;
use App\Http\Requests\HelpDesk\HelpDeskCommentRequest;
use App\Http\Requests\HelpDesk\HelpDeskReassignRequest;
use App\Http\Requests\HelpDesk\HelpDeskTicketStoreRequest;
use App\Http\Requests\HelpDesk\HelpDeskTicketUpdateRequest;
use App\Models\HelpDesk\HelpDeskTickets;
use App\Services\HelpDesk\HelpDeskTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpDeskTicketsController extends Controller
{
    public function __construct(
        private readonly HelpDeskTicketService $ticketService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'scope' => $request->query('scope', 'mine'),
            'estado_id' => $request->query('estado_id'),
            'categoria_id' => $request->query('categoria_id'),
            'prioridad_id' => $request->query('prioridad_id'),
            'sede_id' => $request->query('sede_id'),
            'desde' => $request->query('desde'),
            'hasta' => $request->query('hasta'),
            'q' => $request->query('q'),
        ];

        $result = $this->ticketService->list($filters, $request->user());

        return response()->json($result['data'], $result['status']);
    }

    public function store(HelpDeskTicketStoreRequest $request): JsonResponse
    {
        $result = $this->ticketService->create($request->validated(), $request->user());

        return response()->json($result['data'], $result['status']);
    }

    public function show(HelpDeskTickets $ticket, Request $request): JsonResponse
    {
        $result = $this->ticketService->find($ticket, $request->user());

        return response()->json($result['data'], $result['status']);
    }

    public function update(HelpDeskTicketUpdateRequest $request, HelpDeskTickets $ticket): JsonResponse
    {
        $result = $this->ticketService->update($ticket, $request->validated(), $request->user());

        return response()->json($result['data'], $result['status']);
    }

    public function destroy(HelpDeskTickets $ticket, Request $request): JsonResponse
    {
        $result = $this->ticketService->delete($ticket, $request->user());

        return response()->json(['ok' => true], $result['status']);
    }

    public function comment(HelpDeskTickets $ticket, HelpDeskCommentRequest $request): JsonResponse
    {
        $result = $this->ticketService->comment($ticket, $request->validated(), $request->user());

        return response()->json($result['data'], $result['status']);
    }

    public function reassign(HelpDeskTickets $ticket, HelpDeskReassignRequest $request): JsonResponse
    {
        $result = $this->ticketService->reassign($ticket, $request->validated(), $request->user());

        return response()->json($result['data'], $result['status']);
    }

    public function changeStatus(HelpDeskTickets $ticket, HelpDeskChangeStatusRequest $request): JsonResponse
    {
        $result = $this->ticketService->changeStatus($ticket, $request->validated(), $request->user());

        return response()->json($result['data'], $result['status']);
    }
}
