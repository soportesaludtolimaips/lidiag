<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\ReporteAgentesRequest;
use App\Http\Requests\Reportes\ReporteAlmacenamientoRequest;
use App\Http\Requests\Reportes\ReporteAusentismoCitasRequest;
use App\Http\Requests\Reportes\ReporteCitasWhatsAppRequest;
use App\Http\Requests\Reportes\ReporteConsolidadoRedRequest;
use App\Http\Requests\Reportes\ReporteEstudiosPACRequest;
use App\Http\Requests\Reportes\ReporteProduccionConsolidadaRequest;
use App\Http\Requests\Reportes\ReporteProduccionRequest;
use App\Http\Requests\Reportes\ReporteRepresadosRequest;
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
            'reporteProduccionConsolidada',
            'reporteEstudiosPAC',
            'reporteTeleRadiologia',
            'reporteAusentismoCitas',
            'reporteTAT',
            'reporteAgentes',
            'reporteConsolidadoRed',
            'reporteRepresados',
            'reporteCitasWhatsApp',
            'reporteAlmacenamiento',
            'apiResumenRed',
            'apiSemaforoRepresados',
            'apiCitasWhatsApp',
            'apiAlmacenamiento',
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
     * Genera el reporte de producción consolidada (Estudios Tomados PACS vs Asignados LIDiag).
     *
     * @param  ReporteProduccionConsolidadaRequest  $request  Solicitud con filtros del reporte consolidado.
     *
     * @returns BinaryFileResponse|JsonResponse Archivo Excel o respuesta JSON.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-09
     */
    public function reporteProduccionConsolidada(ReporteProduccionConsolidadaRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteProduccionConsolidada($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'] ?? 'Error desconocido', $result['status'] ?? 500);
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

    /**
     * Devuelve el resumen analítico interactivo del consolidado de la red hospitalaria.
     *
     * @param  ReporteConsolidadoRedRequest  $request  Petición validada con período o rango.
     * @return JsonResponse Métricas del consolidado de la red.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function apiResumenRed(ReporteConsolidadoRedRequest $request): JsonResponse
    {
        return response()->json($this->service->obtenerResumenConsolidadoRed($request->validated()));
    }

    /**
     * Devuelve el semáforo asistencial de estudios represados (>24h) y urgencias en tiempo real.
     *
     * @param  ReporteRepresadosRequest  $request  Petición con filtro opcional de sede.
     * @return JsonResponse Semáforo de represamientos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function apiSemaforoRepresados(ReporteRepresadosRequest $request): JsonResponse
    {
        return response()->json($this->service->obtenerSemaforoRepresados($request->validated()));
    }

    /**
     * Devuelve las estadísticas de citas agendadas y tráfico del canal WhatsApp.
     *
     * @param  ReporteCitasWhatsAppRequest  $request  Petición con período y sede opcional.
     * @return JsonResponse Estadísticas de citas y WhatsApp.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function apiCitasWhatsApp(ReporteCitasWhatsAppRequest $request): JsonResponse
    {
        return response()->json($this->service->obtenerEstadisticasCitas($request->validated()));
    }

    /**
     * Devuelve el estado de almacenamiento del Servidor Central y la telemetría de sedes.
     *
     * @param  ReporteAlmacenamientoRequest  $request  Petición con filtro de sede opcional.
     * @return JsonResponse Estado de almacenamiento y servidores.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function apiAlmacenamiento(ReporteAlmacenamientoRequest $request): JsonResponse
    {
        return response()->json($this->service->obtenerMonitorAlmacenamiento($request->validated()));
    }

    /**
     * Descarga el reporte consolidado de producción de toda la red en formato Excel.
     *
     * @param  ReporteConsolidadoRedRequest  $request  Petición con filtros de período.
     * @return BinaryFileResponse|JsonResponse Descarga Excel o error JSON.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function reporteConsolidadoRed(ReporteConsolidadoRedRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteConsolidadoRed($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }

    /**
     * Descarga el semáforo asistencial de estudios represados y urgencias en Excel.
     *
     * @param  ReporteRepresadosRequest  $request  Petición con filtro de sede opcional.
     * @return BinaryFileResponse|JsonResponse Descarga Excel o error JSON.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function reporteRepresados(ReporteRepresadosRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteRepresados($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }

    /**
     * Descarga el reporte de citas y canal WhatsApp en formato Excel.
     *
     * @param  ReporteCitasWhatsAppRequest  $request  Petición con filtros de citas.
     * @return BinaryFileResponse|JsonResponse Descarga Excel o error JSON.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function reporteCitasWhatsApp(ReporteCitasWhatsAppRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteCitasWhatsApp($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }

    /**
     * Descarga el monitor de almacenamiento y servidores PACS en formato Excel.
     *
     * @param  ReporteAlmacenamientoRequest  $request  Petición con filtro opcional de sede.
     * @return BinaryFileResponse|JsonResponse Descarga Excel o error JSON.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function reporteAlmacenamiento(ReporteAlmacenamientoRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteAlmacenamiento($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }
}
