<?php

namespace App\Services\Reportes;

use App\Services\BaseService;

class ReporteLecturaService extends BaseService
{
    public function generarPdf(array $reporteLectura, string $nomArchivoReporte): array
    {
        $data = [
            'reporteLectura' => $reporteLectura,
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('modulos.reportes.reportes-lecturas.resporte-lectura', $data);
        $path = storage_path('app/reporte_lecturas/');
        $pdf->save($path.$nomArchivoReporte);

        return $this->success([
            'path' => $path.$nomArchivoReporte,
            'filename' => $nomArchivoReporte,
        ], 'Reporte generado correctamente');
    }
}
