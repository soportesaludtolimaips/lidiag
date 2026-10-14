<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\ConfigCitaRechazoMotivoRequest;
use App\Services\Agenda\ConfigCitaRechazoMotivoService;
use Illuminate\Http\JsonResponse;

class ConfigCitaRechazoMotivoController extends Controller
{
    /**
     * Constructor del controlador de motivos de rechazo de citas.
     *
     * @param  ConfigCitaRechazoMotivoService  $service  servicio de gestión de motivos de rechazo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-01
     */
    public function __construct(
        private readonly ConfigCitaRechazoMotivoService $service,
    ) {}

    /**
     * Lista todos los motivos de rechazo registrados.
     *
     * @returns JsonResponse respuesta con los motivos de rechazo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-01
     */
    public function index(): JsonResponse
    {
        $result = $this->service->all();

        return response()->json($result, $result["status"]);
    }

    /**
     * Crea un nuevo motivo de rechazo de cita.
     *
     * @param  ConfigCitaRechazoMotivoRequest  $request  datos validados del motivo.
     *
     * @returns JsonResponse respuesta con el motivo creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-01
     */
    public function store(ConfigCitaRechazoMotivoRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        return response()->json($result, $result["status"]);
    }

    /**
     * Actualiza un motivo de rechazo existente.
     *
     * @param  ConfigCitaRechazoMotivoRequest  $request  datos actualizados del motivo.
     * @param  int  $id  identificador del motivo a actualizar.
     *
     * @returns JsonResponse respuesta con el motivo actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-01
     */
    public function update(ConfigCitaRechazoMotivoRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());

        return response()->json($result, $result["status"]);
    }

    /**
     * Elimina un motivo de rechazo de cita.
     *
     * @param  int  $id  identificador del motivo a eliminar.
     *
     * @returns JsonResponse respuesta de confirmación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-06-01
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);

        return response()->json($result, (int) $result['status']);
    }
}
