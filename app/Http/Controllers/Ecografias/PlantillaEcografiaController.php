<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\PlantillaEcografiaEditWordRequest;
use App\Services\Ecografias\PlantillaEcografiaService;
use Illuminate\Http\JsonResponse;

class PlantillaEcografiaController extends Controller
{
    /**
     * Constructor del controlador de plantillas de ecografía, inyecta el servicio
     * encargado de generar los reportes y configura los middleware de autenticación
     * y permisos requeridos para las acciones disponibles.
     *
     * @param  PlantillaEcografiaService  $plantillaService  Servicio que contiene la lógica de negocio para la generación de las plantillas de ecografía en Word/PDF.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-05-18
     */
    public function __construct(
        private readonly PlantillaEcografiaService $plantillaService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:ecografias.reporte,web')->only(['editWord']);
    }

    /**
     * Recibe la solicitud validada con los datos de la ecografía y delega en el
     * servicio la generación del reporte en Word/PDF, devolviendo una respuesta
     * JSON con el resultado de la operación y su código de estado HTTP.
     *
     * @param  PlantillaEcografiaEditWordRequest  $request  Solicitud HTTP validada que contiene los datos necesarios para generar la plantilla de la ecografía.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la generación de la plantilla y su respectivo código de estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-05-18
     */
    public function editWord(PlantillaEcografiaEditWordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->plantillaService->editWord($data);

        return response()->json($result, $result['status']);
    }
}
