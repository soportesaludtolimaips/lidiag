<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Dompdf\Dompdf;

class ResporteLecturaController extends Controller
{
    private $reporteLectura, $nomArchivoReporte;

    public function __construct($reporteLectura, $nomArchivoReporte)
    {
        $this->reporteLectura = $reporteLectura;
        $this->nomArchivoReporte = $nomArchivoReporte;
    }

    public function generar_reporte()
    {
        $data = [
            'reporteLectura' => $this->reporteLectura
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('modulos.reportes.reportes-lecturas.resporte-lectura', $data)->save(storage_path('app/reporte_lecturas/') . $this->nomArchivoReporte);
    }
}
