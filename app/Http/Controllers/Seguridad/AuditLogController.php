<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Services\Seguridad\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Construye el controlador e inicializa el servicio de auditoría y los permisos requeridos.
     *
     * @param  AuditLogService  $auditLogService  Servicio encargado de la lógica de registros de auditoría.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-30
     */
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:seguridad.auditoria.index,web');
    }

    /**
     * Lista los registros de auditoría aplicando los filtros recibidos en la petición.
     *
     * @param  Request  $request  Petición HTTP con los filtros de evento, usuario, búsqueda, rango de fechas y paginación.
     *
     * @returns JsonResponse Respuesta JSON con la lista paginada de registros de auditoría.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-30
     */
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

    /**
     * Obtiene el catálogo de eventos de auditoría disponibles.
     *
     * @returns JsonResponse Respuesta JSON con la lista de eventos distintos registrados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-30
     */
    public function eventos(): JsonResponse
    {
        $result = $this->auditLogService->eventos();

        return response()->json($result, $result['status']);
    }
}
