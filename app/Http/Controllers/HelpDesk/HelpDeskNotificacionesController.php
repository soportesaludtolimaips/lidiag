<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Services\HelpDesk\HelpDeskNotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpDeskNotificacionesController extends Controller
{
    public function __construct(
        private readonly HelpDeskNotificacionService $notificacionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) ($request->query('limit', 20));
        $result = $this->notificacionService->list($request->user(), $limit);

        return response()->json($result['data'], $result['status']);
    }

    public function markRead(string $id, Request $request): JsonResponse
    {
        $result = $this->notificacionService->markRead($request->user(), $id);

        return response()->json($result['data'], $result['status']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $result = $this->notificacionService->markAllRead($request->user());

        return response()->json($result['data'], $result['status']);
    }
}
