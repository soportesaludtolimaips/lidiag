<?php

namespace App\Http\Controllers\Liquidacion;

use App\Http\Controllers\Controller;
use App\Services\Liquidacion\LiquidacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $this->middleware("permission:liquidacion.index")->only(["calcular"]);
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
        $validated = $request->validate([
            "sede_id" => "required|integer|exists:config_sedes,id",
            "fecha_inicio" => "required|date",
            "fecha_fin" => "required|date|after_or_equal:fecha_inicio",
        ]);

        $result = $this->liquidacionService->calcular(
            (int) $validated["sede_id"],
            $validated["fecha_inicio"],
            $validated["fecha_fin"]
        );

        return response()->json($result, $result["status"]);
    }
}
