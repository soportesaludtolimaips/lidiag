<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigServicioRequest;
use App\Http\Requests\Configuracion\UpdateConfigServicioRequest;
use App\Services\Configuracion\ConfigServicioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigServicioController extends Controller
{
    /**
     * Constructor del controlador de servicios de citas.
     *
     * Inicializa el servicio de servicios de citas.
     *
     * @param  ConfigServicioService  $configServicioService  Servicio de servicios de citas inyectado.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function __construct(
        private readonly ConfigServicioService $configServicioService,
    ) {}

    /**
     * Devuelve la lista de servicios de citas del usuario sin envoltorio de estado.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con el arreglo de servicios de citas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function misServicios(): JsonResponse
    {
        $result = $this->configServicioService->all();

        return response()->json($result['data'] ?? []);
    }

    /**
     * Lista los servicios de citas, filtrando opcionalmente por sede.
     *
     * @param  Illuminate\Http\Request  $request  Solicitud con el filtro opcional sede_id.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con la lista de servicios de citas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->configServicioService->all($request->integer('sede_id'));

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra un servicio de cita específico según su identificador.
     *
     * @param  int  $id  Identificador único del servicio de cita.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con los datos del servicio de cita.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->configServicioService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo servicio de cita con los datos validados de la solicitud.
     *
     * @param  StoreConfigServicioRequest  $request  Solicitud con los datos validados del servicio.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con el servicio de cita creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function store(StoreConfigServicioRequest $request): JsonResponse
    {
        $result = $this->configServicioService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza un servicio de cita existente con los datos validados.
     *
     * @param  UpdateConfigServicioRequest  $request  Solicitud con los datos validados.
     * @param  int  $id  Identificador único del servicio de cita a actualizar.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con el servicio de cita actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function update(UpdateConfigServicioRequest $request, int $id): JsonResponse
    {
        $result = $this->configServicioService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un servicio de cita existente según su identificador.
     *
     * @param  int  $id  Identificador único del servicio de cita a eliminar.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configServicioService->delete($id);

        return response()->json($result, $result['status']);
    }
}
