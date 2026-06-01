<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\Citas\ConfigCitaRechazoMotivo;
use Illuminate\Http\Request;

class ConfigCitaRechazoMotivoController extends Controller
{
    public function index()
    {
        return response()->json(ConfigCitaRechazoMotivo::orderBy('motivo')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'motivo' => 'required|string|max:255',
        ]);

        $motivo = ConfigCitaRechazoMotivo::create([
            'motivo' => $request->motivo,
            'activo' => $request->boolean('activo', true),
        ]);

        return response()->json($motivo, 201);
    }

    public function update(Request $request, $id)
    {
        $motivo = ConfigCitaRechazoMotivo::findOrFail($id);

        $request->validate([
            'motivo' => 'required|string|max:255',
        ]);

        $motivo->update([
            'motivo' => $request->motivo,
            'activo' => $request->boolean('activo', $motivo->activo),
        ]);

        return response()->json($motivo);
    }

    public function destroy($id)
    {
        $motivo = ConfigCitaRechazoMotivo::findOrFail($id);
        $motivo->delete();

        return response()->json(['message' => 'Motivo eliminado']);
    }
}
