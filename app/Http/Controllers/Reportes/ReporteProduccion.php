<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\ReporteAgentesRequest;
use App\Http\Requests\Reportes\ReporteAusentismoCitasRequest;
use App\Http\Requests\Reportes\ReporteEstudiosPACRequest;
use App\Http\Requests\Reportes\ReporteProduccionRequest;
use App\Http\Requests\Reportes\ReporteTATRequest;
use App\Http\Requests\Reportes\ReporteTeleRadiologiaRequest;
use App\Services\Reportes\ReporteProduccionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteProduccion extends Controller
{
    /**
     * Constructor del controlador de reportes de producción.
     *
     * @param  ReporteProduccionService  $service  Servicio de generación de reportes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function __construct(
        private readonly ReporteProduccionService $service,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:reportes.detallados|reportes.indicadores')->only([
            'reporteProduccionDetallado',
            'reporteEstudiosPAC',
            'reporteTeleRadiologia',
            'reporteAusentismoCitas',
        ]);
    }

    /**
     * Genera el reporte detallado de producción en formato Excel o error JSON.
     *
     * @param  ReporteProduccionRequest  $request  Solicitud con filtros del reporte.
     *
     * @returns BinaryFileResponse|JsonResponse Archivo Excel o respuesta JSON.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function reporteProduccionDetallado(ReporteProduccionRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteDetallado($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }

    /**
     * Genera el reporte de estudios tomados en PAC en formato Excel o error JSON.
     *
     * @param  ReporteEstudiosPACRequest  $request  Solicitud con sede y rango de fechas.
     *
     * @returns BinaryFileResponse|JsonResponse Archivo Excel o respuesta JSON.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function reporteEstudiosPAC(ReporteEstudiosPACRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteEstudiosPAC($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }

    /**
     * Genera el reporte de estudios de tele-radiología en formato Excel o error JSON.
     *
     * @param  ReporteTeleRadiologiaRequest  $request  Solicitud con filtros de tele-radiología.
     *
     * @returns BinaryFileResponse|JsonResponse Archivo Excel o respuesta JSON.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function reporteTeleRadiologia(ReporteTeleRadiologiaRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteTeleRadiologia($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }

    /**
     * Genera el reporte de ausentismo y cancelaciones de citas en formato Excel o error JSON.
     *
     * @param  ReporteAusentismoCitasRequest  $request  Solicitud con filtros de ausentismo.
     *
     * @returns BinaryFileResponse|JsonResponse Archivo Excel o respuesta JSON.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function reporteAusentismoCitas(ReporteAusentismoCitasRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteAusentismoCitas($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }

    /**
     * Descarga el reporte de Calidad y Oportunidad (Turnaround Time - TAT) en formato Excel.
     *
     * Flujo del proceso:
     * 1. Valida los filtros (fechas, sede, modalidad) con ReporteTATRequest.
     * 2. Delega al servicio la generación del Excel con métricas de tiempo de ciclo.
     * 3. Devuelve la descarga binaria o respuesta JSON defensiva en error.
     *
     * @param  ReporteTATRequest  $request  Petición validada con filtros.
     *
     * @returns BinaryFileResponse|JsonResponse Descarga directa del archivo Excel o error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function reporteTAT(ReporteTATRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteTAT($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }

    /**
     * Descarga el reporte de Salud y Telemetría de Agentes Periféricos en formato Excel.
     *
     * Flujo del proceso:
     * 1. Valida los filtros (sede opcional) con ReporteAgentesRequest.
     * 2. Delega al servicio la generación del consolidado de backups y telemetría de agentes.
     * 3. Devuelve la descarga binaria o error JSON.
     *
     * @param  ReporteAgentesRequest  $request  Petición validada con sede opcional.
     *
     * @returns BinaryFileResponse|JsonResponse Descarga directa del archivo Excel o error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function reporteAgentes(ReporteAgentesRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteAgentes($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }
}
