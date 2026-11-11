<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\AgendaDisponibilidaDetalleRequest;
use App\Services\Agenda\AgendaDisponibilidaDetalleService;
use Illuminate\Http\JsonResponse;

class AgendaDisponibilidaDetalleController extends Controller
{
    /**
     * Constructor del controlador de detalles de disponibilidad de agenda.
     *
     * @param  AgendaDisponibilidaDetalleService  $service  servicio de gestión de detalles de disponibilidad.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function __construct(
        private readonly AgendaDisponibilidaDetalleService $service,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:agendamiento.disponibilidad.index|agenda.index')->only(['index']);
        $this->middleware('permission:agenda.show|agendamiento.disponibilidad.index|agenda.index')->only(['show']);
        $this->middleware('permission:agendamiento.disponibilidad.create|agenda.create')->only(['store']);
        $this->middleware('permission:agendamiento.disponibilidad.delete|agenda.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los detalles de disponibilidad registrados.
     *
     * @returns JsonResponse respuesta con los detalles de disponibilidad.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function index(): JsonResponse
    {
        $result = $this->service->all();

        return response()->json([$result['data']], $result['status']);
    }

    /**
     * Crea uno o más detalles de disponibilidad a partir de datos enviados.
     *
     * @param  AgendaDisponibilidaDetalleRequest  $request  datos validados para crear los detalles.
     *
     * @returns JsonResponse respuesta con los detalles creados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function store(AgendaDisponibilidaDetalleRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        return response()->json($result, $result["status"]);
    }

    /**
     * Muestra los detalles de un registro de disponibilidad específico.
     *
     * @param  int  $id  identificador del detalle de disponibilidad.
     *
     * @returns JsonResponse respuesta con los datos del detalle.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->service->find($id);

        return response()->json([$result['data']], $result['status']);
    }

    /**
     * Elimina un detalle de disponibilidad específico.
     *
     * @param  int  $id  identificador del detalle a eliminar.
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
