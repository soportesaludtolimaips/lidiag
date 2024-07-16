<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\ProduccionDetalladaExport;
use App\Http\Controllers\Controller;
use App\Models\Estudio\Estudio;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteProduccion extends Controller
{
    public function reporteProduccionDetallado(Request $resquest)
    {
        $fechaInicio = $resquest->fechaIni;
        $fechaFin = $resquest->fechaFin;
        $sedeId = $resquest->sedeId;

        /* return Estudio::with([
            'paciente',
            'medico',
            'quienRegistro',
            'sede',
            'prioridad',
            'productos',
            'productos.transcriptor',
            'diagnosticos'
        ])
            ->whereBetween('fec_estudio', [$fechaInicio, $fechaFin])
            ->where('sede_id', $sedeId)
            ->limit(12)
            ->get(); */





        // Define el nombre y la ruta del archivo
        $fileName = 'estudios_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = 'reporte_lecturas/' . $fileName;

        return Excel::download(new ProduccionDetalladaExport($fechaInicio, $fechaFin, $sedeId),  'produccion lecturas de imagenes diagnosticas.xlsx');

        // Retorna la ruta del archivo almacenado
        //return response()->json(['file_path' => $filePath]);
    }
}
