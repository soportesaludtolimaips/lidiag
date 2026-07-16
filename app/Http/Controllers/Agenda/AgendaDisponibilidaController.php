<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\AgendaDisponibilidaRequest;
use App\Services\Agenda\AgendaDisponibilidaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaDisponibilidaController extends Controller
{
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

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->list($request->only([
            'sede_id', 'servicio_cita_id', 'bus_fech_ini', 'bus_fecha_fin',
        ]));

        return response()->json($result['data'], $result['status']);
    }

    public function store(AgendaDisponibilidaRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        return response()->json($result['data'], $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->find($id);

        return response()->json($result['data'], $result['status']);
    }

    public function update(AgendaDisponibilidaRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());

        return response()->json($result['data'], $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
