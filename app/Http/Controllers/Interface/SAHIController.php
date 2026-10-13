<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\SAHIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SAHIController extends Controller
{
    /**
     * Inyecta el servicio de integración con SAHI.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly SAHIService $sahiService,
    ) {}

    /**
     * Lista las atenciones de un paciente en SAHI por número de documento.
     *
     * @param  Request  $request  Solicitud HTTP con el parámetro numDocu (cuerpo o ruta).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listarAtenciones(Request $request): JsonResponse
    {
        $numDocu = $request->input('numDocu') ?? $request->route('numDocu');

        $result = $this->sahiService->listarAtenciones($numDocu);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los productos diagnósticos de una atención de SAHI.
     *
     * @param  Request  $request  Solicitud HTTP con el parámetro idAtencion (cuerpo o ruta).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listarProductosDiagnosticos(Request $request): JsonResponse
    {
        $idAtencion = $request->input('idAtencion') ?? $request->route('idAtencion');

        $result = $this->sahiService->listarProductosDiagnosticos($idAtencion ? (int) $idAtencion : null);

        return response()->json($result, $result['status']);
    }

    /**
     * Busca productos de atención en SAHI por nombre o número de documento.
     *
     * @param  Request  $request  Solicitud HTTP con el parámetro bus_nom_num_docu.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listarProductosAtencion(Request $request): JsonResponse
    {
        $result = $this->sahiService->listarProductosAtencion($request->input('bus_nom_num_docu'));

        return response()->json($result, $result['status']);
    }
}
