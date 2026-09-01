<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\CargarAudioTranscribirRequest;
use App\Http\Requests\Estudios\StoreAudioEstudioRequest;
use App\Services\Estudios\EstudioAudioService;
use Illuminate\Http\JsonResponse;

class EstudioAudioController extends Controller
{
    /**
     * Constructor del controlador de audios de estudio
     *
     * @param  EstudioAudioService  $estudioAudioService  Servicio de gestión de audios
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function __construct(
        private readonly EstudioAudioService $estudioAudioService,
    ) {}

    /**
     * Sube un archivo de audio, lo convierte a MP3 y transcribe automáticamente
     *
     * @param  StoreAudioEstudioRequest  $request  Solicitud con el archivo audio, estudio_id y estudio_producto_id
     *
     * @returns JsonResponse Respuesta con el ID del audio, archivo MP3, transcripción y duración
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function uploadAudio(StoreAudioEstudioRequest $request): JsonResponse
    {
        $result = $this->estudioAudioService->uploadAudio(
            $request->file('audio'),
            $request->integer('estudio_id'),
            $request->integer('estudio_producto_id'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Lista todos los audios asociados a un producto de estudio específico
     *
     * @param  int  $estudio_producto_id  ID del producto de estudio
     *
     * @returns JsonResponse Respuesta con la lista de audios encontrados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarAudiosPorEstudio(int $estudio_producto_id): JsonResponse
    {
        $result = $this->estudioAudioService->listarPorProducto($estudio_producto_id);

        return response()->json($result, $result['status']);
    }

    /**
     * Reproduce un archivo de audio en streaming con soporte para rangos HTTP
     *
     * @param  string  $filename  Nombre del archivo de audio a reproducir
     *
     * @returns mixed Respuesta de streaming con headers de audio/mpeg
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function stream(string $filename)
    {
        return $this->estudioAudioService->stream($filename);
    }

    /**
     * Carga un archivo de audio existente y lo transcribe automáticamente
     *
     * @param  CargarAudioTranscribirRequest  $request  Solicitud con el archivo audio, estudio_id y estudio_producto_id
     *
     * @returns JsonResponse Respuesta con el ID del audio, archivo MP3, transcripción y duración
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function cargarAudioTranscribir(CargarAudioTranscribirRequest $request): JsonResponse
    {
        $result = $this->estudioAudioService->cargarYTranscribir(
            $request->file('audio'),
            $request->integer('estudio_id'),
            $request->integer('estudio_producto_id'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un audio que fue cargado (no grabado) del sistema
     *
     * @param  int  $id  ID del audio a eliminar
     *
     * @returns JsonResponse Respuesta de confirmación de eliminación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function eliminarAudioCargado(int $id): JsonResponse
    {
        $result = $this->estudioAudioService->eliminarCargado($id);

        return response()->json($result, $result['status']);
    }
}
