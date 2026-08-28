<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigRecomendacionProductoRequest;
use App\Http\Requests\Configuracion\UpdateConfigRecomendacionProductoRequest;
use App\Services\Configuracion\ConfigRecomendacionProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigRecomendacionProductoController extends Controller
{
    /**
     * Constructor del controlador de recomendaciones de producto.
     *
     * Inicializa el servicio y configura los middlewares de autenticación y permisos.
     *
     * @param  ConfigRecomendacionProductoService  $configRecomendacionProductoService  Servicio de recomendaciones inyectado.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function __construct(
        private readonly ConfigRecomendacionProductoService $configRecomendacionProductoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
        $this->middleware('permission:config.recomendaciones.index')->only(['index']);
        $this->middleware('permission:config.recomendaciones.create')->only(['store']);
        $this->middleware('permission:config.recomendaciones.edit')->only(['update']);
        $this->middleware('permission:config.recomendaciones.destroy')->only(['destroy']);
    }

    /**
     * Lista las recomendaciones de producto, filtrando opcionalmente por producto y estado.
     *
     * @param  Illuminate\Http\Request  $request  Solicitud con los filtros opcionales producto_id y estado.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con la lista de recomendaciones.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->configRecomendacionProductoService->all(
            $request->integer('producto_id'),
            $request->boolean('estado'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra una recomendación de producto específica según su identificador.
     *
     * @param  int  $id  Identificador único de la recomendación.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con los datos de la recomendación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->configRecomendacionProductoService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Crea o actualiza una recomendación de producto con los datos validados.
     *
     * @param  StoreConfigRecomendacionProductoRequest  $request  Solicitud con los datos validados de la recomendación.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con la recomendación creada o actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function store(StoreConfigRecomendacionProductoRequest $request): JsonResponse
    {
        $result = $this->configRecomendacionProductoService->createOrUpdate($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza una recomendación de producto existente con los datos validados.
     *
     * @param  UpdateConfigRecomendacionProductoRequest  $request  Solicitud con los datos validados.
     * @param  int  $id  Identificador único de la recomendación a actualizar.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con la recomendación actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function update(UpdateConfigRecomendacionProductoRequest $request, int $id): JsonResponse
    {
        $result = $this->configRecomendacionProductoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina una recomendación de producto existente según su identificador.
     *
     * @param  int  $id  Identificador único de la recomendación a eliminar.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configRecomendacionProductoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
