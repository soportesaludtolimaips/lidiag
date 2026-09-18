<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interfaces\EnviarEstudioTranscripcionRequest;
use App\Services\Interfaces\SAHITranscibirService;
use Illuminate\Http\JsonResponse;

class SAHITranscibirController extends Controller
{
    public function __construct(
        private readonly SAHITranscibirService $sahiTranscibirService,
    ) {}

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
