<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\ReporteEstudiosPACRequest;
use App\Http\Requests\Reportes\ReporteProduccionRequest;
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
        $this->middleware('permission:reportes.detallados|reportes.indicadores')->only(['reporteProduccionDetallado', 'reporteEstudiosPAC']);
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
}
