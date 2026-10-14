<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\AuditLogIndexRequest;
use App\Services\Seguridad\AuditLogService;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * Inyecta el servicio de auditoría y configura los middlewares de acceso.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
        $this->middleware("auth");
        $this->middleware("permission:seguridad.auditoria.index,web");
    }

    /**
     * Lista los eventos de auditoría paginados según los filtros recibidos.
     *
     * @param  AuditLogIndexRequest  $request  Solicitud validada con los filtros evento, user_id, search, desde, hasta y per_page.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $filters = [
            "evento" => $validated["evento"] ?? null,
            "user_id" => $validated["user_id"] ?? null,
            "search" => $validated["search"] ?? null,
            "desde" => $validated["desde"] ?? null,
            "hasta" => $validated["hasta"] ?? null,
            "per_page" => (int) ($validated["per_page"] ?? 50),
        ];

        $result = $this->auditLogService->all($filters);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Devuelve el catálogo de tipos de evento de auditoría disponibles.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function eventos(): JsonResponse
    {
        $result = $this->auditLogService->eventos();

        return response()->json($result, (int) $result["status"]);
    }
}
