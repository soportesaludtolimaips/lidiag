<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\ListarEstudiosNotificarRequest;
use App\Services\Estudios\EstudioNotificacionService;
use Illuminate\Http\JsonResponse;

class EstudioNotificacionController extends Controller
{
    public function __construct(
        private readonly EstudioNotificacionService $estudioNotificacionService,
    ) {
        ini_set('max_execution_time', -1);
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:estudios.notificar.index');
    }

    public function listarEstudios(ListarEstudiosNotificarRequest $request): JsonResponse
    {
        $result = $this->estudioNotificacionService->listarEstudios($request->all());

        return response()->json($result, $result['status']);
    }

    public function notificarEmail($estudio, $archivoReporte): JsonResponse
    {
        $result = $this->estudioNotificacionService->notificarEmail($estudio, $archivoReporte);

        return response()->json($result, $result['status']);
    }

    public function enviarPortalPacientes($estudio): JsonResponse
    {
        $result = $this->estudioNotificacionService->enviarPortalPacientes($estudio);

        return response()->json($result, $result['status']);
    }

    public static function descargaImgDicom($sedeId, $study_iuid): JsonResponse
    {
        $result = EstudioNotificacionService::descargaImagenesDicom($sedeId, $study_iuid);

        return response()->json($result, $result['status']);
    }

    public function enviarMensajeWhatsApp($numeroTelefono, $mensaje): JsonResponse
    {
        $result = $this->estudioNotificacionService->enviarWhatsApp($numeroTelefono, $mensaje);

        return response()->json($result, $result['status']);
    }

    public function eliminarReporte($reportePath): void
    {
        $this->estudioNotificacionService->eliminarReporte($reportePath);
    }
}
