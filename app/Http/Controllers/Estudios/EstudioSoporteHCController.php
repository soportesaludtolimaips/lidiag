<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Services\Estudios\EstudioSoporteHCService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudioSoporteHCController extends Controller
{
    /**
     * Constructor del controlador de soportes de historia clínica
     *
     * @param  EstudioSoporteHCService  $estudioSoporteHCService  Servicio de gestión de soportes
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function __construct(
        private readonly EstudioSoporteHCService $estudioSoporteHCService,
    ) {}

    /**
     * Lista los soportes de historia clínica asociados a un estudio
     *
     * @param  Request  $request  Solicitud con el ID del estudio
     *
     * @returns JsonResponse Respuesta con la lista de soportes del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarSoportesHistoriaClinicaPorPorEstudio(Request $request): JsonResponse
    {
        $result = $this->estudioSoporteHCService->listarPorEstudio($request->integer('id'));

        return response()->json($result, $result['status']);
    }

    /**
     * Descarga un archivo soporte de historia clínica encriptado
     *
     * @param  string  $nomArchivoEncriptadot  Nombre del archivo encriptado a descargar
     *
     * @returns mixed Respuesta con el archivo o error si no existe
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function descargarSoporte(string $nomArchivoEncriptadot)
    {
        $result = $this->estudioSoporteHCService->descargar($nomArchivoEncriptadot);

        if (! $result['success']) {
            return response()->json($result, $result['status']);
        }

        return response()->download($result['data']['path']);
    }
}
