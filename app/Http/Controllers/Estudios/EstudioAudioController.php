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

    public function uploadAudio(Request $request)
    {

        if ($request->hasFile('audio')) {

            $audioFile = $request->file('audio');
            $nombreArchivo = 'audio_' . time() . '.' . $audioFile->getClientOriginalExtension();
            $outputPath = Storage::disk('audios')->path('audio_' . time() . '.mp3'); // Ruta del archivo convertido

            if ($audioFile->getSize() > 20000) {

                $audioFilePath = $audioFile->storeAs('', $nombreArchivo, 'audios');

                try {
                    // Configurar FFmpeg
                    $ffmpeg = FFMpeg\FFMpeg::create([
                        'ffmpeg.binaries'  => 'C:/ffmpeg/bin/ffmpeg.exe',
                        'ffprobe.binaries' => 'C:/ffmpeg/bin/ffprobe.exe',
                        'timeout'          => 3600,
                        'ffmpeg.threads'   => 12,
                    ]);

                    // Configurar formato MP3
                    $format = new Mp3();
                    $format->setAudioCodec('libmp3lame');
                    $format->setAudioKiloBitrate(128);

                    // Convertir el archivo
                    $audio = $ffmpeg->open(Storage::disk('audios')->path($nombreArchivo));
                    $audio->save($format, $outputPath);

                    // Guardar en la base de datos
                    $estudioAudio = new EstudioAudio();
                    $estudioAudio->estudio_id = $request->estudio_id;
                    $estudioAudio->estudio_producto_id = $request->estudio_producto_id;
                    $estudioAudio->audio = $audioFilePath;
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

    public function listarAudiosPorEstudio(Request $request)
    {
        $estudioId = $request->estudio_id;

        $audios = EstudioAudio::where('estudio_id', '=', $estudioId)
            ->get();

        return response()->json($audios);
    }
}
