<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\AgendaDisponibilidaIndexRequest;
use App\Http\Requests\Agenda\AgendaDisponibilidaRequest;
use App\Services\Agenda\AgendaDisponibilidaService;
use Illuminate\Http\JsonResponse;

class AgendaDisponibilidaController extends Controller
{
    /**
     * Constructor del controlador de disponibilidad de agenda.
     *
     * @param  AgendaDisponibilidaService  $service  servicio de gestión de disponibilidad.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function __construct(
        private readonly AgendaDisponibilidaService $service,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:agendamiento.disponibilidad.index|agenda.index')->only(['index']);
        $this->middleware('permission:agenda.show|agendamiento.disponibilidad.index|agenda.index')->only(['show']);
        $this->middleware('permission:agendamiento.disponibilidad.create|agenda.create')->only(['store']);
        $this->middleware('permission:agendamiento.disponibilidad.update|agenda.edit')->only(['update']);
        $this->middleware('permission:agendamiento.disponibilidad.delete|agenda.destroy')->only(['destroy']);
    }

    /**
     * Lista las disponibilidades de agenda con filtros por sede, servicio y rango de fechas.
     *
     * @param  AgendaDisponibilidaIndexRequest  $request  parámetros validados de filtrado.
     *
     * @returns JsonResponse respuesta con las disponibilidades encontradas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function index(AgendaDisponibilidaIndexRequest $request): JsonResponse
    {
        $result = $this->service->list($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Crea una nueva disponibilidad de agenda con sus slots de detalle.
     *
     * @param  AgendaDisponibilidaRequest  $request  datos validados de la disponibilidad.
     *
     * @returns JsonResponse respuesta con la disponibilidad creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function store(AgendaDisponibilidaRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        return response()->json($result, $result["status"]);
    }

    /**
     * Muestra los detalles de una disponibilidad específica.
     *
     * @param  int  $id  identificador de la disponibilidad.
     *
     * @returns JsonResponse respuesta con los datos de la disponibilidad.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->service->find($id);

        return response()->json($result, $result["status"]);
    }

    /**
     * Actualiza una disponibilidad existente y regenera sus slots de detalle.
     *
     * @param  AgendaDisponibilidaRequest  $request  datos actualizados de la disponibilidad.
     * @param  int  $id  identificador de la disponibilidad.
     *
     * @returns JsonResponse respuesta con la disponibilidad actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function update(AgendaDisponibilidaRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());

        return response()->json($result, $result["status"]);
    }

    /**
     * Elimina una disponibilidad y sus slots asociados.
     *
     * @param  int  $id  identificador de la disponibilidad a eliminar.
     *
     * @returns JsonResponse respuesta de confirmación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
