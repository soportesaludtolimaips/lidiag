<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioSoportesHC;
use Illuminate\Http\Request;

class EstudioSoporteHCController extends Controller
{
    public function listarSoportesHistoriaClinicaPorPorEstudio(Request $request)
    {
        $soportes = EstudioSoportesHC::where('estudio_id', '=', $request->id)->get();
        return response()->json($soportes);
    }

    public function descargarSoporte($nomArchivoEncriptadot)
    {
        $rutaArchivo = storage_path('app/soportes_hc/' . $nomArchivoEncriptadot);
        return response()->download($rutaArchivo);
    }
}
