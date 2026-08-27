<?php

namespace App\Services\Reportes;

use App\Exports\ProduccionDetalladaEcografiasExport;
use App\Exports\ProduccionDetalladaExport;
use App\Exports\ReportesEstudioPAC;
use App\Services\BaseService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteProduccionService extends BaseService
{
    public function generateReporteDetallado(array $data): BinaryFileResponse|array
    {
        $fechaInicio = $data['fechaIni'];
        $fechaFin = $data['fechaFin'];
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);
        $quienAsignoId = $this->normalizeNullable($data['quien_asigno'] ?? null);
        $medicoId = $this->normalizeNullable($data['medico'] ?? null);
        $tipo = $data['tipo'];

        if ($tipo === 'Rx') {
            return Excel::download(
                new ProduccionDetalladaExport($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId),
                'produccion lecturas de imagenes diagnosticas.xlsx'
            );
        }

        if ($tipo === 'Ecografia') {
            return Excel::download(
                new ProduccionDetalladaEcografiasExport($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId),
                'produccion lecturas de ecografias.xlsx'
            );
        }

        return $this->error('Tipo de reporte inválido.', 422);
    }

    public function normalizeNullable($value)
    {
        if ($value === null) {
            return null;
        }
        $v = is_string($value) ? trim($value) : $value;
        if ($v === '' || $v === '0' || $v === 0 || $v === 'null') {
            return null;
        }

        return $v;
    }

    public function generateReporteEstudiosPAC(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);
        $fechaInicio = $data['fechaIni'] ?? null;
        $fechaFin = $data['fechaFin'] ?? null;

        if (! $sedeId) {
            return ['message' => 'Debes seleccionar una sede.', 'status' => 422];
        }

        return Excel::download(
            new ReportesEstudioPAC($fechaInicio, $fechaFin, $sedeId, "", ""),
            'estudios tomados en PAC.xlsx'
        );
    }
}
