<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConsentimientoInformado;
use Illuminate\Http\Request;

class ConsentimientoInformadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $consentimientoInformados = ConsentimientoInformado::all();
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
        $consentimientoInformado = ConsentimientoInformado::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Consentimiento Informado creado correctamente',
            'data' => $consentimientoInformado
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $consentimientoInformado = ConsentimientoInformado::find($id);

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

        $consentimientoInformado->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Consentimiento Informado actualizado correctamente',
            'data' => $consentimientoInformado
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

        $consentimientoInformado->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Consentimiento Informado eliminado correctamente',
        ]);
    }

    public function listarActivos()
    {
        $consentimientoInformados = ConsentimientoInformado::where('estado', true)->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Consentimiento Informado listado correctamente',
            'data' => $consentimientoInformados
        ]);
    }
}
