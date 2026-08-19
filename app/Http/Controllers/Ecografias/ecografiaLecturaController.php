<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaLeerRequest;
use App\Services\Ecografias\EcografiaLecturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ecografiaLecturaController extends Controller
{
    public function __construct(
        private readonly EcografiaLecturaService $lecturaService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:ecografias.leer,web')->only(['leer', 'listarPendientesPorLectura']);
    }

    /**
     * Permite a un médico leer una ecografía específica, realizar acciones como imprimir la lectura, terminar la lectura o guardar un borrador.
     * además, permite actualizar la lectura y opinión asociadas a la ecografía.
     *
     * @param  EcografiaLeerRequest  $request  - La solicitud HTTP que contiene los datos necesarios para realizar la acción de lectura.
     * @param  int  $id  - El ID de la ecografía que se desea leer.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @throws ValidationException - Si los datos proporcionados en la solicitud no cumplen con las reglas de validación definidas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function leer(EcografiaLeerRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        $result = $this->lecturaService->leer($data, $id);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista todas las ecografías pendientes de lectura para un médico específico.
     *
     * @param  int  $idMedico  - El ID del médico para el cual se desean listar las ecografías pendientes de lectura.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function listarPendientesPorLectura(int $idMedico): JsonResponse
    {
        $result = $this->lecturaService->listarPendientesPorLectura($idMedico);

        return response()->json($result, $result['status']);
    }
}
