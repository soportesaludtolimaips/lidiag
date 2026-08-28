<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigPlantillaRequest;
use App\Http\Requests\Configuracion\UpdateConfigPlantillaRequest;
use App\Services\Configuracion\ConfigPlantillaService;
use Illuminate\Http\JsonResponse;

class ConfigPlantillaController extends Controller
{
    public function __construct(
        private readonly ConfigPlantillaService $configPlantillaService,
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'listarActivos', 'listarActivosPorTipo']);
        $this->middleware('permission:config.plantillas.create')->only(['store']);
        $this->middleware('permission:config.plantillas.edit')->only(['update']);
        $this->middleware('permission:config.plantillas.delete')->only(['destroy']);
    }

    /**
     * Lista todas las plantillas registradas en el sistema.
     *
     * @returns JsonResponse Respuesta JSON con la lista de plantillas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function index(): JsonResponse
    {
        $result = $this->configPlantillaService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra el detalle de una plantilla identificada por su id.
     *
     * @param  int  $id  Identificador de la plantilla a consultar.
     *
     * @returns JsonResponse Respuesta JSON con los datos de la plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->configPlantillaService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista las plantillas activas registradas en el sistema.
     *
     * @returns JsonResponse Respuesta JSON con la lista de plantillas activas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function listarActivos(): JsonResponse
    {
        $result = $this->configPlantillaService->listarActivos();

        return response()->json($result, $result['status']);
    }

    /**
     * Lista las plantillas activas filtradas por tipo y médico.
     *
     * @param  int  $tipoId  Identificador del tipo de plantilla.
     * @param  int  $medicoId  Identificador del médico.
     *
     * @returns JsonResponse Respuesta JSON con la lista de plantillas activas filtradas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function listarActivosPorTipo(int $tipoId, int $medicoId): JsonResponse
    {
        $result = $this->configPlantillaService->listarActivosPorTipo($tipoId, $medicoId);

        return response()->json($result, $result['status']);
    }

    /**
     * Crea una nueva plantilla con los datos validados recibidos.
     *
     * @param  StoreConfigPlantillaRequest  $request  Datos validados de la plantilla a crear.
     *
     * @returns JsonResponse Respuesta JSON con la plantilla creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function store(StoreConfigPlantillaRequest $request): JsonResponse
    {
        $result = $this->configPlantillaService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza una plantilla existente identificada por su id.
     *
     * @param  UpdateConfigPlantillaRequest  $request  Datos validados de la plantilla a actualizar.
     * @param  int  $id  Identificador de la plantilla a actualizar.
     *
     * @returns JsonResponse Respuesta JSON con la plantilla actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function update(UpdateConfigPlantillaRequest $request, int $id): JsonResponse
    {
        $result = $this->configPlantillaService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina una plantilla existente identificada por su id.
     *
     * @param  int  $id  Identificador de la plantilla a eliminar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configPlantillaService->delete($id);

        return response()->json($result, $result['status']);
    }
}
