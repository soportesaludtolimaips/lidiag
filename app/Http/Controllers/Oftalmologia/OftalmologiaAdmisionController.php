<?php

namespace App\Http\Controllers\Oftalmologia;

use App\Http\Controllers\Controller;
use App\Http\Requests\Oftalmologia\OftalmologiaAdmisionRequest;
use App\Services\Interfaces\SAHIOftalmologiaService;
use App\Services\Oftalmologia\OftalmologiaAdmisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para las admisiones del módulo de Oftalmología.
 *
 * Flujo del proceso:
 * 1. Controla el listado y filtrado de pacientes en sala / consulta.
 * 2. Consulta de forma segura atenciones externas en SAHI si la sede cuenta con interfaz.
 * 3. Ejecuta la admisión directa o institucional.
 * 4. Gestiona la anulación de atenciones.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaAdmisionController extends Controller
{
    public function __construct(
        protected OftalmologiaAdmisionService $admisionService,
        protected SAHIOftalmologiaService $sahiOftalmologiaService
    ) {
        $this->middleware('permission:oftalmologia.index')->only(['index', 'listarSahi']);
        $this->middleware('permission:oftalmologia.admision')->only(['store']);
        $this->middleware('permission:oftalmologia.anular')->only(['anular']);
    }

    /**
     * Lista las atenciones registradas de oftalmología en LIDiag.
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = [
            'sede_id' => $request->input('sede_id', $request->query('sede_id')),
            'estado' => $request->input('estado', $request->query('estado')),
            'fecha_ini' => $request->input('fecha_ini', $request->input('bus_fech_ini', $request->query('fecha_ini'))),
            'fecha_fin' => $request->input('fecha_fin', $request->input('bus_fecha_fin', $request->query('fecha_fin'))),
            'busqueda' => $request->input('busqueda', $request->input('bus_nom_num_docu', $request->query('busqueda'))),
            'per_page' => $request->input('per_page', $request->query('per_page', 15)),
        ];

        $resultado = $this->admisionService->listarAtenciones($filtros);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }

    /**
     * Consulta atenciones disponibles de Oftalmología en SAHI (SQL Server).
     */
    public function listarSahi(Request $request): JsonResponse
    {
        $sedeId = (int) ($request->input('sede_id', $request->query('sede_id')));
        if (! $sedeId) {
            return response()->json([
                'ok' => false,
                'message' => 'Debe indicar la sede para consultar en SAHI.',
            ], 422);
        }

        $filtros = [
            'fecha_ini' => $request->input('fecha_ini', $request->input('bus_fech_ini', $request->query('fecha_ini'))),
            'fecha_fin' => $request->input('fecha_fin', $request->input('bus_fecha_fin', $request->query('fecha_fin'))),
            'busqueda' => $request->input('busqueda', $request->input('bus_nom_num_docu', $request->query('busqueda'))),
            'atencion_id' => $request->input('atencion_id', $request->query('atencion_id')),
        ];

        $resultado = $this->sahiOftalmologiaService->listarAtencionesDisponibles($sedeId, $filtros);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }

    /**
     * Registra una nueva admisión de Oftalmología.
     */
    public function store(OftalmologiaAdmisionRequest $request): JsonResponse
    {
        $usuarioId = (int) $request->user()->id;
        $resultado = $this->admisionService->admisionar($request->validated(), $usuarioId);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }

    /**
     * Anula una atención de oftalmología.
     */
    public function anular(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'motivo' => 'required|string|min:4|max:255',
        ], [
            'motivo.required' => 'El motivo de anulación es obligatorio.',
        ]);

        $usuarioId = (int) $request->user()->id;
        $resultado = $this->admisionService->anular($id, (string) $request->input('motivo'), $usuarioId);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }
}
