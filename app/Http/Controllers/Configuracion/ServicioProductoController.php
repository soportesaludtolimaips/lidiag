<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Citas\CitaPivot;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigServicioCitas;
use Illuminate\Http\Request;

class ServicioProductoController extends Controller
{
    public function index(Request $request)
    {
        $servicioId = $request->servicio_cita_id;

        if ($servicioId) {
            $rels = CitaPivot::where('servicio_cita_id', $servicioId)
                ->with('producto')
                ->get();

            return response()->json($rels);
        }

        $servicios = ConfigServicioCitas::with(['productos' => function ($query) {
            $query->where('config_servicio_cita_producto.estado', true);
        }])->where('estado', true)->get();

        return response()->json($servicios);
    }

    public function store(Request $request)
    {
        $request->validate([
            'servicio_cita_id' => 'required|exists:config_servicio_citas,id',
            'producto_id' => 'required|exists:config_productos,id',
        ]);

        $existe = CitaPivot::where('servicio_cita_id', $request->servicio_cita_id)
            ->where('producto_id', $request->producto_id)
            ->first();

        if ($existe) {
            $existe->update(['estado' => true]);

            return response()->json(['message' => 'Producto vinculado al servicio.']);
        }

        CitaPivot::create([
            'servicio_cita_id' => $request->servicio_cita_id,
            'producto_id' => $request->producto_id,
            'estado' => true,
        ]);

        return response()->json(['message' => 'Producto vinculado al servicio.'], 201);
    }

    public function destroy($id)
    {
        $rel = CitaPivot::findOrFail($id);
        $rel->update(['estado' => false]);

        return response()->json(['message' => 'Vinculación eliminada.']);
    }

    public function productosNoVinculados(Request $request)
    {
        $request->validate([
            'servicio_cita_id' => 'required|exists:config_servicio_citas,id',
        ]);

        $vinculados = CitaPivot::where('servicio_cita_id', $request->servicio_cita_id)
            ->where('estado', true)
            ->pluck('producto_id')
            ->toArray();

        $productos = ConfigProducto::with('recomendacionProducto')
            ->whereNotIn('id', $vinculados)
            ->where('estado', true)
            ->orderBy('nom_produc')
            ->get();

        return response()->json($productos);
    }
}
