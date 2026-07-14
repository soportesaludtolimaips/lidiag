<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\Reportes\ReporteLecturaService;

class ResporteLecturaController extends Controller
{
    public function __construct(
        private readonly ReporteLecturaService $reporteLecturaService,
    ) {}

    public function generar_reporte($reporteLectura, $nomArchivoReporte): void
    {
        $this->reporteLecturaService->generarPdf($reporteLectura, $nomArchivoReporte);
    }
}
