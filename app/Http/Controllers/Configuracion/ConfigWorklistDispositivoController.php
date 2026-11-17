<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigWorklistDispositivoRequest;
use App\Services\Configuracion\ConfigWorklistDispositivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigWorklistDispositivoController extends Controller
{
    /**
     * Constructor con inyección del servicio y políticas Spatie.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function __construct(
        private readonly ConfigWorklistDispositivoService $dispositivoService
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:config.dispositivos.index|ecografias.admision|gestion.pacientes.index')->only(['index', 'show']);
        $this->middleware('permission:config.dispositivos.create')->only(['store']);
        $this->middleware('permission:config.dispositivos.edit')->only(['update', 'toggleEstado']);
        $this->middleware('permission:config.dispositivos.destroy')->only(['destroy']);
    }

    /**
     * Lista los dispositivos (opcionalmente filtrados por sede y modulo).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function index(Request $request): JsonResponse
    {
        $sedeId = $request->query('sede_id') ? (int) $request->query('sede_id') : null;
        $moduloId = $request->query('modulo_id') ? (int) $request->query('modulo_id') : null;
        $result = $this->dispositivoService->listarPorSede($sedeId, $moduloId);

        return response()->json($result, (int) $result['status']);
    }

    /**
     * Muestra el detalle de un dispositivo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->dispositivoService->find($id);

        return response()->json($result, (int) $result['status']);
    }

    /**
     * Almacena un nuevo dispositivo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function store(ConfigWorklistDispositivoRequest $request): JsonResponse
    {
        $result = $this->dispositivoService->create($request->validated());

        return response()->json($result, (int) $result['status']);
    }

    /**
     * Actualiza un dispositivo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function update(ConfigWorklistDispositivoRequest $request, int $id): JsonResponse
    {
        $result = $this->dispositivoService->update($id, $request->validated());

        return response()->json($result, (int) $result['status']);
    }

    /**
     * Alterna el estado activo/inactivo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function toggleEstado(int $id): JsonResponse
    {
        $result = $this->dispositivoService->toggleEstado($id);

        return response()->json($result, (int) $result['status']);
    }

    /**
     * Elimina un dispositivo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->dispositivoService->destroy($id);

        return response()->json($result, (int) $result['status']);
    }
}
