<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\SAHITranscibirService;
use Illuminate\Http\JsonResponse;

class SAHITranscibirController extends Controller
{
    public function __construct(
        private readonly SAHITranscibirService $sahiTranscibirService,
    ) {}

    public function enviarEstudio(
        ?int $idAtencion = null,
        ?int $idMedico = null,
        ?string $reporteEstudio = null,
        ?string $codCups = null,
        ?string $fecHorLectura = null
    ): JsonResponse {
        $result = $this->sahiTranscibirService->enviarEstudio(
            $idAtencion,
            $idMedico,
            $reporteEstudio,
            $codCups,
            $fecHorLectura
        );

        return response()->json($result, $result['status']);
    }
}
