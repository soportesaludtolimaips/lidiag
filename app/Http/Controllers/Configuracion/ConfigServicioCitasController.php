<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigServicioCita;
use Illuminate\Http\Request;

class ConfigServicioCitasController extends Controller
{
    public function index(Request $request)
    {
        $query = ConfigServicioCita::with('sede');

        if ($request->sede_id) {
            $query->where('sede_id', $request->sede_id);
        }

        return response()->json($query->orderBy('nombre')->get());
    }

    public function misServicios(Request $request)
    {
        $query = ConfigServicioCita::with('sede');

        if ($request->sede_id) {
            $query->where('sede_id', $request->sede_id);
        }

        return response()->json($query->orderBy('nombre')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'sede_id' => 'required|exists:config_sedes,id',
        ]);

        $servicio = ConfigServicioCita::create([
            'nombre' => $request->nombre,
            'sede_id' => $request->sede_id,
            'estado' => $request->boolean('estado', true),
        ]);

        return response()->json($servicio->load('sede'), 201);
    }

    public function show($id)
    {
        $servicio = ConfigServicioCita::with('sede')->find($id);
        if (! $servicio) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        return response()->json($servicio);
    }

    public function update(Request $request, $id)
    {
        $servicio = ConfigServicioCita::find($id);
        if (! $servicio) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'sede_id' => 'required|exists:config_sedes,id',
        ]);

        $servicio->update([
            'nombre' => $request->nombre,
            'sede_id' => $request->sede_id,
            'estado' => $request->boolean('estado', $servicio->estado),
        ]);

        return response()->json($servicio->load('sede'));
    }

    public function destroy($id)
    {
        $servicio = ConfigServicioCita::find($id);
        if (! $servicio) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        $servicio->delete();

        return response()->json(['message' => 'Servicio eliminado']);
    }
}
