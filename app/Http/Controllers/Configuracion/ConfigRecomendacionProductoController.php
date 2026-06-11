<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfigRecomendacionProductoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:config.productos.index')->only(['index', 'show']);
        $this->middleware('permission:config.productos.create')->only(['store']);
        $this->middleware('permission:config.productos.edit')->only(['update']);
        $this->middleware('permission:config.productos.destroy')->only(['destroy']);
    }

    /**
     * Lista todas las recomendaciones con datos del producto.
     */
    public function index(Request $request)
    {
        $query = ConfigRecomendacionProducto::with('producto:id,nom_produc,cod_cups');

        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', filter_var($request->estado, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json($query->orderByDesc('id')->get());
    }

    /**
     * Muestra una recomendación específica.
     */
    public function show($id)
    {
        $recomendacion = ConfigRecomendacionProducto::with('producto')->find($id);

        if (! $recomendacion) {
            return response()->json(['error' => 'Recomendación no encontrada.'], 404);
        }

        return response()->json($recomendacion);
    }

    /**
     * Crea o actualiza una recomendación para un producto.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:config_productos,id',
            'recomendacion' => 'nullable|string',
            'estado' => 'nullable|boolean',
        ], [
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $recomendacion = ConfigRecomendacionProducto::updateOrCreate(
            ['producto_id' => $request->producto_id],
            [
                'recomendacion' => $request->recomendacion,
                'estado' => $request->input('estado', true),
            ]
        );

        return response()->json([
            'message' => 'Recomendación guardada correctamente.',
            'data' => $recomendacion->load('producto:id,nom_produc,cod_cups'),
        ], 201);
    }

    /**
     * Actualiza una recomendación existente.
     */
    public function update(Request $request, $id)
    {
        $recomendacion = ConfigRecomendacionProducto::find($id);

        if (! $recomendacion) {
            return response()->json(['error' => 'Recomendación no encontrada.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'recomendacion' => 'nullable|string',
            'estado' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $recomendacion->update([
            'recomendacion' => $request->input('recomendacion', $recomendacion->recomendacion),
            'estado' => $request->input('estado', $recomendacion->estado),
        ]);

        return response()->json([
            'message' => 'Recomendación actualizada correctamente.',
            'data' => $recomendacion->fresh()->load('producto:id,nom_produc,cod_cups'),
        ]);
    }

    /**
     * Elimina una recomendación.
     */
    public function destroy($id)
    {
        $recomendacion = ConfigRecomendacionProducto::find($id);

        if (! $recomendacion) {
            return response()->json(['error' => 'Recomendación no encontrada.'], 404);
        }

        $recomendacion->delete();

        return response()->json(['message' => 'Recomendación eliminada correctamente.']);
    }
}
