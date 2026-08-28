<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigTipoDocumentoRequest;
use App\Http\Requests\Configuracion\UpdateConfigTipoDocumentoRequest;
use App\Services\Configuracion\ConfigTipoDocumentoService;
use Illuminate\Http\JsonResponse;

class ConfigTipoDocumentoController extends Controller
{
    public function __construct(
        private readonly ConfigTipoDocumentoService $configTipoDocumentoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'listarActivos']);
        $this->middleware('permission:config.tipo.documento.create')->only(['store']);
        $this->middleware('permission:config.tipo.documento.edit')->only(['update']);
        $this->middleware('permission:config.tipo.documento.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los tipos de documento.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function index(): JsonResponse
    {
        $result = $this->configTipoDocumentoService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra los detalles de un tipo de documento.
     *
     * @param  int  $id  Identificador del tipo de documento.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->configTipoDocumentoService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los tipos de documento activos.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function listarActivos(): JsonResponse
    {
        $result = $this->configTipoDocumentoService->listarActivos();

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo tipo de documento.
     *
     * @param  StoreConfigTipoDocumentoRequest  $request  Solicitud validada con los datos.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function store(StoreConfigTipoDocumentoRequest $request): JsonResponse
    {
        $result = $this->configTipoDocumentoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza un tipo de documento existente.
     *
     * @param  UpdateConfigTipoDocumentoRequest  $request  Solicitud validada con los datos.
     * @param  int  $id  Identificador del tipo de documento.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function update(UpdateConfigTipoDocumentoRequest $request, int $id): JsonResponse
    {
        $result = $this->configTipoDocumentoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un tipo de documento existente.
     *
     * @param  int  $id  Identificador del tipo de documento.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configTipoDocumentoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
