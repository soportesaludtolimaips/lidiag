<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Agenda\AgendaDisponibilida;
use App\Models\Agenda\AgendaDisponibilidaDetalle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AgendaDisponibilidaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Compat: existen permisos `agenda.*` y `agendamiento.disponibilidad.*`
        $this->middleware('permission:agendamiento.disponibilidad.index|agenda.index')->only(['index']);
        $this->middleware('permission:agenda.show|agendamiento.disponibilidad.index|agenda.index')->only(['show']);
        $this->middleware('permission:agendamiento.disponibilidad.create|agenda.create')->only(['store']);
        $this->middleware('permission:agendamiento.disponibilidad.update|agenda.edit')->only(['update']);
        $this->middleware('permission:agendamiento.disponibilidad.delete|agenda.destroy')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AgendaDisponibilida::query()->with(['sede', 'servicio'])->orderByDesc('id');

        if ($request->filled('sede_id')) {
            $query->where('sede_id', $request->sede_id);
        }

        if ($request->filled('bus_fehc_ini') && $request->filled('bus_fecha_fin')) {
            $ini = Carbon::parse($request->bus_fehc_ini)->startOfDay();
            $fin = Carbon::parse($request->bus_fecha_fin)->endOfDay();
            $query->whereBetween('fecha_hora_ini', [$ini, $fin]);
        }

        return response()->json($query->limit(1000)->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'servicio_id' => 'required|integer|exists:config_modulos,id',
            'fecha_hora_ini' => 'required|date',
            'total_citas' => 'required|integer|min:1',
            'dura_cita' => 'required|integer|min:1',
        ], [
            'sede_id.required' => 'La sede es obligatoria.',
            'servicio_id.required' => 'El servicio es obligatorio.',
            'fecha_hora_ini.required' => 'La fecha/hora inicio es obligatoria.',
            'total_citas.required' => 'El total de citas es obligatorio.',
            'dura_cita.required' => 'La duración por cita es obligatoria.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $inicio = Carbon::parse($request->fecha_hora_ini);
        $totalCitas = (int) $request->total_citas;
        $dura = (int) $request->dura_cita;
        $fin = $inicio->copy()->addMinutes($totalCitas * $dura);

        $created = DB::transaction(function () use ($request, $inicio, $fin, $totalCitas, $dura) {
            $disponibilidad = AgendaDisponibilida::create([
                'quien_registro_id' => auth()->id(),
                'sede_id' => (int) $request->sede_id,
                'servicio_id' => (int) $request->servicio_id,
                'fecha_hora_ini' => $inicio,
                'fecha_hora_fin' => $fin,
                'total_citas' => $totalCitas,
                'dura_cita' => $dura,
                'estado' => 'Activo',
            ]);

            $slotStart = $inicio->copy();
            for ($i = 0; $i < $totalCitas; $i++) {
                $slotEnd = $slotStart->copy()->addMinutes($dura);
                AgendaDisponibilidaDetalle::create([
                    'agenda_disponibilidad_id' => $disponibilidad->id,
                    'fecha_hora_inicio' => $slotStart,
                    'fecha_hora_fin' => $slotEnd,
                    'estado' => 'Disponible',
                ]);
                $slotStart = $slotEnd;
            }

            return $disponibilidad;
        });

        return response()->json([
            'message' => 'Disponibilidad creada correctamente.',
            'data' => $created->load(['sede', 'servicio']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $disponibilidad = AgendaDisponibilida::with(['sede', 'servicio'])->find($id);

        if (! $disponibilidad) {
            return response()->json(['error' => 'Disponibilidad no encontrada.'], 404);
        }

        return response()->json($disponibilidad);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $disponibilidad = AgendaDisponibilida::find($id);

        if (! $disponibilidad) {
            return response()->json(['error' => 'Disponibilidad no encontrada.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'servicio_id' => 'required|integer|exists:config_modulos,id',
            'fecha_hora_ini' => 'required|date',
            'total_citas' => 'required|integer|min:1',
            'dura_cita' => 'required|integer|min:1',
            'estado' => 'nullable|in:Activo,Inactivo,Completado,Pendiente',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $inicio = Carbon::parse($request->fecha_hora_ini);
        $totalCitas = (int) $request->total_citas;
        $dura = (int) $request->dura_cita;
        $fin = $inicio->copy()->addMinutes($totalCitas * $dura);

        DB::transaction(function () use ($disponibilidad, $request, $inicio, $fin, $totalCitas, $dura) {
            $disponibilidad->update([
                'sede_id' => (int) $request->sede_id,
                'servicio_id' => (int) $request->servicio_id,
                'fecha_hora_ini' => $inicio,
                'fecha_hora_fin' => $fin,
                'total_citas' => $totalCitas,
                'dura_cita' => $dura,
                'estado' => $request->estado ?? $disponibilidad->estado,
            ]);

            // Regenerar detalle (simple y consistente)
            AgendaDisponibilidaDetalle::where('agenda_disponibilidad_id', $disponibilidad->id)->delete();
            $slotStart = $inicio->copy();
            for ($i = 0; $i < $totalCitas; $i++) {
                $slotEnd = $slotStart->copy()->addMinutes($dura);
                AgendaDisponibilidaDetalle::create([
                    'agenda_disponibilidad_id' => $disponibilidad->id,
                    'fecha_hora_inicio' => $slotStart,
                    'fecha_hora_fin' => $slotEnd,
                    'estado' => 'Disponible',
                ]);
                $slotStart = $slotEnd;
            }
        });

        return response()->json([
            'message' => 'Disponibilidad actualizada correctamente.',
            'data' => $disponibilidad->fresh()->load(['sede', 'servicio']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $disponibilidadDetalle = AgendaDisponibilidaDetalle::where('agenda_disponibilidad_id', $id)->get();
        if ($disponibilidadDetalle->count() > 0) {
            return response()->json(['error' => 'No se puede eliminar la disponibilidad porque tiene detalles asociados.'], 400);
        }

        $disponibilidad = AgendaDisponibilida::find($id);

        if (! $disponibilidad) {
            return response()->json(['error' => 'Disponibilidad no encontrada.'], 404);
        }

        $disponibilidad->delete();

        return response()->json(['message' => 'Disponibilidad eliminada correctamente.']);
    }
}
