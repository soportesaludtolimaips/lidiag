<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigAdminSaludRequest;
use App\Services\Configuracion\ConfigAdminSaludService;
use Illuminate\Http\JsonResponse;

class ConfigAdminSaludController extends Controller
{
    /**
     * Inyecta el servicio de administradores de salud y configura los middlewares.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly ConfigAdminSaludService $configAdminSaludService,
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
        // Catálogo público/lectura: index queda abierto a usuarios autenticados
        // $this->middleware('permission:config.admin.salud.index')->only(['index']);
        $this->middleware('permission:config.admin.salud.create')->only(['store']);
        $this->middleware('permission:config.admin.salud.edit')->only(['update']);
        $this->middleware('permission:config.admin.salud.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los administradores de salud registrados en el sistema.
     *
     * @returns JsonResponse Respuesta JSON con la lista de administradores de salud.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function index(): JsonResponse
    {
        $result = $this->configAdminSaludService->all();

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Crea un nuevo administrador de salud con los datos validados recibidos.
     *
     * @param  ConfigAdminSaludRequest  $request  Datos validados del administrador de salud a crear.
     *
     * @returns JsonResponse Respuesta JSON con el administrador de salud creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function store(ConfigAdminSaludRequest $request): JsonResponse
    {
        $result = $this->configAdminSaludService->create($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Actualiza un administrador de salud existente identificado por su id.
     *
     * @param  ConfigAdminSaludRequest  $request  Datos validados del administrador de salud a actualizar.
     * @param  int  $id  Identificador del administrador de salud a actualizar.
     *
     * @returns JsonResponse Respuesta JSON con el administrador de salud actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function update(ConfigAdminSaludRequest $request, int $id): JsonResponse
    {
        $result = $this->configAdminSaludService->update($id, $request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Elimina un administrador de salud existente identificado por su id.
     *
     * @param  int  $id  Identificador del administrador de salud a eliminar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configAdminSaludService->delete($id);

        return response()->json($result, (int) $result["status"]);
    }
}
