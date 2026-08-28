<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigSedeRequest;
use App\Http\Requests\Configuracion\UpdateConfigSedeRequest;
use App\Services\Configuracion\ConfigSedeService;
use Illuminate\Http\JsonResponse;

class ConfigSedeController extends Controller
{
    /**
     * Constructor del controlador de sedes.
     *
     * Inicializa el servicio y configura los middlewares de autenticación y permisos.
     *
     * @param  ConfigSedeService  $configSedeService  Servicio de gestión de sedes inyectado.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function __construct(
        private readonly ConfigSedeService $configSedeService,
    ) {
        $this->middleware('auth:sanctum')->except(['index']);
        $this->middleware('permission:config.sedes.index')->only(['index']);
        $this->middleware('permission:config.sedes.create')->only(['store']);
        $this->middleware('permission:config.sedes.edit')->only(['update']);
        $this->middleware('permission:config.sedes.destroy')->only(['destroy']);
    }

    /**
     * Lista todas las sedes registradas con sus servicios y números de WhatsApp.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con la lista de sedes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function index(): JsonResponse
    {
        $result = $this->configSedeService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Busca una sede específica por su identificador.
     *
     * @param  mixed  $id  Identificador de la sede a buscar.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con los datos de la sede.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function buscarPorId($id): JsonResponse
    {
        $result = $this->configSedeService->buscarPorId((int) $id);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista únicamente las sedes que se encuentran activas.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con la lista de sedes activas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarSedesActivas(): JsonResponse
    {
        $result = $this->configSedeService->listarActivas();

        return response()->json($result, $result['status']);
    }

    /**
     * Crea una nueva sede con los datos y plantillas validados de la solicitud.
     *
     * @param  StoreConfigSedeRequest  $request  Solicitud con los datos y archivos validados de la sede.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con la sede creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function store(StoreConfigSedeRequest $request): JsonResponse
    {
        $result = $this->configSedeService->create(
            $request->decoded(),
            $request->file('plantilla_rx'),
            $request->file('plantilla_rx_ecografia'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza una sede existente con los datos y plantillas validados de la solicitud.
     *
     * @param  UpdateConfigSedeRequest  $request  Solicitud con los datos y archivos validados de la sede.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con la sede actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function update(UpdateConfigSedeRequest $request): JsonResponse
    {
        $result = $this->configSedeService->update(
            $request->decoded(),
            $request->file('plantilla_rx'),
            $request->file('plantilla_rx_ecografia'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina una sede existente según su identificador.
     *
     * @param  int  $id  Identificador único de la sede a eliminar.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configSedeService->delete($id);

        return response()->json($result, $result['status']);
    }
}
