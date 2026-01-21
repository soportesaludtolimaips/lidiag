<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Agenda\AgendaDisponibilida;
use App\Models\Agenda\AgendaDisponibilidaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgendaDisponibilidaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $disponibilidads = AgendaDisponibilida::all();
        return response()->json([$disponibilidads]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request, [
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'intervalo' => 'required|integer|min:1',
        ], [
            'data_inicio.required' => 'La fecha de inicio es obligatoria.',
            'data_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'data_fim.required' => 'La fecha de fin es obligatoria.',
            'data_fim.date' => 'La fecha de fin debe ser una fecha válida.',
            'data_fim.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio debe estar en el formato HH:MM.',
            'hora_fim.required' => 'La hora de fin es obligatoria.',
            'hora_fim.date_format' => 'La hora de fin debe estar en el formato HH:MM.',
            'hora_fim.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'intervalo.required' => 'El intervalo es obligatorio.',
            'intervalo.integer' => 'El intervalo debe ser un número entero.',
            'intervalo.min' => 'El intervalo debe ser al menos 1 minuto.',
        ]);

        if ($validator['data_inicio'] > $validator['data_fim']) {
            return response()->json(['error' => 'La fecha de inicio no puede ser posterior a la fecha de fin.'], 422);
        }

        // Si la validación falla, devuelve los errores
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $disponibilidad = AgendaDisponibilida::create($request->all());
        return response()->json([$disponibilidad]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $disponibilidad = AgendaDisponibilida::find($id);

        if (!$disponibilidad) {
            return response()->json(['error' => 'Disponibilidad no encontrada.'], 404);
        }

        return response()->json([$disponibilidad]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request, [
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'intervalo' => 'required|integer|min:1',
        ], [
            'data_inicio.required' => 'La fecha de inicio es obligatoria.',
            'data_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'data_fim.required' => 'La fecha de fin es obligatoria.',
            'data_fim.date' => 'La fecha de fin debe ser una fecha válida.',
            'data_fim.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio debe estar en el formato HH:MM.',
            'hora_fim.required' => 'La hora de fin es obligatoria.',
            'hora_fim.date_format' => 'La hora de fin debe estar en el formato HH:MM.',
            'hora_fim.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'intervalo.required' => 'El intervalo es obligatorio.',
            'intervalo.integer' => 'El intervalo debe ser un número entero.',
            'intervalo.min' => 'El intervalo debe ser al menos 1 minuto.',
        ]);

        $disponibilidad = AgendaDisponibilida::find($id);

        if (!$disponibilidad) {
            return response()->json(['error' => 'Disponibilidad no encontrada.'], 404);
        }

        $disponibilidad->update($request->all());
        return response()->json([$disponibilidad]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $disponibilidadDetalle = AgendaDisponibilidaDetalle::where('agenda_disponibilida_id', $id)->get();
        if ($disponibilidadDetalle->count() > 0) {
            return response()->json(['error' => 'No se puede eliminar la disponibilidad porque tiene detalles asociados.'], 400);
        }

        $disponibilidad = AgendaDisponibilida::find($id);

        if (!$disponibilidad) {
            return response()->json(['error' => 'Disponibilidad no encontrada.'], 404);
        }

        $disponibilidad->delete();
        return response()->json(['message' => 'Disponibilidad eliminada correctamente.']);
    }
}
