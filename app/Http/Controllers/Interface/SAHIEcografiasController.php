<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\SAHIEcografiasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SAHIEcografiasController extends Controller
{
    public function __construct(
        private readonly SAHIEcografiasService $sahiEcografiasService,
    ) {}

    /**
     * Lista los productos de atención desde SAHI para la admisión de ecografías.
     *
     * @param  Request  $request  Solicitud HTTP con el criterio de búsqueda.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function listarProductosAtencion(Request $request): JsonResponse
    {
        $result = $this->sahiEcografiasService->listarProductosAtencion($request->bus_nom_num_docu);

        return response()->json($result, $result['status']);
    }
}
