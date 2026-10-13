<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interfaces\EnviarEstudioTranscripcionRequest;
use App\Services\Interfaces\SAHITranscibirService;
use Illuminate\Http\JsonResponse;

class SAHITranscibirController extends Controller
{
    /**
     * Inyecta el servicio de transcripción de estudios a SAHI.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly SAHITranscibirService $sahiTranscibirService,
    ) {}

    /**
     * Envía la transcripción de un estudio (reporte, cups y lectura) a SAHI.
     *
     * @param  EnviarEstudioTranscripcionRequest  $request  Solicitud validada con los datos de la transcripción.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function enviarEstudio(EnviarEstudioTranscripcionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->sahiTranscibirService->enviarEstudio(
            (int) $validated['idAtencion'],
            (int) $validated['idMedico'],
            $validated['reporteEstudio'],
            $validated['codCups'],
            $validated['fecHorLectura']
        );

        return response()->json($result, $result['status']);
    }
}
