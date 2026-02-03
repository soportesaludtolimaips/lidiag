<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConsentimientoInformado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConsentimientoInformadoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:config.consentimiento_informado.index')->only(['index', 'show', 'listarActivos']);
        $this->middleware('permission:config.consentimiento_informado.create')->only(['store']);
        $this->middleware('permission:config.consentimiento_informado.edit')->only(['update']);
        $this->middleware('permission:config.consentimiento_informado.destroy')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $consentimientoInformados = ConsentimientoInformado::with(['producto', 'sede', 'usuarioCrea'])
            ->orderByDesc('id')
            ->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Consentimiento Informado listado correctamente',
            'data' => $consentimientoInformados
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|integer|exists:config_productos,id',
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'nombre' => 'required|string|max:255',
            'estado' => 'nullable|boolean',
            'archivo' => 'required|file|mimes:pdf|max:5120',
        ]);

        $path = null;
        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $name = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('consentimiento_informado', $name, 'public');
        }

        $consentimientoInformado = ConsentimientoInformado::create([
            'producto_id' => (int) $request->producto_id,
            'sede_id' => (int) $request->sede_id,
            'usuario_crea_id' => auth()->id(),
            'nombre' => $request->nombre,
            'archivo' => $path,
            'estado' => (bool) ($request->estado ?? true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Consentimiento Informado creado correctamente',
            'data' => $consentimientoInformado->load(['producto', 'sede', 'usuarioCrea'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $consentimientoInformado = ConsentimientoInformado::with(['producto', 'sede', 'usuarioCrea'])->find($id);

        if (!$consentimientoInformado) {
            return response()->json([
                'status' => 'error',
                'message' => 'Consentimiento Informado no encontrado',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Consentimiento Informado obtenido correctamente',
            'data' => $consentimientoInformado
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $consentimientoInformado = ConsentimientoInformado::find($id);

        if (!$consentimientoInformado) {
            return response()->json([
                'status' => 'error',
                'message' => 'Consentimiento Informado no encontrado',
            ], 404);
        }

        $request->validate([
            'producto_id' => 'required|integer|exists:config_productos,id',
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'nombre' => 'required|string|max:255',
            'estado' => 'nullable|boolean',
            'archivo' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $path = $consentimientoInformado->archivo;
        if ($request->hasFile('archivo')) {
            // borrar anterior best-effort
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            $file = $request->file('archivo');
            $name = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('consentimiento_informado', $name, 'public');
        }

        $consentimientoInformado->update([
            'producto_id' => (int) $request->producto_id,
            'sede_id' => (int) $request->sede_id,
            'nombre' => $request->nombre,
            'archivo' => $path,
            'estado' => (bool) ($request->estado ?? true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Consentimiento Informado actualizado correctamente',
            'data' => $consentimientoInformado->load(['producto', 'sede', 'usuarioCrea'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $consentimientoInformado = ConsentimientoInformado::find($id);

        if (!$consentimientoInformado) {
            return response()->json([
                'status' => 'error',
                'message' => 'Consentimiento Informado no encontrado',
            ], 404);
        }

        // borrar archivo best-effort
        if ($consentimientoInformado->archivo && Storage::disk('public')->exists($consentimientoInformado->archivo)) {
            Storage::disk('public')->delete($consentimientoInformado->archivo);
        }
        $consentimientoInformado->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Consentimiento Informado eliminado correctamente',
        ]);
    }

    public function listarActivos()
    {
        $consentimientoInformados = ConsentimientoInformado::with(['producto', 'sede'])
            ->where('estado', true)
            ->orderByDesc('id')
            ->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Consentimiento Informado listado correctamente',
            'data' => $consentimientoInformados
        ]);
    }
}
