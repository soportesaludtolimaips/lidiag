<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioAudio;
use Illuminate\Http\Request;
use FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Support\Facades\Storage;

class EstudioAudioController extends Controller
{

    /**
     * Sube y convierte un archivo de audio a MP3 usando FFmpeg.
     *
     * Flujo del proceso:
     * 1. Valida que el archivo tenga contenido (> 20KB).
     * 2. Guarda el archivo original temporalmente.
     * 3. Convierte el audio a MP3 con bitrate reducido (64 kbps) usando FFmpeg.
     * 4. Elimina el archivo original.
     * 5. Guarda la referencia en la base de datos.
     *
     * @param Request $request Archivo de audio, `estudio_id`, `estudio_producto_id`.
     * @return \Illuminate\Http\JsonResponse Confirmación o error.
     */
    public function uploadAudio(Request $request)
    {
        if ($request->hasFile('audio')) {
            $audioFile = $request->file('audio');
            $timestamp = time();
            $nombreArchivoOriginal = 'audio_' . $timestamp . '.' . $audioFile->getClientOriginalExtension();
            $nombreArchivoMp3 = 'audio_' . $timestamp . '.mp3'; // ← Nombre del MP3
            $outputPath = Storage::disk('audios')->path($nombreArchivoMp3);

            if ($audioFile->getSize() > 20000) {
                // Guardar temporal
                $audioFile->storeAs('', $nombreArchivoOriginal, 'audios');

                try {
                    $ffmpeg = FFMpeg\FFMpeg::create([
                        'ffmpeg.binaries'  => 'C:/ffmpeg/bin/ffmpeg.exe',
                        'ffprobe.binaries' => 'C:/ffmpeg/bin/ffprobe.exe',
                        'timeout'          => 3600,
                        'ffmpeg.threads'   => 12,
                    ]);

                    $format = new Mp3();
                    $format->setAudioCodec('libmp3lame');
                    $format->setAudioKiloBitrate(64);

                    $audio = $ffmpeg->open(Storage::disk('audios')->path($nombreArchivoOriginal));
                    $audio->save($format, $outputPath);

                    Storage::disk('audios')->delete($nombreArchivoOriginal);

                    // Guardar en BD con nombre .mp3 ← FIX AQUÍ
                    $estudioAudio = new EstudioAudio();
                    $estudioAudio->estudio_id = $request->estudio_id;
                    $estudioAudio->estudio_producto_id = $request->estudio_producto_id;
                    $estudioAudio->audio = $nombreArchivoMp3; // ← Cambio importante
                    $estudioAudio->save();

                    return response()->json(['success' => true, 'message' => 'Audio subido con éxito'], 200);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al convertir el audio.',
                        'error' => $e->getMessage(),
                    ], 500);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'El audio debe tener contenido'], 400);
            }
        }

        return response()->json(['success' => false, 'message' => 'No se proporcionó un archivo de audio'], 400);
    }

    /**
     * Lista los archivos de audio asociados a un estudio.
     *
     * @param Request $request ID del estudio (`estudio_id`).
     * @return \Illuminate\Http\JsonResponse Lista de audios con URL incluida.
     */
    public function listarAudiosPorEstudio(Request $request)
    {
        $estudioId = $request->estudio_id;

        $audios = EstudioAudio::where('estudio_id', $estudioId)->get();

        return response()->json($audios);
    }
}
