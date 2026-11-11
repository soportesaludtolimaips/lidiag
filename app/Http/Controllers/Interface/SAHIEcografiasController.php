<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\SAHIEcografiasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SAHIEcografiasController extends Controller
{
    /**
     * Inyecta el servicio de ecografías SAHI.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly SAHIEcografiasService $sahiEcografiasService,
    ) {}

    /**
     * Lista los productos de atención de ecografías en SAHI por nombre o documento.
     *
     * @param  Request  $request  Solicitud HTTP con el parámetro bus_nom_num_docu.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listarProductosAtencion(Request $request): JsonResponse
    {
        $result = $this->sahiEcografiasService->listarProductosAtencion($request->input('bus_nom_num_docu'));

        return response()->json($result, $result['status']);
    }
}
