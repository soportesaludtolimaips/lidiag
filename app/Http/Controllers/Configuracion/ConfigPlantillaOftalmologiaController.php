<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Services\Configuracion\ConfigPlantillaOftalmologiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para las plantillas clínicas de Oftalmología en el módulo de Configuración.
 *
 * Flujo del proceso:
 * 1. Lista las plantillas disponibles por sección o completas.
 * 2. Provee endpoints para crear, actualizar y eliminar plantillas.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class ConfigPlantillaOftalmologiaController extends Controller
{
    public function __construct(
        protected ConfigPlantillaOftalmologiaService $plantillaService
    ) {
        $this->middleware('permission:config.plantillas.index')->only(['index', 'listarPorSeccion']);
        $this->middleware('permission:config.plantillas.create')->only(['store']);
        $this->middleware('permission:config.plantillas.edit')->only(['update']);
        $this->middleware('permission:config.plantillas.destroy')->only(['destroy']);
    }

    /**
     * Lista todas las plantillas registradas.
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->only(['seccion', 'medico_id', 'activo']);
        $resultado = $this->plantillaService->all($filtros);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }

    /**
     * Lista plantillas activas filtradas por sección para ser usadas en la consulta médica.
     */
    public function listarPorSeccion(Request $request, string $seccion): JsonResponse
    {
        $medicoId = $request->user()?->id;
        $resultado = $this->plantillaService->listarPorSeccion($seccion, $medicoId);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }

    /**
     * Guarda una nueva plantilla de oftalmología.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seccion' => 'required|string|in:EXAMEN_FISICO,CONCEPTO_PLAN,PROCEDIMIENTOS,MEDICAMENTOS,ANAMNESIS',
            'titulo' => 'required|string|max:150',
            'contenido' => 'required|string',
            'sede_id' => 'nullable|integer|exists:config_sedes,id',
            'es_institucional' => 'sometimes|boolean',
            'activo' => 'sometimes|boolean',
        ]);

        $usuarioId = (int) $request->user()->id;
        $resultado = $this->plantillaService->crear($data, $usuarioId);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }

    /**
     * Actualiza una plantilla existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'seccion' => 'sometimes|string|in:EXAMEN_FISICO,CONCEPTO_PLAN,PROCEDIMIENTOS,MEDICAMENTOS,ANAMNESIS',
            'titulo' => 'sometimes|string|max:150',
            'contenido' => 'sometimes|string',
            'sede_id' => 'nullable|integer|exists:config_sedes,id',
            'activo' => 'sometimes|boolean',
        ]);

        $resultado = $this->plantillaService->actualizar($id, $data);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }

    /**
     * Elimina una plantilla.
     */
    public function destroy(int $id): JsonResponse
    {
        $resultado = $this->plantillaService->eliminar($id);

        return response()->json($resultado, $resultado['code'] ?? 200);
    }
}
