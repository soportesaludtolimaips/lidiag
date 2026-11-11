<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\StoreEstudioDiagnosticoRequest;
use App\Http\Requests\Estudios\UpdateEstudioDiagnosticoRequest;
use App\Services\Estudios\EstudioDiagnosticoService;
use Illuminate\Http\JsonResponse;

class EstudioDiagnosticoController extends Controller
{
    /**
     * Constructor del controlador de diagnósticos de estudio
     *
     * @param  EstudioDiagnosticoService  $estudioDiagnosticoService  Servicio de gestión de diagnósticos
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function __construct(
        private readonly EstudioDiagnosticoService $estudioDiagnosticoService,
    ) {}

    /**
     * Lista todos los diagnósticos de estudios registrados
     *
     * @returns JsonResponse Respuesta con la lista completa de diagnósticos
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function index(): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo diagnóstico de estudio
     *
     * @param  StoreEstudioDiagnosticoRequest  $request  Solicitud con los datos del diagnóstico
     *
     * @returns JsonResponse Respuesta con el diagnóstico creado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function store(StoreEstudioDiagnosticoRequest $request): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra un diagnóstico de estudio específico por ID
     *
     * @param  int  $id  ID del diagnóstico a consultar
     *
     * @returns JsonResponse Respuesta con el diagnóstico encontrado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza un diagnóstico de estudio existente
     *
     * @param  UpdateEstudioDiagnosticoRequest  $request  Solicitud con los datos a actualizar
     * @param  int  $id  ID del diagnóstico a actualizar
     *
     * @returns JsonResponse Respuesta con el diagnóstico actualizado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function update(UpdateEstudioDiagnosticoRequest $request, int $id): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un diagnóstico de estudio existente
     *
     * @param  int  $id  ID del diagnóstico a eliminar
     *
     * @returns JsonResponse Respuesta de confirmación de eliminación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->estudioDiagnosticoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
