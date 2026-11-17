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
     * Lista los productos de atención de ecografías en SAHI por número de atención,
     * documento de paciente, nombres o rango de fechas.
     *
     * Flujo del proceso:
     * 1. Extrae los parámetros de búsqueda enviados en la solicitud (atencion_id, bus_nom_num_docu, bus_fech_ini, bus_fecha_fin).
     * 2. Invoca al servicio SAHIEcografiasService delegando la resolución y enriquecimiento de atenciones.
     * 3. Retorna la respuesta en formato JSON con el código de estado devuelto por el servicio.
     *
     * @param  Request  $request  Solicitud HTTP con los filtros de búsqueda.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listarProductosAtencion(Request $request): JsonResponse
    {
        $criterio = $request->input('atencion_id')
            ?? $request->input('bus_nom_num_docu')
            ?? $request->input('idAtencion');

        $result = $this->sahiEcografiasService->listarProductosAtencion(
            $criterio,
            $request->input('bus_fech_ini'),
            $request->input('bus_fecha_fin')
        );

        return response()->json($result, $result['status']);
    }
}
