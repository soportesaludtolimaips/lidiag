<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Services\Seguridad\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:seguridad.auditoria.index,web');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'evento' => $request->get('evento'),
            'user_id' => $request->get('user_id'),
            'search' => $request->get('search'),
            'desde' => $request->get('desde'),
            'hasta' => $request->get('hasta'),
            'per_page' => $request->get('per_page', 50),
        ];

        $result = $this->auditLogService->all($filters);

        return response()->json($result, $result['status']);
    }

    public function eventos(): JsonResponse
    {
        $result = $this->auditLogService->eventos();

        return response()->json($result, $result['status']);
    }
}
