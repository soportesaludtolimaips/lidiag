<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:seguridad.auditoria.index,web');
    }

    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name')
            ->orderBy('created_at', 'desc');

        if ($evento = $request->get('evento')) {
            $query->where('evento', $evento);
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('descripcion', 'like', "%{$search}%")
                    ->orWhere('model_type', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%");
            });
        }

        if ($desde = $request->get('desde')) {
            $query->whereDate('created_at', '>=', $desde);
        }

        if ($hasta = $request->get('hasta')) {
            $query->whereDate('created_at', '<=', $hasta);
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    public function eventos(): JsonResponse
    {
        $eventos = AuditLog::select('evento')
            ->distinct()
            ->orderBy('evento')
            ->pluck('evento');

        return response()->json($eventos);
    }
}
