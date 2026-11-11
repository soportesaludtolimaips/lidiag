<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pacientes\PacienteIndexRequest;
use App\Http\Requests\Pacientes\PacienteStoreRequest;
use App\Http\Requests\Pacientes\PacienteUpdateRequest;
use App\Http\Requests\Pacientes\WorklistCancelarRequest;
use App\Http\Requests\Pacientes\WorklistEnviarRequest;
use App\Models\Configuracion\ConfigSede;
use App\Services\Dcm4chee\WorklistService;
use App\Services\Pacientes\PacienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para la gestión y admisión de pacientes y emisión de órdenes a la Worklist DICOM.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class PacienteController extends Controller
{
    /**
     * Inyecta los servicios de pacientes y worklist y define permisos por método.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function __construct(
        private readonly PacienteService $pacienteService,
        private readonly WorklistService $worklistService,
    ) {
        $this->middleware('permission:gestion.pacientes.index')->only([
            'listar', 'show', 'obtenerCatalogos', 'obtenerAccessionSugerido', 'obtenerEstacionesWorklist',
        ]);
        $this->middleware('permission:gestion.pacientes.create')->only([
            'store', 'enviarWorklist',
        ]);
        $this->middleware('permission:gestion.pacientes.update')->only([
            'update', 'cancelarWorklist',
        ]);
        $this->middleware('permission:gestion.pacientes.delete')->only([
            'destroy',
        ]);
    }

    /**
     * Lista los pacientes filtrados y mapea su estado de Worklist de la sede activa.
     */
    public function listar(PacienteIndexRequest $request): JsonResponse
    {
        $result = $this->pacienteService->listar(
            $request->validated(),
            (int) $request->input('sede_id')
        );

        return response()->json($result, (int) $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Registra un nuevo paciente en el sistema.
     */
    public function store(PacienteStoreRequest $request): JsonResponse
    {
        $result = $this->pacienteService->store($request->validated());

        return response()->json($result, (int) $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Muestra la información de un paciente específico.
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->pacienteService->show($id);

        return response()->json($result, (int) $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Actualiza la información demográfica del paciente.
     */
    public function update(PacienteUpdateRequest $request, int $id): JsonResponse
    {
        $result = $this->pacienteService->update($request->validated(), $id);

        return response()->json($result, (int) $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Elimina un paciente si no cuenta con registros clínicos asociados.
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->pacienteService->destroy($id);

        return response()->json($result, (int) $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Retorna los catálogos auxiliares (tipos de documento y productos de Rayos X).
     */
    public function obtenerCatalogos(): JsonResponse
    {
        $result = $this->pacienteService->obtenerCatalogosAuxiliares();

        return response()->json($result, (int) $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Genera un Accession Number sugerido para una sede determinada.
     */
    public function obtenerAccessionSugerido(Request $request): JsonResponse
    {
        $sedeId = (int) $request->input('sede_id');
        if ($sedeId <= 0) {
            $sedeId = (int) (session('sede_id')
                ?? auth()->user()?->sedes()?->first()?->id
                ?? ConfigSede::where('estado', true)->value('id'));
        }

        if ($sedeId <= 0) {
            return response()->json([
                'status' => 422,
                'message' => 'El ID de sede es requerido para generar el consecutivo.',
            ], 422);
        }

        $accessionNo = $this->worklistService->generarAccessionSugerido($sedeId);

        return response()->json([
            'status' => 200,
            'data' => [
                'accession_no' => $accessionNo,
            ],
        ]);
    }

    /**
     * Obtiene las estaciones o equipos de adquisición DICOM configurados en el PACS de la sede.
     */
    public function obtenerEstacionesWorklist(Request $request): JsonResponse
    {
        $sedeId = (int) ($request->query('sede_id') ?: $request->input('sede_id'));
        if (! $sedeId) {
            return response()->json([
                'status' => 422,
                'message' => 'La sede es obligatoria para consultar los equipos de Worklist.',
            ], 422);
        }

        $result = $this->worklistService->obtenerEstacionesWorklist($sedeId);

        return response()->json($result, (int) $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Envía un paciente admitido a la Modality Worklist DICOM de la sede.
     */
    public function enviarWorklist(WorklistEnviarRequest $request): JsonResponse
    {
        $usuarioId = (int) (auth()->id() ?: 1);
        $result = $this->worklistService->enviarAWorklist($request->validated(), $usuarioId);

        return response()->json($result, (int) $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Retira o cancela una orden activa de la Worklist.
     */
    public function cancelarWorklist(WorklistCancelarRequest $request): JsonResponse
    {
        $usuarioId = (int) (auth()->id() ?: 1);
        $result = $this->worklistService->cancelarOrdenWorklist((int) $request->input('orden_id'), $usuarioId);

        return response()->json($result, (int) $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Reconcilia las órdenes de Worklist con los estudios recibidos en el PACS Dcm4chee.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function reconciliarWorklist(Request $request): JsonResponse
    {
        $sedeId = $request->input('sede_id') ? (int) $request->input('sede_id') : null;
        $result = $this->worklistService->reconciliarOrdenes($sedeId);

        return response()->json($result, (int) $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
