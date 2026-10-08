<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Services\Estudios\EstudioLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de reserva (lock) de lectura y asignación de estudios.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-08
 */
class EstudioLockController extends Controller
{
    public function __construct(
        private readonly EstudioLockService $lockService,
    ) {}

    /**
     * Toma o renueva la reserva de un estudio para el usuario actual.
     *
     * @param  Request  $request  tipo, estudio_producto_id (lectura) o study_iuid/study_pk/sede_id (asignación)
     *
     * @returns JsonResponse Clave y expiración, o 409 si otro usuario la tiene reservada
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function tomar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo' => 'required|in:lectura,asignacion',
            'estudio_producto_id' => 'required_if:tipo,lectura|nullable|integer|exists:estudios_productos,id',
            'study_iuid' => 'nullable|string|max:100',
            'study_pk' => 'nullable|integer',
            'sede_id' => 'nullable|integer|exists:config_sedes,id',
        ], [
            'estudio_producto_id.required_if' => 'Debe indicar el producto de estudio a reservar.',
            'estudio_producto_id.exists' => 'El producto de estudio no existe.',
        ]);

        $result = $this->lockService->tomar($data['tipo'], $data);

        return response()->json($result, $result['status']);
    }

    /**
     * Libera la reserva de un estudio si pertenece al usuario actual.
     *
     * @param  Request  $request  tipo y mismos identificadores usados al tomar
     *
     * @returns JsonResponse Confirmación de liberación (idempotente)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function liberar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipo' => 'required|in:lectura,asignacion',
            'estudio_producto_id' => 'required_if:tipo,lectura|nullable|integer|exists:estudios_productos,id',
            'study_iuid' => 'nullable|string|max:100',
            'study_pk' => 'nullable|integer',
        ]);

        $result = $this->lockService->liberar($data['tipo'], $data);

        return response()->json($result, $result['status']);
    }
}
