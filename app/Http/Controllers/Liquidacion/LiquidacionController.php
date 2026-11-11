<?php

namespace App\Http\Controllers\Liquidacion;

use App\Exports\LiquidacionExport;
use App\Exports\ProductividadExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Liquidacion\LiquidacionFiltroRequest;
use App\Http\Requests\Liquidacion\LiquidacionProductividadRequest;
use App\Services\Liquidacion\LiquidacionService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller LiquidacionController: API de liquidación de pagos a médicos.
 *
 * Expone el cálculo de la liquidación por sede y rango de fechas que usa
 * el frontend y reutiliza la exportación a Excel. La única fuente del
 * cálculo es LiquidacionService::calcular().
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
class LiquidacionController extends Controller
{
    /**
     * Constructor del controller. Inyecta el servicio de liquidación
     * y configura los middlewares de autenticación y permisos.
     *
     * Flujo del proceso:
     * 1. Recibe el servicio de liquidación por inyección de dependencias.
     * 2. Exige autenticación en todos los métodos.
     * 3. Exige el permiso liquidacion.index para calcular.
     * 4. Exige el permiso liquidacion.exportar para exportar.
     *
     * @param  LiquidacionService  $liquidacionService  Servicio de lógica de negocio para liquidación.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function __construct(
        private readonly LiquidacionService $liquidacionService,
    ) {
        $this->middleware("auth");
        $this->middleware("permission:liquidacion.index")->only(["calcular", "productividad"]);
        $this->middleware("permission:liquidacion.exportar")->only(["exportar", "exportarProductividad"]);
    }

    /**
     * Calcula la liquidación de pagos a médicos de una sede en un rango de fechas.
     *
     * Flujo del proceso:
     * 1. Valida los filtros mediante LiquidacionFiltroRequest.
     * 2. Delega el cálculo a LiquidacionService::calcular().
     * 3. Devuelve líneas, exclusiones y totales con el estado del servicio.
     *
     * @param  LiquidacionFiltroRequest  $request  Petición validada con sede_id, fecha_inicio, fecha_fin y opcionales.
     *
     * @returns JsonResponse Líneas liquidables, exclusiones con conteo y totales por médico.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function calcular(LiquidacionFiltroRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->liquidacionService->calcular(
            (int) $validated["sede_id"],
            $validated["fecha_inicio"],
            $validated["fecha_fin"],
            isset($validated["contrato_id"]) ? (int) $validated["contrato_id"] : null,
            isset($validated["medico_id"]) ? (int) $validated["medico_id"] : null
        );

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Descarga la liquidación de un periodo en Excel.
     *
     * Flujo del proceso:
     * 1. Valida los filtros mediante LiquidacionFiltroRequest.
     * 2. Construye el export con los filtros validados.
     * 3. Devuelve la descarga del xlsx generada por Maatwebsite Excel.
     *
     * @param  LiquidacionFiltroRequest  $request  Mismos filtros validados que calcular.
     *
     * @returns \Symfony\Component\HttpFoundation\BinaryFileResponse Archivo xlsx.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function exportar(LiquidacionFiltroRequest $request): BinaryFileResponse
    {
        $validated = $request->validated();

        return Excel::download(
            new LiquidacionExport(
                (int) $validated["sede_id"],
                $validated["fecha_inicio"],
                $validated["fecha_fin"],
                isset($validated["contrato_id"]) ? (int) $validated["contrato_id"] : null,
                isset($validated["medico_id"]) ? (int) $validated["medico_id"] : null
            ),
            "liquidacion.xlsx"
        );
    }

    /**
     * Reporte de productividad por médico para el corte de mes.
     *
     * Flujo del proceso:
     * 1. Valida sede_id y periodo en formato YYYY-MM mediante LiquidacionProductividadRequest.
     * 2. Delega a LiquidacionService::productividad() (lee lo congelado).
     * 3. Devuelve médicos con detalle, totales y gran total.
     *
     * @param  LiquidacionProductividadRequest  $request  Petición validada con sede_id y periodo.
     *
     * @returns JsonResponse Productividad del periodo por médico.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-01
     */
    public function productividad(LiquidacionProductividadRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->liquidacionService->productividad((int) $validated["sede_id"], $validated["periodo"]);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Descarga el reporte de productividad médica de un periodo mensual en Excel.
     *
     * Flujo del proceso:
     * 1. Valida los filtros (sede_id y periodo YYYY-MM) mediante LiquidacionProductividadRequest.
     * 2. Construye el exportador con los filtros validados.
     * 3. Devuelve la descarga del archivo xlsx generado por Maatwebsite Excel.
     *
     * @param  LiquidacionProductividadRequest  $request  Petición validada con sede_id y periodo.
     * @return BinaryFileResponse Archivo xlsx de productividad médica.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function exportarProductividad(LiquidacionProductividadRequest $request): BinaryFileResponse
    {
        $validated = $request->validated();

        $nombreArchivo = "productividad_{$validated['periodo']}_sede_{$validated['sede_id']}.xlsx";

        return Excel::download(
            new ProductividadExport((int) $validated["sede_id"], $validated["periodo"]),
            $nombreArchivo
        );
    }
}
