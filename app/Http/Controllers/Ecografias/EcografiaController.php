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
use Illuminate\Validation\ValidationException;

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
     * Filtra las ecografías según los criterios proporcionados en la solicitud.
     *
     * @param  Request  $request  - La solicitud HTTP que contiene los criterios de filtrado.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
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
     * Almacena una nueva ecografía en la base de datos.
     *
     * @param  EcografiaStoreRequest  $request  - La solicitud HTTP que contiene los datos para crear la ecografía.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @throws ValidationException - Si los datos proporcionados en la solicitud no cumplen con las reglas de validación definidas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function store(EcografiaStoreRequest $request): JsonResponse
    {
        $result = $this->ecografiaService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra los detalles de una ecografía específica.
     *
     * @param  int  $id  - El ID de la ecografía a mostrar.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->ecografiaService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza los datos de una ecografía específica.
     *
     * @param  Request  $request  - La solicitud HTTP que contiene los datos para actualizar la ecografía.
     * @param  int  $id  - El ID de la ecografía a actualizar.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function update(EcografiaUpdateRequest $request, int $id): JsonResponse
    {
        $result = $this->ecografiaService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina una ecografía específica.
     *
     * @param  int  $id  - El ID de la ecografía a eliminar.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->ecografiaService->delete($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Anula una ecografía específica.
     *
     * @param  int  $id  - El ID de la ecografía a anular.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function anularEcografia(EcografiaAnularRequest $request, int $id): JsonResponse
    {
        $result = $this->ecografiaService->anular($id, $request->validated('motivo_anula'));

        return response()->json($result, $result['status']);
    }

    /**
     * Genera un reporte de ecografía en formato Word utilizando los datos proporcionados en la solicitud.
     *
     * @param  Request  $request  - La solicitud HTTP que contiene los datos necesarios para generar el reporte.
     * @param  int  $ecografia_id  - El ID de la ecografía para la cual se generará el reporte.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @throws ValidationException - Si los datos proporcionados en la solicitud no cumplen con las reglas de validación definidas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function generarReporte(EcografiaGenerarReporteRequest $request, int $ecografia_id): JsonResponse
    {
        $data = $request->validated();
        $data['ecografia_id'] = $ecografia_id;

        $result = $this->plantillaEcografiaService->editWord($data);

        return response()->json($result);
    }
}
