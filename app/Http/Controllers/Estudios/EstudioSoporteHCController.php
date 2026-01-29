<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioSoportesHC;
use Illuminate\Http\Request;

class EstudioSoporteHCController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:estudio.asignar|estudio.transcribir|estudio.leerEstudio');
    }

    /**
     * Lista los soportes de historia clínica asociados a un estudio.
     *
     * @param Request $request ID del estudio (`id`).
     * @return \Illuminate\Http\JsonResponse Lista de soportes.
     */
    public function listarSoportesHistoriaClinicaPorPorEstudio(Request $request)
    {
        $soportes = EstudioSoportesHC::where('estudio_id', '=', $request->id)->get();
        return response()->json($soportes);
    }

    /**
     * Descarga un archivo de soporte de historia clínica.
     *
     * @param string $nomArchivoEncriptadot Nombre encriptado del archivo.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse Archivo descargable.
     */
    public function descargarSoporte($nomArchivoEncriptadot)
    {
        $rutaArchivo = storage_path('app/soportes_hc/' . $nomArchivoEncriptadot);
        if (!file_exists($rutaArchivo)) {
            abort(404);
        }
        return response()->download($rutaArchivo);
    }
}
