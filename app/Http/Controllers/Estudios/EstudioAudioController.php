<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\CargarAudioTranscribirRequest;
use App\Http\Requests\Estudios\StoreAudioEstudioRequest;
use App\Services\Estudios\EstudioAudioService;
use Illuminate\Http\JsonResponse;

class EstudioAudioController extends Controller
{
    public function __construct(
        private readonly EstudioAudioService $estudioAudioService,
    ) {}

    public function uploadAudio(StoreAudioEstudioRequest $request): JsonResponse
    {
        $result = $this->estudioAudioService->uploadAudio(
            $request->file('audio'),
            $request->integer('estudio_id'),
            $request->integer('estudio_producto_id'),
        );

        return response()->json($result, $result['status']);
    }

    public function listarAudiosPorEstudio(int $estudio_producto_id): JsonResponse
    {
        $result = $this->estudioAudioService->listarPorProducto($estudio_producto_id);

        return response()->json($result, $result['status']);
    }

    public function stream(string $filename)
    {
        return $this->estudioAudioService->stream($filename);
    }

    public function cargarAudioTranscribir(CargarAudioTranscribirRequest $request): JsonResponse
    {
        $result = $this->estudioAudioService->cargarYTranscribir(
            $request->file('audio'),
            $request->integer('estudio_id'),
            $request->integer('estudio_producto_id'),
        );

        return response()->json($result, $result['status']);
    }

    public function eliminarAudioCargado(int $id): JsonResponse
    {
        $result = $this->estudioAudioService->eliminarCargado($id);

        return response()->json($result, $result['status']);
    }
}
