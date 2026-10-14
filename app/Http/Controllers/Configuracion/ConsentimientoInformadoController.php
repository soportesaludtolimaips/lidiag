<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConsentimientoInformadoRequest;
use App\Http\Requests\Configuracion\UpdateConsentimientoInformadoRequest;
use App\Services\Configuracion\ConsentimientoInformadoService;
use Illuminate\Http\JsonResponse;

class ConsentimientoInformadoController extends Controller
{
    /**
     * Constructor del controlador que inicializa el servicio y configura los permisos por acción.
     *
     * @param  ConsentimientoInformadoService  $consentimientoInformadoService  Servicio encargado de la lógica de consentimientos informados.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-16
     */
    public function __construct(
        private readonly ConsentimientoInformadoService $consentimientoInformadoService,
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'listarActivos']);
        $this->middleware('permission:config.consentimiento_informado.index')->only(['index', 'listarActivos', 'show']);
        $this->middleware('permission:config.consentimiento_informado.create')->only(['store']);
        $this->middleware('permission:config.consentimiento_informado.edit')->only(['update']);
        $this->middleware('permission:config.consentimiento_informado.destroy')->only(['destroy']);
    }

    /**
     * Obtiene la lista de consentimientos informados registrados.
     *
     * @returns JsonResponse Respuesta JSON con la lista de consentimientos y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-16
     */
    public function index(): JsonResponse
    {
        $result = $this->consentimientoInformadoService->all();

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Muestra el consentimiento informado correspondiente al identificador indicado.
     *
     * @param  int  $id  Identificador del consentimiento informado.
     *
     * @returns JsonResponse Respuesta JSON con el consentimiento encontrado y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-16
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->consentimientoInformadoService->find($id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Lista los consentimientos informados que se encuentran activos.
     *
     * @returns JsonResponse Respuesta JSON con los consentimientos activos y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-16
     */
    public function listarActivos(): JsonResponse
    {
        $result = $this->consentimientoInformadoService->listarActivos();

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Crea un nuevo consentimiento informado con los datos validados y el archivo adjunto.
     *
     * @param  StoreConsentimientoInformadoRequest  $request  Solicitud HTTP validada con los datos y el archivo.
     *
     * @returns JsonResponse Respuesta JSON con el consentimiento creado y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-16
     */
    public function store(StoreConsentimientoInformadoRequest $request): JsonResponse
    {
        $result = $this->consentimientoInformadoService->create(
            $request->validated(),
            $request->file("archivo"),
        );

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Actualiza el consentimiento informado identificado por el id con los datos validados.
     *
     * @param  UpdateConsentimientoInformadoRequest  $request  Solicitud HTTP validada con los datos a actualizar.
     * @param  int  $id  Identificador del consentimiento informado a actualizar.
     *
     * @returns JsonResponse Respuesta JSON con el consentimiento actualizado y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-16
     */
    public function update(UpdateConsentimientoInformadoRequest $request, int $id): JsonResponse
    {
        $result = $this->consentimientoInformadoService->update(
            $id,
            $request->validated(),
            $request->file("archivo"),
        );

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Elimina el consentimiento informado correspondiente al identificador indicado.
     *
     * @param  int  $id  Identificador del consentimiento informado a eliminar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la eliminación y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-16
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->consentimientoInformadoService->delete($id);

        return response()->json($result, (int) $result["status"]);
    }
}
