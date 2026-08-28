<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaAnularRequest;
use App\Http\Requests\Ecografias\EcografiaGenerarReporteRequest;
use App\Http\Requests\Ecografias\EcografiaStoreRequest;
use App\Http\Requests\Ecografias\EcografiaUpdateRequest;
use App\Services\Ecografias\EcografiaService;
use App\Services\Ecografias\PlantillaEcografiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcografiaController extends Controller
{
    public function __construct(
        private readonly EcografiaService $ecografiaService,
        private readonly PlantillaEcografiaService $plantillaEcografiaService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:ecografias.index,web')->only(['index', 'show', 'filtarEcografias']);
        $this->middleware('permission:ecografias.admision,web')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:ecografias.anular,web')->only(['anularEcografia']);
        $this->middleware('permission:ecografias.reporte,web')->only(['generarReporte']);
    }

    /**
     * Lista todas las ecografías aplicando los filtros de búsqueda recibidos.
     *
     * @param  Request  $request  Solicitud HTTP con los filtros de búsqueda.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->ecografiaService->all([
            'bus_nom_num_docu' => $request->input('bus_nom_num_docu'),
            'bus_fech_ini' => $request->input('bus_fech_ini'),
            'bus_fecha_fin' => $request->input('bus_fecha_fin'),
        ]);

        return response()->json($result, $result['status']);
    }

    /**
     * Filtra las ecografías según los criterios de búsqueda recibidos.
     *
     * @param  Request  $request  Solicitud HTTP con los filtros de búsqueda.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function filtarEcografias(Request $request): JsonResponse
    {
        $result = $this->ecografiaService->filtarEcografias([
            'bus_nom_num_docu' => $request->input('bus_nom_num_docu'),
            'bus_fech_ini' => $request->input('bus_fech_ini'),
            'bus_fecha_fin' => $request->input('bus_fecha_fin'),
        ]);

        return response()->json($result, $result['status']);
    }

    /**
     * Almacena una nueva ecografía validando los datos de entrada.
     *
     * @param  EcografiaStoreRequest  $request  Solicitud validada con los datos de la ecografía.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function store(EcografiaStoreRequest $request): JsonResponse
    {
        $result = $this->ecografiaService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra los detalles de una ecografía específica.
     *
     * @param  int  $id  Identificador de la ecografía.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->ecografiaService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza los datos de una ecografía específica.
     *
     * @param  EcografiaUpdateRequest  $request  Solicitud validada con los datos a actualizar.
     * @param  int  $id  Identificador de la ecografía.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function update(EcografiaUpdateRequest $request, int $id): JsonResponse
    {
        $result = $this->ecografiaService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina una ecografía específica.
     *
     * @param  int  $id  Identificador de la ecografía a eliminar.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->ecografiaService->delete($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Anula una ecografía registrando el motivo de anulación.
     *
     * @param  EcografiaAnularRequest  $request  Solicitud validada con el motivo de anulación.
     * @param  int  $id  Identificador de la ecografía a anular.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function anularEcografia(EcografiaAnularRequest $request, int $id): JsonResponse
    {
        $result = $this->ecografiaService->anular($id, $request->validated('motivo_anula'));

        return response()->json($result, $result['status']);
    }

    /**
     * Genera el reporte Word de una ecografía.
     *
     * @param  EcografiaGenerarReporteRequest  $request  Solicitud validada con los parámetros del reporte.
     * @param  int  $ecografia_id  Identificador de la ecografía.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function generarReporte(EcografiaGenerarReporteRequest $request, int $ecografia_id): JsonResponse
    {
        $data = $request->validated();
        $data['ecografia_id'] = $ecografia_id;

        $result = $this->plantillaEcografiaService->editWord($data);

        return response()->json($result);
    }
}
