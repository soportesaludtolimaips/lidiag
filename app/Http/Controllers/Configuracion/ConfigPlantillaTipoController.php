<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigPlantillaTipoRequest;
use App\Http\Requests\Configuracion\UpdateConfigPlantillaTipoRequest;
use App\Services\Configuracion\ConfigPlantillaTipoService;
use Illuminate\Http\JsonResponse;

class ConfigPlantillaTipoController extends Controller
{
    /**
     * Inyecta el servicio de tipos de plantilla y configura los middlewares de permisos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly ConfigPlantillaTipoService $configPlantillaTipoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'listarActivos']);
        $this->middleware('permission:config.plantillas.tipos.create')->only(['store']);
        $this->middleware('permission:config.plantillas.tipos.edit')->only(['update']);
        $this->middleware('permission:config.plantillas.tipos.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los tipos de plantilla registrados en el sistema.
     *
     * @returns JsonResponse Respuesta JSON con la lista de tipos de plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function index(): JsonResponse
    {
        $result = $this->configPlantillaTipoService->all();

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Muestra el detalle de un tipo de plantilla identificado por su id.
     *
     * @param  int  $id  Identificador del tipo de plantilla a consultar.
     *
     * @returns JsonResponse Respuesta JSON con los datos del tipo de plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->configPlantillaTipoService->find($id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Lista los tipos de plantilla activos registrados en el sistema.
     *
     * @returns JsonResponse Respuesta JSON con la lista de tipos de plantilla activos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function listarActivos(): JsonResponse
    {
        $result = $this->configPlantillaTipoService->listarActivos();

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Crea un nuevo tipo de plantilla con los datos validados recibidos.
     *
     * @param  StoreConfigPlantillaTipoRequest  $request  Datos validados del tipo de plantilla a crear.
     *
     * @returns JsonResponse Respuesta JSON con el tipo de plantilla creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function store(StoreConfigPlantillaTipoRequest $request): JsonResponse
    {
        $result = $this->configPlantillaTipoService->create($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Actualiza un tipo de plantilla existente identificado por su id.
     *
     * @param  UpdateConfigPlantillaTipoRequest  $request  Datos validados del tipo de plantilla a actualizar.
     * @param  int  $id  Identificador del tipo de plantilla a actualizar.
     *
     * @returns JsonResponse Respuesta JSON con el tipo de plantilla actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function update(UpdateConfigPlantillaTipoRequest $request, int $id): JsonResponse
    {
        $result = $this->configPlantillaTipoService->update($id, $request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Elimina un tipo de plantilla existente identificado por su id.
     *
     * @param  int  $id  Identificador del tipo de plantilla a eliminar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-03-27
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configPlantillaTipoService->delete($id);

        return response()->json($result, (int) $result["status"]);
    }
}
