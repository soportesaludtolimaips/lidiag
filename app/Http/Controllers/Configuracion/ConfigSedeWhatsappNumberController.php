<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreConfigSedeWhatsappNumberRequest;
use App\Http\Requests\Configuracion\UpdateConfigSedeWhatsappNumberRequest;
use App\Services\Configuracion\ConfigSedeWhatsappNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigSedeWhatsappNumberController extends Controller
{
    /**
     * Constructor del controlador de números de WhatsApp por sede.
     *
     * Inicializa el servicio de números de WhatsApp.
     *
     * @param  ConfigSedeWhatsappNumberService  $configSedeWhatsappNumberService  Servicio de números de WhatsApp inyectado.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-01
     */
    public function __construct(
        private readonly ConfigSedeWhatsappNumberService $configSedeWhatsappNumberService,
    ) {}

    /**
     * Lista los números de WhatsApp, filtrando opcionalmente por sede.
     *
     * @param  Illuminate\Http\Request  $request  Solicitud con el filtro opcional sede_id.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con la lista de números de WhatsApp.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-01
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->configSedeWhatsappNumberService->all($request->integer('sede_id'));

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo número de WhatsApp con los datos validados de la solicitud.
     *
     * @param  StoreConfigSedeWhatsappNumberRequest  $request  Solicitud con los datos validados del número.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con el número de WhatsApp creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-01
     */
    public function store(StoreConfigSedeWhatsappNumberRequest $request): JsonResponse
    {
        $result = $this->configSedeWhatsappNumberService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza un número de WhatsApp existente con los datos validados.
     *
     * @param  UpdateConfigSedeWhatsappNumberRequest  $request  Solicitud con los datos validados.
     * @param  int  $id  Identificador único del número de WhatsApp a actualizar.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con el número de WhatsApp actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-01
     */
    public function update(UpdateConfigSedeWhatsappNumberRequest $request, int $id): JsonResponse
    {
        $result = $this->configSedeWhatsappNumberService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un número de WhatsApp existente según su identificador.
     *
     * @param  int  $id  Identificador único del número de WhatsApp a eliminar.
     *
     * @returns Illuminate\Http\JsonResponse Respuesta JSON con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-01
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->configSedeWhatsappNumberService->delete($id);

        return response()->json($result, $result['status']);
    }
}
