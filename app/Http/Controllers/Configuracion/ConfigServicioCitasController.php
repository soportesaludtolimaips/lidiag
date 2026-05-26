<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigServicioCitas;
use Illuminate\Http\Request;

class ConfigServicioCitasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $servicios = ConfigServicioCitas::all();

        return response()->json($servicios);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $servicio = ConfigServicioCitas::create([
            'nombre' => $request->nombre,
            'estado' => true,
        ]);

        return response()->json($servicio, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $servicio = ConfigServicioCitas::find($id);
        if (! $servicio) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        return response()->json($servicio);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $servicio = ConfigServicioCitas::find($id);
        if (! $servicio) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $servicio->update([
            'nombre' => $request->nombre,
        ]);

        return response()->json($servicio);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $servicio = ConfigServicioCitas::find($id);
        if (! $servicio) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        $servicio->delete();

        return response()->json(['message' => 'Servicio eliminado']);
    }
}
