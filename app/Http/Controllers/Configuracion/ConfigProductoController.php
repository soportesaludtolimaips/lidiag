<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigProductoRequest;
use App\Services\Configuracion\ConfigProductoService;
use Illuminate\Http\JsonResponse;

class ConfigProductoController extends Controller
{
    /**
     * Inyecta el servicio de productos y configura los middlewares de permisos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly ConfigProductoService $configProductoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
        $this->middleware('permission:config.productos.index')->only(['index']);
        $this->middleware('permission:config.productos.create')->only(['store']);
        $this->middleware('permission:config.productos.edit')->only(['update']);
        $this->middleware('permission:config.productos.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los productos de configuración ordenados por nombre.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function index(): JsonResponse
    {
        $result = $this->configProductoService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra los detalles de un producto de configuración.
     *
     * @param  int  $id  Identificador del producto.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->configProductoService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo producto de configuración.
     *
     * @param  ConfigProductoRequest  $request  Solicitud validada con los datos.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function store(ConfigProductoRequest $request): JsonResponse
    {
        $result = $this->configProductoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza un producto de configuración existente.
     *
     * @param  ConfigProductoRequest  $request  Solicitud validada con los datos.
     * @param  int  $id  Identificador del producto.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function update(ConfigProductoRequest $request, int $id): JsonResponse
    {
        $result = $this->configProductoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un producto de configuración existente.
     *
     * @param  int  $id  Identificador del producto.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configProductoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
