<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Estudio\Estudio;
use Illuminate\Http\Request;

class ReporteProduccion extends Controller
{
    public function reporteProduccionDetallado(Request $resquest)
    {
        $resporte = Estudio::select('fec_estudio', 'paciente.num_docu', 'paciente.nombres')
            ->with('paciente')
            ->limit(20)
            ->get();

        return $resporte;
    }
}
