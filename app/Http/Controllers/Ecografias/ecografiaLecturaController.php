<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaLeerRequest;
use App\Services\Ecografias\EcografiaLecturaService;
use Illuminate\Http\JsonResponse;

class ecografiaLecturaController extends Controller
{
    /**
     * Inicializa el controlador de lectura de ecografías e inyecta el servicio de lectura, configurando los middleware de autenticación y permisos.
     *
     * @param  EcografiaLecturaService  $lecturaService  Servicio encargado de la lógica de lectura de ecografías.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-18
     */
    public function __construct(
        private readonly EcografiaLecturaService $lecturaService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:ecografias.leer,web')->only(['leer', 'listarPendientesPorLectura']);
    }

    /**
     * Permite a un médico leer una ecografía específica, realizar acciones como imprimir la lectura, terminar la lectura o guardar un borrador, además de actualizar la lectura y opinión asociadas a la ecografía.
     *
     * @param  EcografiaLeerRequest  $request  Solicitud HTTP validada con los datos necesarios para realizar la acción de lectura.
     * @param  int  $id  Identificador de la ecografía que se desea leer.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la operación, incluyendo el estado y los mensajes relevantes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-18
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
     * @param  int  $idMedico  Identificador del médico para el cual se desean listar las ecografías pendientes de lectura.
     *
     * @returns JsonResponse Respuesta JSON con la lista de ecografías pendientes y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-18
     */
    public function listarPendientesPorLectura(int $idMedico): JsonResponse
    {
        $result = $this->lecturaService->listarPendientesPorLectura($idMedico);

        return response()->json($result, $result['status']);
    }
}
