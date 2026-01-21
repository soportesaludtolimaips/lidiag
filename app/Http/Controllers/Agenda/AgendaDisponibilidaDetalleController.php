<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Agenda\AgendaDisponibilidaDetalle;
use Illuminate\Http\Request;

class AgendaDisponibilidaDetalleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $agendaDisponibilidaDetalle = AgendaDisponibilidaDetalle::all();
        return response()->json([$agendaDisponibilidaDetalle]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'total_citas' => 'required|integer|min:1',
            'dura_cita' => 'required|integer|min:1',
            'fecha_hora_inicio' => 'required|date',
        ]);

        $data = [];
        $inicio = \Carbon\Carbon::parse($request->fecha_hora_inicio);

        for ($i = 0; $i < $request->total_citas; $i++) {
            $fin = $inicio->copy()->addMinutes($request->dura_cita);

            $registro = AgendaDisponibilidaDetalle::create([
                'fecha_hora_inicio' => $inicio,
                'fecha_hora_fin' => $fin,
                'duracion' => $request->dura_cita,
            ]);

            $data[] = $registro;

            $inicio = $fin;
        }

        return response()->json($data);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $agendaDisponibilidaDetalle = AgendaDisponibilidaDetalle::find($id);

        if (!$agendaDisponibilidaDetalle) {
            return response()->json(['error' => 'Detalle de disponibilidad no encontrado.'], 404);
        }

        return response()->json([$agendaDisponibilidaDetalle]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $agendaDisponibilidaDetalle = AgendaDisponibilidaDetalle::find($id);

        if (!$agendaDisponibilidaDetalle) {
            return response()->json(['error' => 'Detalle de disponibilidad no encontrado.'], 404);
        }

        $agendaDisponibilidaDetalle->delete();
        return response()->json(['message' => 'Detalle de disponibilidad eliminado correctamente.']);
    }
}
