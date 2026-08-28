<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigDiagnosticoRequest;
use App\Services\Configuracion\ConfigDiagnosticoService;
use Illuminate\Http\JsonResponse;

class ConfigDiagnosticoController extends Controller
{
    public function __construct(
        private readonly ConfigDiagnosticoService $configDiagnosticoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
        $this->middleware('permission:config.diagnosticos.index')->only(['index']);
        $this->middleware('permission:config.diagnosticos.create')->only(['store']);
        $this->middleware('permission:config.diagnosticos.edit')->only(['update']);
        $this->middleware('permission:config.diagnosticos.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los diagnósticos registrados en el sistema.
     *
     * @returns JsonResponse Respuesta JSON con la lista de diagnósticos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function index(): JsonResponse
    {
        $result = $this->configDiagnosticoService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo diagnóstico con los datos validados recibidos.
     *
     * @param  ConfigDiagnosticoRequest  $request  Datos validados del diagnóstico a crear.
     *
     * @returns JsonResponse Respuesta JSON con el diagnóstico creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function store(ConfigDiagnosticoRequest $request): JsonResponse
    {
        $result = $this->configDiagnosticoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza un diagnóstico existente identificado por su id.
     *
     * @param  ConfigDiagnosticoRequest  $request  Datos validados del diagnóstico a actualizar.
     * @param  int  $id  Identificador del diagnóstico a actualizar.
     *
     * @returns JsonResponse Respuesta JSON con el diagnóstico actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function update(ConfigDiagnosticoRequest $request, int $id): JsonResponse
    {
        $result = $this->configDiagnosticoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un diagnóstico existente identificado por su id.
     *
     * @param  int  $id  Identificador del diagnóstico a eliminar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configDiagnosticoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
