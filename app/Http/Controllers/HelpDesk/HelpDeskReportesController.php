<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\HelpDesk\HelpDeskReporteRequest;
use App\Services\HelpDesk\HelpDeskReporteService;
use Illuminate\Http\JsonResponse;

class HelpDeskReportesController extends Controller
{
    /**
     * Construye el controlador inyectando el servicio de reportes del help desk.
     *
     * @param  HelpDeskReporteService  $reporteService  servicio encargado de la lógica de reportes.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function __construct(
        private readonly HelpDeskReporteService $reporteService,
    ) {}

    /**
     * Genera los indicadores agregados de los tickets según los filtros validados.
     *
     * @param  HelpDeskReporteRequest  $request  petición validada con los filtros del reporte.
     *
     * @returns JsonResponse respuesta JSON con los indicadores calculados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function indicadores(HelpDeskReporteRequest $request): JsonResponse
    {
        $filters = $this->reporteService->normalizeFilters($request->validated());
        $result = $this->reporteService->indicadores($filters);

        return response()->json($result, $result["status"]);
    }

    /**
     * Genera el listado detallado de tickets según los filtros validados.
     *
     * @param  HelpDeskReporteRequest  $request  petición validada con los filtros del reporte.
     *
     * @returns JsonResponse respuesta JSON con los tickets detallados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function detallado(HelpDeskReporteRequest $request): JsonResponse
    {
        $filters = $this->reporteService->normalizeFilters($request->validated());
        $limit = (int) ($request->input('limit') ?? 500);
        $result = $this->reporteService->detallado($filters, $limit);

        return response()->json($result, $result["status"]);
    }

    /**
     * Exporta el reporte detallado de tickets a un archivo de Excel.
     *
     * @param  HelpDeskReporteRequest  $request  petición validada con los filtros del reporte.
     *
     * @returns \Symfony\Component\HttpFoundation\BinaryFileResponse descarga del archivo Excel generado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function exportExcel(HelpDeskReporteRequest $request)
    {
        $filters = $this->reporteService->normalizeFilters($request->validated());
        $filters['limit'] = (int) ($request->input('limit') ?? 5000);

        return $this->reporteService->exportExcel($filters);
    }

    /**
     * Exporta los indicadores del reporte a un archivo PDF.
     *
     * @param  HelpDeskReporteRequest  $request  petición validada con los filtros del reporte.
     *
     * @returns \Symfony\Component\HttpFoundation\BinaryFileResponse descarga del archivo PDF generado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function exportPdf(HelpDeskReporteRequest $request)
    {
        $filters = $this->reporteService->normalizeFilters($request->validated());

        return $this->reporteService->exportPdf($filters);
    }
}
