<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Estudios\PlantillaEstudioController;
use App\Models\Ecografias\Ecografia;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use Illuminate\Http\Request;

class ecografiaLecturaController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function leer(Request $request, $id)
    {
        // Verificar si existen productos para la ecografía
        $query = EcografiaProducto::where('ecografia_id', $id);

        if (!$query->exists()) {
            return response()->json(['message' => 'El producto de la ecografía no se encontró'], 404);
        }

        // Acción: IMPRIMIR_LECTURA (solo primer producto)
        if ($request->accion === "IMPRIMIR_LECTURA") {
            $producto = $query->first();

            $producto->update([
                'lectura' => $request->registro['lectura'],
                'opinion' => $request->registro['opinion'],
                'usua_regis_id' => auth()->id(),
                'fechor_lectura' => now(),
            ]);
        }

        // Acción: TERMINAR_LECTURA (todos los productos)
        if ($request->accion === "TERMINAR_LECTURA") {
            $query->update([
                'lectura' => $request->registro['lectura'],
                'opinion' => $request->registro['opinion'],
                'usua_regis_id' => auth()->id(),
                'fechor_lectura' => now(),
                'estado' => 'LEIDO'
            ]);
        }

        return response()->json(['message' => 'Lectura guardada exitosamente'], 201);
    }



    public function listarPendientesPorLectura($idMedico)
    {
        $medico = User::find($idMedico);
        if(!$medico){
            return response()->json(['message' => 'El medico no se encontro'], 404);
        }
        $ecografiasPendientes = Ecografia::select(
            'ecografias.*',
            'ep.id as ecografia_producto_id',
            'ep.lectura',
            'ep.opinion',
            'ep.fechor_lectura',
            'p.id as producto_id',
            'p.nom_produc'
        )
            ->join('ecografias_productos as ep', 'ep.ecografia_id', '=', 'ecografias.id')
            ->join('config_productos as p', 'p.id', '=', 'ep.producto_id')
            ->where('ecografias.medico_id', $idMedico)
            ->where('ep.estado', '=', 'EN PROCESO')
            ->with(['paciente', 'usuarioRegistrador', 'adminSalud', 'medico'])
            ->get();

        return response()->json(['ecografias' => $ecografiasPendientes]);
    }
}
