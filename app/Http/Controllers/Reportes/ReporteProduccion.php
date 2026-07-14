<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\ReporteProduccionRequest;
use App\Services\Reportes\ReporteProduccionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteProduccion extends Controller
{
    public function __construct(
        private readonly ReporteProduccionService $service,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:reportes.detallados|reportes.indicadores')->only(['reporteProduccionDetallado']);
    }

    public function reporteProduccionDetallado(ReporteProduccionRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->generateReporteDetallado($request->validated());

        if ($result instanceof BinaryFileResponse) {
            return $result;
        }

        return response()->json($result['message'], $result['status']);
    }
}
