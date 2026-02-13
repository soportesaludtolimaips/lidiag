<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\HelpDesk\HelpDeskCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HelpDeskCategoriasController extends Controller
{
    public function index()
    {
        return response()->json(
            HelpDeskCategoria::query()->orderBy('nombre')->get()
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'activo' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $cat = HelpDeskCategoria::create([
            'nombre' => $request->nombre,
            'activo' => $request->has('activo') ? (bool) $request->activo : true,
        ]);

        return response()->json($cat, 201);
    }

    public function show(HelpDeskCategoria $categoria)
    {
        return response()->json($categoria);
    }

    public function update(Request $request, HelpDeskCategoria $categoria)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'activo' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $categoria->update([
            'nombre' => $request->nombre,
            'activo' => (bool) $request->activo,
        ]);

        return response()->json($categoria);
    }

    public function destroy(HelpDeskCategoria $categoria)
    {
        // En vez de borrar, se desactiva (patrón del proyecto)
        $categoria->activo = false;
        $categoria->save();

        return response()->json(['ok' => true]);
    }
}
