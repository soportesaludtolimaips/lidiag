<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigWhatsappBuscarRequest;
use App\Http\Requests\Configuracion\StoreConfigWhatsappRequest;
use App\Http\Requests\Configuracion\UpdateConfigWhatsappRequest;
use App\Services\Configuracion\ConfigWhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigWhatsappController extends Controller
{
    /**
     * Constructor del controlador que inicializa el servicio de configuración de WhatsApp.
     *
     * @param  ConfigWhatsappService  $configWhatsappService  Servicio encargado de la lógica de configuración de WhatsApp.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function __construct(
        private readonly ConfigWhatsappService $configWhatsappService,
    ) {}

    /**
     * Obtiene la lista de configuraciones de WhatsApp, opcionalmente filtradas por sede.
     *
     * @param  Request  $request  Solicitud HTTP que puede contener el parámetro sede_id.
     *
     * @returns JsonResponse Respuesta JSON con la lista de configuraciones y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->configWhatsappService->all($request->integer("sede_id"));

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Muestra la configuración de WhatsApp correspondiente al identificador indicado.
     *
     * @param  int  $id  Identificador de la configuración de WhatsApp.
     *
     * @returns JsonResponse Respuesta JSON con la configuración encontrada y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->configWhatsappService->find($id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Crea una nueva configuración de WhatsApp con los datos validados de la solicitud.
     *
     * @param  StoreConfigWhatsappRequest  $request  Solicitud HTTP validada con los datos de la configuración.
     *
     * @returns JsonResponse Respuesta JSON con la configuración creada y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function store(StoreConfigWhatsappRequest $request): JsonResponse
    {
        $result = $this->configWhatsappService->create($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Actualiza la configuración de WhatsApp identificada por el id con los datos validados.
     *
     * @param  UpdateConfigWhatsappRequest  $request  Solicitud HTTP validada con los datos a actualizar.
     * @param  int  $id  Identificador de la configuración de WhatsApp a actualizar.
     *
     * @returns JsonResponse Respuesta JSON con la configuración actualizada y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function update(UpdateConfigWhatsappRequest $request, int $id): JsonResponse
    {
        $result = $this->configWhatsappService->update($id, $request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Elimina la configuración de WhatsApp correspondiente al identificador indicado.
     *
     * @param  int  $id  Identificador de la configuración de WhatsApp a eliminar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la eliminación y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configWhatsappService->delete($id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Busca la configuración de WhatsApp según la sede y el servicio de cita indicados.
     *
     * @param  ConfigWhatsappBuscarRequest  $request  Solicitud HTTP validada con los parámetros sede_id y servicio_id.
     *
     * @returns JsonResponse Respuesta JSON con la configuración encontrada y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-11
     */
    public function buscar(ConfigWhatsappBuscarRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->configWhatsappService->buscarPorSedeYServicio(
            (int) $validated["sede_id"],
            (int) $validated["servicio_id"],
        );

        return response()->json($result, (int) $result["status"]);
    }
}
