<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\HelpDesk\HelpDeskReporteRequest;
use App\Services\HelpDesk\HelpDeskReporteService;
use Illuminate\Http\JsonResponse;

class HelpDeskReportesController extends Controller
{
    public function __construct(
        private readonly HelpDeskReporteService $reporteService,
    ) {}

    public function indicadores(HelpDeskReporteRequest $request): JsonResponse
    {
        $filters = $this->reporteService->normalizeFilters($request->validated());
        $result = $this->reporteService->indicadores($filters);

        return response()->json($result, $result["status"]);
    }

    public function detallado(HelpDeskReporteRequest $request): JsonResponse
    {
        $filters = $this->reporteService->normalizeFilters($request->validated());
        $limit = (int) ($request->input('limit') ?? 500);
        $result = $this->reporteService->detallado($filters, $limit);

        return response()->json($result, $result["status"]);
    }

    public function exportExcel(HelpDeskReporteRequest $request)
    {
        $filters = $this->reporteService->normalizeFilters($request->validated());
        $filters['limit'] = (int) ($request->input('limit') ?? 5000);

        return $this->reporteService->exportExcel($filters);
    }

    public function exportPdf(HelpDeskReporteRequest $request)
    {
        $filters = $this->reporteService->normalizeFilters($request->validated());

        return $this->reporteService->exportPdf($filters);
    }
}
