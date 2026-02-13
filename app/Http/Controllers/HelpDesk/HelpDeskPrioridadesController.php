<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\HelpDesk\HelpDeskPrioridad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HelpDeskPrioridadesController extends Controller
{
    public function index()
    {
        return response()->json(
            HelpDeskPrioridad::query()->orderBy('orden')->orderBy('nombre')->get()
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $prio = HelpDeskPrioridad::create([
            'nombre' => $request->nombre,
            'orden' => $request->has('orden') ? (int) $request->orden : 0,
            'activo' => $request->has('activo') ? (bool) $request->activo : true,
        ]);

        return response()->json($prio, 201);
    }

    public function show(HelpDeskPrioridad $prioridad)
    {
        return response()->json($prioridad);
    }

    public function update(Request $request, HelpDeskPrioridad $prioridad)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50',
            'orden' => 'required|integer|min:0',
            'activo' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $prioridad->update([
            'nombre' => $request->nombre,
            'orden' => (int) $request->orden,
            'activo' => (bool) $request->activo,
        ]);

        return response()->json($prioridad);
    }

    public function destroy(HelpDeskPrioridad $prioridad)
    {
        $prioridad->activo = false;
        $prioridad->save();

        return response()->json(['ok' => true]);
    }
}
