<?php

namespace App\Http\Controllers\Liquidacion;

use App\Exports\LiquidacionExport;
use App\Http\Controllers\Controller;
use App\Services\Liquidacion\LiquidacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $this->middleware("permission:liquidacion.exportar")->only(["exportar"]);
    }

    /**
     * Calcula la liquidación de pagos a médicos de una sede en un rango de fechas.
     *
     * Flujo del proceso:
     * 1. Valida sede_id contra config_sedes y el rango de fechas.
     * 2. Delega el cálculo a LiquidacionService::calcular().
     * 3. Devuelve líneas, exclusiones y totales con el estado del servicio.
     *
     * @param  Request  $request  Petición con sede_id, fecha_inicio y fecha_fin.
     *
     * @returns JsonResponse Líneas liquidables, exclusiones con conteo y totales por médico.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function calcular(Request $request): JsonResponse
    {
        $validated = $this->validarFiltros($request);

        $result = $this->liquidacionService->calcular(
            (int) $validated["sede_id"],
            $validated["fecha_inicio"],
            $validated["fecha_fin"],
            isset($validated["contrato_id"]) ? (int) $validated["contrato_id"] : null,
            isset($validated["medico_id"]) ? (int) $validated["medico_id"] : null
        );

        return response()->json($result, $result["status"]);
    }

    /**
     * Descarga la liquidación de un periodo en Excel.
     *
     * Flujo del proceso:
     * 1. Valida sede_id contra config_sedes y el rango de fechas.
     * 2. Construye el export con los mismos filtros que calcular.
     * 3. Devuelve la descarga del xlsx generada por Maatwebsite Excel.
     *
     * @param  Request  $request  Mismos filtros que calcular.
     *
     * @returns \Symfony\Component\HttpFoundation\BinaryFileResponse Archivo xlsx.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function exportar(Request $request): BinaryFileResponse
    {
        $validated = $this->validarFiltros($request);

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
     * 1. Valida sede_id y periodo en formato YYYY-MM.
     * 2. Delega a LiquidacionService::productividad() (lee lo congelado).
     * 3. Devuelve médicos con detalle, totales y gran total.
     *
     * @param  Request  $request  Petición con sede_id y periodo.
     *
     * @returns JsonResponse Productividad del periodo por médico.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-01
     */
    public function productividad(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "sede_id" => "required|integer|exists:config_sedes,id",
            "periodo" => ["required", "regex:/^\\d{4}-(0[1-9]|1[0-2])$/"],
        ]);

        $result = $this->liquidacionService->productividad((int) $validated["sede_id"], $validated["periodo"]);

        return response()->json($result, $result["status"]);
    }

    /**
     * Valida los filtros compartidos de calcular y exportar.
     *
     * Flujo del proceso:
     * 1. Exige sede_id existente en config_sedes.
     * 2. Exige fecha_inicio y fecha_fin con fin posterior o igual al inicio.
     * 3. Acepta contrato_id y medico_id opcionales (deben existir si vienen).
     *
     * @param  Request  $request  Petición con sede_id, fecha_inicio, fecha_fin y opcionales.
     *
     * @returns array{sede_id: int, fecha_inicio: string, fecha_fin: string} Filtros validados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    private function validarFiltros(Request $request): array
    {
        return $request->validate([
            "sede_id" => "required|integer|exists:config_sedes,id",
            "fecha_inicio" => "required|date",
            "fecha_fin" => "required|date|after_or_equal:fecha_inicio",
            "contrato_id" => "sometimes|integer|exists:contratos,id",
            "medico_id" => "sometimes|integer|exists:users,id",
        ]);
    }
}
