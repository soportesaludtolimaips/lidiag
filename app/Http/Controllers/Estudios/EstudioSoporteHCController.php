<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Services\Estudios\EstudioSoporteHCService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudioSoporteHCController extends Controller
{
    public function __construct(
        private readonly EstudioSoporteHCService $estudioSoporteHCService,
    ) {}

    public function listarSoportesHistoriaClinicaPorPorEstudio(Request $request): JsonResponse
    {
        $result = $this->estudioSoporteHCService->listarPorEstudio($request->integer('id'));

        return response()->json($result, $result['status']);
    }

    public function descargarSoporte(string $nomArchivoEncriptadot)
    {
        $result = $this->estudioSoporteHCService->descargar($nomArchivoEncriptadot);

        if (! $result['success']) {
            return response()->json($result, $result['status']);
        }

        return response()->download($result['data']['path']);
    }
}
