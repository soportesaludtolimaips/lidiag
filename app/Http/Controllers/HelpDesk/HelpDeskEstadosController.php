<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\HelpDesk\HelpDeskEstado;
use Illuminate\Http\Request;

class HelpDeskEstadosController extends Controller
{
    public function index()
    {
        return response()->json(
            HelpDeskEstado::query()->orderBy('orden')->orderBy('nombre')->get()
        );
    }

    public function store(Request $request)
    {
        // Decisión: estados fijos (solo 4 base).
        return response()->json(['message' => 'Estados fijos: no se permite crear nuevos estados.'], 403);
    }

    public function show(HelpDeskEstado $estado)
    {
        return response()->json($estado);
    }

    public function update(Request $request, HelpDeskEstado $estado)
    {
        // Decisión: bloquear edición (especialmente estados base).
        return response()->json(['message' => 'Estados fijos: no se permite editar estados.'], 403);
    }

    public function destroy(HelpDeskEstado $estado)
    {
        // Decisión: bloquear borrado.
        return response()->json(['message' => 'Estados fijos: no se permite eliminar estados.'], 403);
    }
}
