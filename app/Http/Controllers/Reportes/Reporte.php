<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\ProduccionDetalladaExport;
use App\Http\Controllers\Controller;
use App\Models\Estudio\Estudio;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class Reporte extends Controller
{
    public function reporteProduccionDetallado(Request $resquest)
    {
        $fechaInicio = $resquest->input('fecha_inicio');
        $fechaFin = $resquest->input('fecha_fin');
        $sedeId = $resquest->input('sede_id');

        // Define el nombre y la ruta del archivo
        $fileName = 'estudios_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = 'exports/' . $fileName;

        return Excel::download(new ProduccionDetalladaExport($fechaInicio, $fechaFin, $sedeId), $fileName);
    }
}
