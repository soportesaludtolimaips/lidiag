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
        $ecografiaProducto = EcografiaProducto::find($id);
        if (!$ecografiaProducto) {
            return response()->json(['message' => 'El producto de la ecografía no se encontro'], 404);
        }

        if ($request->accion === "IMPRIMIR_LECTURA") {
            $ecografiaProducto->lectura = $request->registro['lectura'];
            $ecografiaProducto->opinion = $request->registro['opinion'];
            $ecografiaProducto->usua_regis_id = Auth()->id();
            $ecografiaProducto->fechor_lectura = now();
        } elseif ($request->accion === "TERMINAR_LECTURA") {
            $ecografiaProducto->lectura = $request->registro['lectura'];
            $ecografiaProducto->opinion = $request->registro['opinion'];
            $ecografiaProducto->usua_regis_id = Auth()->id();
            $ecografiaProducto->fechor_lectura = now();
            $ecografiaProducto->estado = 'LEIDO';
        }

        $ecografiaProducto->save();

        return response()->json(['message' => 'Lectura guardada exitosamente'], 201);
    }

    public function listarPendientesPorLectura($idMedico)
    {
        $ecografiasPendientes = Ecografia::select(
            'ecografias.*',
            'ep.id as ecografia_producto_id',
            'ep.lectura',
            'ep.fechor_lectura',
            'p.id as producto_id',
            'p.nom_produc'
        )
            ->join('ecografias_productos as ep', 'ep.ecografia_id', '=', 'ecografias.id')
            ->join('config_productos as p', 'p.id', '=', 'ep.producto_id')
            ->where('ecografias.medico_id', $idMedico)
            ->whereNull('ep.lectura')
            ->whereNull('ep.fechor_lectura')
            ->with(['paciente', 'usuarioRegistrador', 'adminSalud', 'medico'])
            ->get();

        return response()->json(['ecografias' => $ecografiasPendientes]);
    }
}
