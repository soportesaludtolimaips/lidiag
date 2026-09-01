<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consentimientos\ConsentimientoEstadisticaRequest;
use App\Services\Consentimientos\ConsentimientoEstadisticaService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsentimientoEstadisticaController extends Controller
{
    public function __construct(
        private readonly ConsentimientoEstadisticaService $service,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:consentimientos.estadisticas,web');
    }

    /**
     * Exporta las estadísticas de consentimientos a un archivo CSV
     * (resumen general + desglose por producto y por médico).
     *
     * @param  ConsentimientoEstadisticaRequest  $request  Filtros validados.
     * @return StreamedResponse Descarga CSV.
     *
     * @author Davide Rivera (Luis Castaño)
     *
     * @date 2026-08-29
     */
    public function exportar(ConsentimientoEstadisticaRequest $request): StreamedResponse
    {
        $filtros = $request->validated();
        $resumen = $this->service->resumen($filtros)['data'];
        $porProducto = $this->service->porProducto($filtros)['data'];
        $porMedico = $this->service->porMedico($filtros)['data'];

        $nombre = 'estadisticas_consentimientos_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($resumen, $porProducto, $porMedico) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel

            fputcsv($out, ['RESUMEN GENERAL']);
            foreach ($resumen as $k => $v) {
                fputcsv($out, [$k, $v]);
            }

            fputcsv($out, []);
            fputcsv($out, ['POR PRODUCTO', 'Total', 'Completos', 'Expirados']);
            foreach ($porProducto as $p) {
                fputcsv($out, [$p->producto?->nom_produc ?? $p->producto_id, $p->total, $p->completos, $p->expirados]);
            }

            fputcsv($out, []);
            fputcsv($out, ['POR MÉDICO', 'Total', 'Pendientes', 'Completos']);
            foreach ($porMedico as $m) {
                fputcsv($out, [$m->medico?->name ?? $m->medico_id, $m->total, $m->pendientes, $m->completos]);
            }

            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function resumen(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->resumen($request->validated());

        return response()->json($result, $result['status']);
    }

    public function porSede(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->porSede($request->validated());

        return response()->json($result, $result['status']);
    }

    public function porProducto(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->porProducto($request->validated());

        return response()->json($result, $result['status']);
    }

    public function porMedico(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->porMedico($request->validated());

        return response()->json($result, $result['status']);
    }

    public function tiemposPromedio(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->tiemposPromedio($request->validated());

        return response()->json($result, $result['status']);
    }

    public function productosPopulares(ConsentimientoEstadisticaRequest $request): JsonResponse
    {
        $result = $this->service->productosPopulares($request->validated());

        return response()->json($result, $result['status']);
    }
}
