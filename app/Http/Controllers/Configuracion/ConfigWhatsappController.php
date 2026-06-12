<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigWhatsapp;
use Illuminate\Http\Request;

class ConfigWhatsappController extends Controller
{
    public function index(Request $request)
    {
        $query = ConfigWhatsapp::with(['sede', 'servicio']);

        if ($request->sede_id) {
            $query->where('sede_id', $request->sede_id);
        }

        return response()->json($query->orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'sede_id' => 'nullable|exists:config_sedes,id',
            'servicio_cita_id' => 'nullable|exists:config_servicio_citas,id',
            'mensaje_bienvenida' => 'required|string',
            'activo' => 'boolean',
        ]);

        $config = ConfigWhatsapp::create([
            'sede_id' => $request->sede_id,
            'servicio_cita_id' => $request->servicio_cita_id,
            'mensaje_bienvenida' => $request->mensaje_bienvenida,
            'activo' => $request->boolean('activo', true),
        ]);

        return response()->json($config->load(['sede', 'servicio']), 201);
    }

    public function show($id)
    {
        $config = ConfigWhatsapp::with(['sede', 'servicio'])->findOrFail($id);
        return response()->json($config);
    }

    public function update(Request $request, $id)
    {
        $config = ConfigWhatsapp::findOrFail($id);

        $request->validate([
            'sede_id' => 'nullable|exists:config_sedes,id',
            'servicio_cita_id' => 'nullable|exists:config_servicio_citas,id',
            'mensaje_bienvenida' => 'sometimes|required|string',
            'activo' => 'boolean',
        ]);

        $config->update($request->only([
            'sede_id', 'servicio_cita_id', 'mensaje_bienvenida', 'activo',
        ]));

        return response()->json($config->load(['sede', 'servicio']));
    }

    public function destroy($id)
    {
        $config = ConfigWhatsapp::findOrFail($id);
        $config->delete();

        return response()->json(['message' => 'Configuración eliminada correctamente.']);
    }
}
