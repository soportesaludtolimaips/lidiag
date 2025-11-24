<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigTipoDocumento;
use Illuminate\Http\Request;

class ConfigTipoDocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiposDocumento = ConfigTipoDocumento::all();
        return response()->json($tiposDocumento);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $tipoDocumento = ConfigTipoDocumento::create($request->all());
        return response()->json($tipoDocumento);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $tipoDocumento = ConfigTipoDocumento::find($id);
        if (!$tipoDocumento) {
            return response()->json([
                'message' => 'Tipo de documento no encontrado'
            ], 404);
        }
        return response()->json($tipoDocumento);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $tipoDocumento = ConfigTipoDocumento::find($id);
        if (!$tipoDocumento) {
            return response()->json([
                'message' => 'Tipo de documento no encontrado'
            ], 404);
        }

        $tipoDocumento->update($request->all());
        return response()->json($tipoDocumento);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $tipoDocumento = ConfigTipoDocumento::find($id);
        if (!$tipoDocumento) {
            return response()->json([
                'message' => 'Tipo de documento no encontrado'
            ], 404);
        }

        $tipoDocumento->delete();
        
        return response()->json([
            'message' => 'Tipo de documento eliminado'
        ]);
    }

    public function listarActivos()
    {
        $tiposDocumento = ConfigTipoDocumento::where('estado', 1)->get();
        return response()->json($tiposDocumento);
    }
}
