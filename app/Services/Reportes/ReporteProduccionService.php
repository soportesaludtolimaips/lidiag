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
    /**
     * Genera el reporte detallado de producción en formato Excel.
     *
     * @param  array  $data  Datos filtrados: fechaIni, fechaFin, sedeId, quien_asigno, medico, tipo.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Normaliza un valor nullable convirtiendo cadenas vacías, '0' o 'null' a null.
     *
     * @param  mixed  $value  Valor a normalizar.
     *
     * @returns mixed|null Valor normalizado o null.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Genera el reporte de estudios tomados en PAC en formato Excel.
     *
     * @param  array  $data  Datos filtrados: sedeId, fechaIni, fechaFin.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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
