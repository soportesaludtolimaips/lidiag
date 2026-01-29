<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioAudio;
use Illuminate\Http\Request;
use FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EstudioAudioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:estudio.transcribir|estudio.leerEstudio');
    }

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
        $request->validate([
            'audio' => ['required', 'file'],
            'estudio_id' => ['required', 'integer'],
            'estudio_producto_id' => ['required', 'integer'],
        ]);

        if (!$request->hasFile('audio')) {
            return response()->json(['success' => false, 'message' => 'No se proporcionó un archivo de audio'], 400);
        }

        $audioFile = $request->file('audio');
        if (!$audioFile || !$audioFile->isValid()) {
            return response()->json(['success' => false, 'message' => 'Archivo de audio inválido'], 400);
        }

        // Nombre único para evitar colisiones
        $baseName = 'audio_' . Str::ulid()->toBase32();

        // Usar extensión "adivinada" por el servidor (NO confiar en la del cliente)
        $guessedExt = $audioFile->guessExtension();
        $guessedExt = $guessedExt ? strtolower(preg_replace('/[^a-z0-9]+/i', '', $guessedExt)) : 'dat';
        if ($guessedExt === '') $guessedExt = 'dat';

        $tmpRelativePath = 'tmp/' . $baseName . '.' . $guessedExt;
        $mp3RelativePath = $baseName . '.mp3';

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('audios');

        $tmpFullPath = $disk->path($tmpRelativePath);
        $mp3FullPath = $disk->path($mp3RelativePath);

        // Guardar temporal (siempre)
        $audioFile->storeAs('tmp', $baseName . '.' . $guessedExt, 'audios');

        try {
            $ffmpeg = FFMpeg\FFMpeg::create([
                // Permite configurar por .env; mantiene compatibilidad con tu ruta actual
                'ffmpeg.binaries'  => env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe'),
                'ffprobe.binaries' => env('FFPROBE_BINARIES', 'C:/ffmpeg/bin/ffprobe.exe'),
                'timeout'          => (int) env('FFMPEG_TIMEOUT', 3600),
                'ffmpeg.threads'   => (int) env('FFMPEG_THREADS', 12),
            ]);

            // Verificar que realmente sea un archivo con stream de audio (aceptar "cualquier audio")
            try {
                $streams = $ffmpeg->getFFProbe()->streams($tmpFullPath);
                $audioStreams = $streams->audios();
                if (!$audioStreams || $audioStreams->count() < 1) {
                    return response()->json(['success' => false, 'message' => 'El archivo no contiene audio válido'], 400);
                }

                // Opcional: considerar "vacío" por duración mínima (en segundos)
                $minSeconds = (float) env('AUDIO_MIN_SECONDS', 0);
                if ($minSeconds > 0) {
                    $duration = (float) $ffmpeg->getFFProbe()->format($tmpFullPath)->get('duration');
                    if ($duration > 0 && $duration < $minSeconds) {
                        return response()->json(['success' => false, 'message' => 'El audio debe tener contenido'], 400);
                    }
                }
            } catch (\Exception $e) {
                // Si ffprobe falla, tratar como archivo inválido
                return response()->json(['success' => false, 'message' => 'No se pudo procesar el audio'], 400);
            }

            // Convertir/comprimir a MP3 antes de almacenar "final"
            $format = new Mp3();
            $format->setAudioCodec('libmp3lame');
            $format->setAudioKiloBitrate((int) env('AUDIO_MP3_KBPS', 64));

            $audio = $ffmpeg->open($tmpFullPath);
            $audio->save($format, $mp3FullPath);

            // Validación básica del resultado
            if (!file_exists($mp3FullPath) || filesize($mp3FullPath) < 1024) {
                return response()->json(['success' => false, 'message' => 'Error al convertir el audio.'], 500);
            }

            // Guardar en BD apuntando al mp3
            $estudioAudio = new EstudioAudio();
            $estudioAudio->estudio_id = (int) $request->estudio_id;
            $estudioAudio->estudio_producto_id = (int) $request->estudio_producto_id;
            $estudioAudio->audio = $mp3RelativePath;
            $estudioAudio->save();

            return response()->json(['success' => true, 'message' => 'Audio subido con éxito'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al convertir el audio.',
                'error' => $e->getMessage(),
            ], 500);
        } finally {
            // Limpieza best-effort del temporal siempre
            try {
                $disk->delete($tmpRelativePath);
            } catch (\Exception $e) {
            }
        }
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
