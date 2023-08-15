<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Dompdf\Dompdf;

class ResporteLecturaController extends Controller
{
    private $reporteLectura;

    public function __construct($reporteLectura)
    {
        $this->reporteLectura = $reporteLectura;
    }

    public function generar_reporte()
    {
        $data = [
            'reporteLectura' => $this->reporteLectura
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('modulos.reportes.reportes-lecturas.resporte-lectura', $data)->save(storage_path('app/reporte_lecturas/') . $this->reporteLectura->estudio->id . '.pdf');
    }
}
