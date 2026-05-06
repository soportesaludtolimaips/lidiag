<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Dompdf\Dompdf;

class ResporteLecturaController extends Controller
{
    private $reporteLectura;

    private $nomArchivoReporte;

    public function __construct($reporteLectura, $nomArchivoReporte)
    {
        $this->reporteLectura = $reporteLectura;
        $this->nomArchivoReporte = $nomArchivoReporte;
    }

    /**
     * Genera un reporte PDF de lectura usando Dompdf.
     *
     * @return void Guarda el archivo PDF en `storage/app/reporte_lecturas/`.
     */
    public function generar_reporte()
    {
        $data = [
            'reporteLectura' => $this->reporteLectura,
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('modulos.reportes.reportes-lecturas.resporte-lectura', $data)->save(storage_path('app/reporte_lecturas/').$this->nomArchivoReporte);
    }
}
