<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dosimetria\DosimetriaFiltroRequest;
use App\Http\Requests\Dosimetria\DosimetriaStoreRequest;
use App\Services\Dosimetria\DosimetriaService;
use Illuminate\Http\JsonResponse;

class DosimetriaController extends Controller
{
    /**
     * Instancia del servicio de dosimetría radiológica.
     */
    protected DosimetriaService $dosimetriaService;

    /**
     * Constructor con inyección de dependencias y políticas de acceso.
     *
     * Flujo del proceso:
     * 1. Inyecta el servicio de lógica de negocio DosimetriaService.
     * 2. Configura los permisos Spatie para endpoints interactivos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function __construct(DosimetriaService $dosimetriaService)
    {
        $this->dosimetriaService = $dosimetriaService;

        $this->middleware('permission:dosimetria.dashboard')->only(['index']);
    }

    /**
     * Retorna el consolidado analítico de dosimetría para el dashboard clínico.
     *
     * Flujo del proceso:
     * 1. Valida los filtros opcionales de sede, modalidad, métrica y fechas mediante DosimetriaFiltroRequest.
     * 2. Delega el procesamiento analítico y cálculo de NRD al servicio de dosimetría.
     * 3. Retorna la respuesta HTTP tipada en formato JSON con código de estado estandarizado.
     *
     * @param  DosimetriaFiltroRequest  $request  Filtros de consulta clínica
     * @return JsonResponse Métricas agrupadas, alertas NRD y catálogo de sedes
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function index(DosimetriaFiltroRequest $request): JsonResponse
    {
        $result = $this->dosimetriaService->obtenerDashboard($request->validated());

        return response()->json($result, (int) $result['status']);
    }

    /**
     * Almacena un lote masivo de mediciones dosimétricas enviadas por el Agente Edge.
     *
     * Flujo del proceso:
     * 1. Valida la estructura del lote, claves requeridas e integridad mediante DosimetriaStoreRequest.
     * 2. Ejecuta el upsert transaccional en la base de datos evitando colisiones de study_uid.
     * 3. Responde con el número de registros persistidos y código HTTP 201.
     *
     * @param  DosimetriaStoreRequest  $request  Payload en lote de telemetría de radiación
     * @return JsonResponse Confirmación de recepción y métricas guardadas
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function store(DosimetriaStoreRequest $request): JsonResponse
    {
        $sedeId = (int) $request->input('sede_id');
        $registros = (array) $request->input('registros');

        $result = $this->dosimetriaService->guardarLote($sedeId, $registros);

        return response()->json($result, (int) $result['status']);
    }
}
