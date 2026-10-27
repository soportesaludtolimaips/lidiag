<?php

namespace App\Http\Controllers\Api;

use App\Exports\Dosimetria\DosimetriaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dosimetria\DosimetriaFiltroRequest;
use App\Http\Requests\Dosimetria\DosimetriaStoreRequest;
use App\Services\Dosimetria\DosimetriaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        $this->middleware('permission:dosimetria.dashboard')->only(['index', 'exportar']);
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
        $batchHash = $request->input('batch_hash');

        $result = $this->dosimetriaService->guardarLote($sedeId, $registros, $batchHash);

        return response()->json($result, (int) $result['status']);
    }

    /**
     * Registra el latido periódico (Heartbeat) de conectividad y estado del agente Edge de la sede.
     *
     * Flujo del proceso:
     * 1. Valida el identificador de sede entrante.
     * 2. Guarda el último timestamp de reporte en memoria de alta velocidad.
     * 3. Devuelve confirmación de recepción con código HTTP 200.
     *
     * @param  Request  $request  Petición HTTP con telemetría de latido
     * @return JsonResponse Confirmación de recepción
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'version' => 'sometimes|nullable|string|max:20',
            'pendientes' => 'sometimes|nullable|integer|min:0',
            'estado_cola' => 'sometimes|nullable|string|max:50',
            'detalles' => 'sometimes|nullable|array',
        ]);

        $result = $this->dosimetriaService->registrarHeartbeat((int) $validated['sede_id'], $validated);

        return response()->json($result, (int) $result['status']);
    }

    /**
     * Exporta el informe consolidado y detallado de dosimetría a un archivo Excel multi-hoja.
     *
     * Flujo del proceso:
     * 1. Valida los filtros opcionales de sede, modalidad, métrica y fechas mediante DosimetriaFiltroRequest.
     * 2. Genera el nombre del archivo con timestamp para auditoría técnica.
     * 3. Descarga el archivo Excel mediante Maatwebsite Excel (DosimetriaExport).
     *
     * @param  DosimetriaFiltroRequest  $request  Filtros de consulta clínica
     * @return BinaryFileResponse Descarga del libro Excel estructurado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-26
     */
    public function exportar(DosimetriaFiltroRequest $request): BinaryFileResponse
    {
        $filtros = $request->validated();
        $timestamp = Carbon::now()->format('Ymd_His');
        $nombreArchivo = "Reporte_Dosimetria_Radiologica_{$timestamp}.xlsx";

        return Excel::download(new DosimetriaExport($filtros), $nombreArchivo);
    }
}
