<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigPrioridadRequest;
use App\Services\Configuracion\ConfigPrioridadService;
use Illuminate\Http\JsonResponse;

class ConfigPrioridadController extends Controller
{
    /**
     * Constructor del controlador de prioridades.
     *
     * Inicializa el servicio y configura los middlewares de autenticación y permisos.
     *
     * @param  ConfigPrioridadService  $configPrioridadService  Servicio de gestión de prioridades inyectado.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function __construct(
        private readonly ConfigPrioridadService $configPrioridadService,
    ) {
        $this->middleware("auth:sanctum")->except(["index"]);
        // Catálogo público/lectura: index queda abierto a usuarios autenticados para asignación de estudios
        // $this->middleware("permission:config.prioridades.index")->only(["index"]);
        $this->middleware("permission:config.prioridades.create")->only(["store"]);
        $this->middleware("permission:config.prioridades.edit")->only(["update"]);
        $this->middleware("permission:config.prioridades.destroy")->only(["destroy"]);
    }

    /**
     * Lista todas las prioridades registradas en el sistema.
     *
     * @returns JsonResponse Respuesta JSON con la lista de prioridades.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function index(): JsonResponse
    {
        $result = $this->configPrioridadService->all();

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Muestra una prioridad específica según su identificador.
     *
     * @param  int  $id  Identificador único de la prioridad.
     *
     * @returns JsonResponse Respuesta JSON con los datos de la prioridad.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->configPrioridadService->find($id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Crea una nueva prioridad con los datos validados de la solicitud.
     *
     * @param  ConfigPrioridadRequest  $request  Solicitud con los datos validados de la prioridad.
     *
     * @returns JsonResponse Respuesta JSON con la prioridad creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function store(ConfigPrioridadRequest $request): JsonResponse
    {
        $result = $this->configPrioridadService->create($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Actualiza una prioridad existente con los datos validados.
     *
     * @param  ConfigPrioridadRequest  $request  Solicitud validada con los datos a actualizar.
     * @param  int  $id  Identificador único de la prioridad.
     *
     * @returns JsonResponse Respuesta JSON con la prioridad actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function update(ConfigPrioridadRequest $request, int $id): JsonResponse
    {
        $result = $this->configPrioridadService->update($id, $request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Elimina una prioridad existente según su identificador.
     *
     * @param  int  $id  Identificador único de la prioridad a eliminar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configPrioridadService->delete($id);

        return response()->json($result, (int) $result["status"]);
    }
}
