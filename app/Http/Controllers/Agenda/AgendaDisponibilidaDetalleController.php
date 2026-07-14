<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\AgendaDisponibilidaDetalleRequest;
use App\Services\Agenda\AgendaDisponibilidaDetalleService;
use Illuminate\Http\JsonResponse;

class AgendaDisponibilidaDetalleController extends Controller
{
    public function __construct(
        private readonly AgendaDisponibilidaDetalleService $service,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:agendamiento.disponibilidad.index|agenda.index')->only(['index']);
        $this->middleware('permission:agenda.show|agendamiento.disponibilidad.index|agenda.index')->only(['show']);
        $this->middleware('permission:agendamiento.disponibilidad.create|agenda.create')->only(['store']);
        $this->middleware('permission:agendamiento.disponibilidad.delete|agenda.destroy')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->service->all();

        return response()->json([$result['data']], $result['status']);
    }

    public function store(AgendaDisponibilidaDetalleRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        return response()->json($result['data'], $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->find($id);

        return response()->json([$result['data']], $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
