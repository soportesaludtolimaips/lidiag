<?php

namespace App\Http\Controllers\Oftalmologia;

use App\Http\Controllers\Controller;
use App\Http\Requests\Oftalmologia\OftalmologiaConsultaStoreRequest;
use App\Models\Configuracion\ConfigDiagnostico;
use App\Models\Configuracion\ConfigProducto;
use App\Services\Interfaces\SAHIOftalmologiaService;
use App\Services\Oftalmologia\OftalmologiaConsultaService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador para la consulta médica e historia clínica de Oftalmología.
 *
 * Flujo del proceso:
 * 1. Obtiene la historia clínica actual ligada a una atención.
 * 2. Guarda el borrador o finaliza la consulta clínica con diagnósticos CIE-10.
 * 3. Transmite o sincroniza con SAHI si la atención lo requiere.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaConsultaController extends Controller
{
    public function __construct(
        protected OftalmologiaConsultaService $consultaService,
        protected SAHIOftalmologiaService $sahiOftalmologiaService
    ) {
        $this->middleware('permission:oftalmologia.consulta');
        $this->middleware('permission:oftalmologia.sahi.sincronizar')->only(['sincronizarSahi']);
    }

    /**
     * Obtiene los datos clínicos de una atención de oftalmología.
     */
    public function show(int $atencionId): JsonResponse
    {
        $resultado = $this->consultaService->obtenerPorAtencion($atencionId);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }

    /**
     * Guarda la consulta de oftalmología (borrador o finalización).
     */
    public function store(OftalmologiaConsultaStoreRequest $request): JsonResponse
    {
        $usuarioId = (int) $request->user()->id;
        $finalizar = (bool) $request->input('finalizar', false);

        $resultado = $this->consultaService->guardarConsulta($request->validated(), $usuarioId, $finalizar);

        // Si se finaliza exitosamente y debe transmitir a SAHI, se dispara la transmisión
        if (($resultado['success'] ?? false) && $finalizar) {
            $atencionId = (int) $request->input('atencion_id');
            $this->sahiOftalmologiaService->transmitirConsultaASAHI($atencionId);
        }

        return response()->json($resultado, $resultado['code'] ?? 200);
    }

    /**
     * Reintenta o sincroniza manualmente la consulta con SAHI.
     */
    public function sincronizarSahi(int $atencionId): JsonResponse
    {
        $resultado = $this->sahiOftalmologiaService->transmitirConsultaASAHI($atencionId);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }

    /**
     * Búsqueda ágil de diagnósticos CIE-10 para la historia clínica.
     */
    public function buscarDiagnosticos(string $termino): JsonResponse
    {
        $termino = trim($termino);
        $diagnosticos = ConfigDiagnostico::query()
            ->where('cod_diagnos', 'like', "%{$termino}%")
            ->orWhere('nom_diagnos', 'like', "%{$termino}%")
            ->take(20)
            ->get(['id', 'cod_diagnos', 'nom_diagnos']);

        return response()->json(['success' => true, 'data' => $diagnosticos]);
    }

    /**
     * Búsqueda ágil de productos/procedimientos/fármacos para órdenes oftálmicas.
     */
    public function buscarProductos(string $termino): JsonResponse
    {
        $termino = trim($termino);
        $productos = ConfigProducto::query()
            ->where('cod_cups', 'like', "%{$termino}%")
            ->orWhere('nom_produc', 'like', "%{$termino}%")
            ->take(20)
            ->get(['id', 'cod_cups', 'nom_produc']);

        return response()->json(['success' => true, 'data' => $productos]);
    }
}
