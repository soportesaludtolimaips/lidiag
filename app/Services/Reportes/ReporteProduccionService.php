<?php

namespace App\Services\Reportes;

use App\Exports\ProduccionDetalladaEcografiasExport;
use App\Exports\ProduccionDetalladaExport;
use App\Exports\ReporteAgentesTelemetriaExport;
use App\Exports\ReporteAusentismoCitasExport;
use App\Exports\ReportesEstudioPAC;
use App\Exports\ReporteTATExport;
use App\Exports\ReporteTeleRadiologiaExport;
use App\Services\BaseService;
use Illuminate\Support\Facades\Log;
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

        try {
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
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteDetallado failed', [
                'tipo' => $tipo,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Error al generar el reporte: '.$e->getMessage(), 500);
        }
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

        try {
            return Excel::download(
                new ReportesEstudioPAC($fechaInicio, $fechaFin, $sedeId, "", ""),
                'estudios tomados en PAC.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteEstudiosPAC failed', [
                'sede_id' => $sedeId,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'error' => $e->getMessage(),
            ]);

            return ['message' => 'Error al generar el reporte PAC: '.$e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Genera el reporte de estudios derivados a tele-radiología en formato Excel.
     *
     * @param  array  $data  Datos filtrados: sedeId, fechaIni, fechaFin.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function generateReporteTeleRadiologia(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);
        $fechaInicio = $data['fechaIni'] ?? null;
        $fechaFin = $data['fechaFin'] ?? null;

        try {
            return Excel::download(
                new ReporteTeleRadiologiaExport($fechaInicio, $fechaFin, $sedeId),
                'reporte_tele_radiologia.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteTeleRadiologia failed', [
                'sede_id' => $sedeId,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'error' => $e->getMessage(),
            ]);

            return ['message' => 'Error al generar el reporte de tele-radiología: '.$e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Genera el reporte de ausentismo y cancelaciones de citas en formato Excel.
     *
     * @param  array  $data  Datos filtrados: sedeId, fechaIni, fechaFin, estado.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function generateReporteAusentismoCitas(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);
        $fechaInicio = $data['fechaIni'] ?? null;
        $fechaFin = $data['fechaFin'] ?? null;
        $estado = $data['estado'] ?? null;

        try {
            return Excel::download(
                new ReporteAusentismoCitasExport($fechaInicio, $fechaFin, $sedeId, $estado),
                'reporte_ausentismo_citas.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteAusentismoCitas failed', [
                'sede_id' => $sedeId,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'error' => $e->getMessage(),
            ]);

            return ['message' => 'Error al generar el reporte de ausentismo: '.$e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Genera el reporte de Calidad y Oportunidad (Turnaround Time - TAT) en formato Excel.
     *
     * @param  array  $data  Datos filtrados: sedeId, fechaIni, fechaFin, modalidad.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function generateReporteTAT(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);
        $fechaInicio = $data['fechaIni'] ?? null;
        $fechaFin = $data['fechaFin'] ?? null;
        $modalidad = $this->normalizeNullable($data['modalidad'] ?? null);

        try {
            return Excel::download(
                new ReporteTATExport($fechaInicio, $fechaFin, $sedeId, $modalidad),
                'reporte_oportunidad_tat.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteTAT failed', [
                'sede_id' => $sedeId,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'modalidad' => $modalidad,
                'error' => $e->getMessage(),
            ]);

            return ['message' => 'Error al generar el reporte de oportunidad TAT: '.$e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Genera el reporte de salud y telemetría de agentes hospitalarios en Excel.
     *
     * @param  array  $data  Datos filtrados: sedeId opcional.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function generateReporteAgentes(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);

        try {
            return Excel::download(
                new ReporteAgentesTelemetriaExport($sedeId),
                'reporte_agentes_telemetria.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteAgentes failed', [
                'sede_id' => $sedeId,
                'error' => $e->getMessage(),
            ]);

            return ['message' => 'Error al generar el reporte de agentes: '.$e->getMessage(), 'status' => 500];
        }
    }
}
