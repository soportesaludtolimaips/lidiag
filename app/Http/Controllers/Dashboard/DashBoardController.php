<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashBoardController extends Controller
{
    /**
     * Inyecta el servicio del tablero.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly DashBoardService $service,
    ) {}

    /**
     * Devuelve las métricas del tablero filtradas por rango de días, sede y médico.
     *
     * @param  Request  $request  Solicitud HTTP con los filtros days, sede_id y medico_id.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function dashboard(Request $request): JsonResponse
    {
        $filters = [
            'days' => (int) $request->query('days', 30),
            'sede_id' => $request->query('sede_id'),
            'medico_id' => $request->query('medico_id'),
        ];

        $result = $this->service->dashboard($filters);

        return response()->json($result, $result["status"]);
    }
}
