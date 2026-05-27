<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioAudio;
use App\Services\TranscripcionService;
use FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EstudioAudioController extends Controller
{
    public function __construct() {}

    /**
     * Sube y convierte un archivo de audio a MP3 usando FFmpeg.
     */
    public function uploadAudio(Request $request)
    {
        $request->validate([
            'audio' => ['required', 'file'],
            'estudio_id' => ['required', 'integer'],
            'estudio_producto_id' => ['required', 'integer'],
        ]);

        if (! $request->hasFile('audio')) {
            return response()->json(['success' => false, 'message' => 'No se proporcionó un archivo de audio'], 400);
        }

        $audioFile = $request->file('audio');
        if (! $audioFile || ! $audioFile->isValid()) {
            return response()->json(['success' => false, 'message' => 'Archivo de audio inválido'], 400);
        }

        $baseName = 'audio_'.Str::ulid()->toBase32();

        $guessedExt = $audioFile->guessExtension();
        $guessedExt = $guessedExt ? strtolower(preg_replace('/[^a-z0-9]+/i', '', $guessedExt)) : 'dat';
        if ($guessedExt === '') {
            $guessedExt = 'dat';
        }

        $tmpRelativePath = 'tmp/'.$baseName.'.'.$guessedExt;
        $mp3RelativePath = $baseName.'.mp3';

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('audios');

        $tmpFullPath = $disk->path($tmpRelativePath);
        $mp3FullPath = $disk->path($mp3RelativePath);

        $audioFile->storeAs('tmp', $baseName.'.'.$guessedExt, 'audios');

        try {
            $ffmpeg = FFMpeg\FFMpeg::create([
                'ffmpeg.binaries' => env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe'),
                'ffprobe.binaries' => env('FFPROBE_BINARIES', 'C:/ffmpeg/bin/ffprobe.exe'),
                'timeout' => (int) env('FFMPEG_TIMEOUT', 3600),
                'ffmpeg.threads' => (int) env('FFMPEG_THREADS', 12),
            ]);

            try {
                $streams = $ffmpeg->getFFProbe()->streams($tmpFullPath);
                $audioStreams = $streams->audios();
                if (! $audioStreams || $audioStreams->count() < 1) {
                    return response()->json(['success' => false, 'message' => 'El archivo no contiene audio válido'], 400);
                }

                $minSeconds = (float) env('AUDIO_MIN_SECONDS', 0);
                if ($minSeconds > 0) {
                    $duration = (float) $ffmpeg->getFFProbe()->format($tmpFullPath)->get('duration');
                    if ($duration > 0 && $duration < $minSeconds) {
                        return response()->json(['success' => false, 'message' => 'El audio debe tener contenido'], 400);
                    }
                }
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'No se pudo procesar el audio'], 400);
            }

            $format = new Mp3;
            $format->setAudioCodec('libmp3lame');
            $format->setAudioKiloBitrate((int) env('AUDIO_MP3_KBPS', 64));

            $audio = $ffmpeg->open($tmpFullPath);
            $audio->save($format, $mp3FullPath);

            if (! file_exists($mp3FullPath) || filesize($mp3FullPath) < 1024) {
                return response()->json(['success' => false, 'message' => 'Error al convertir el audio.'], 500);
            }

            $estudioAudio = new EstudioAudio;
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
            try {
                $disk->delete($tmpRelativePath);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Lista los archivos de audio asociados a un estudio/producto.
     */
    public function listarAudiosPorEstudio(int $estudio_producto_id)
    {
        if (! $estudio_producto_id || $estudio_producto_id < 1) {
            return response()->json(['error' => 'Invalid ID', 'received' => $estudio_producto_id], 400);
        }

        $audios = EstudioAudio::where('estudio_producto_id', $estudio_producto_id)->get();

        return response()->json($audios);
    }

    /**
     * Sirve un audio MP3 desde el disk "audios".
     */
    public function stream(string $filename)
    {
        if (! preg_match('/^(audio|cargado)_[A-Za-z0-9]{10,}\.(mp3|wav)$/', $filename)) {
            abort(404);
        }

        $disk = Storage::disk('audios');
        if (! $disk->exists($filename)) {
            abort(404);
        }

        $root = (string) config('filesystems.disks.audios.root', storage_path('app/audios'));
        $fullPath = rtrim($root, "/\\").DIRECTORY_SEPARATOR.$filename;
        if (! is_file($fullPath)) {
            abort(404);
        }

        $size = (int) filesize($fullPath);
        $range = request()->header('Range');

        if (! $range) {
            return response()->file($fullPath, [
                'Content-Type' => 'audio/mpeg',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Accept-Ranges' => 'bytes',
                'Content-Length' => (string) $size,
            ]);
        }

        if (! preg_match('/bytes=(\d*)-(\d*)/i', $range, $m)) {
            return response('', 416)->withHeaders([
                'Content-Range' => 'bytes */'.$size,
                'Accept-Ranges' => 'bytes',
            ]);
        }

        $start = ($m[1] !== '') ? (int) $m[1] : 0;
        $end = ($m[2] !== '') ? (int) $m[2] : ($size - 1);

        if ($start < 0) {
            $start = 0;
        }
        if ($end >= $size) {
            $end = $size - 1;
        }
        if ($start > $end || $start >= $size) {
            return response('', 416)->withHeaders([
                'Content-Range' => 'bytes */'.$size,
                'Accept-Ranges' => 'bytes',
            ]);
        }

        $length = $end - $start + 1;
        $fh = fopen($fullPath, 'rb');
        if (! $fh) {
            abort(404);
        }
        fseek($fh, $start);

        return response()->stream(function () use ($fh, $length) {
            $remaining = $length;
            while ($remaining > 0 && ! feof($fh)) {
                $read = ($remaining > 8192) ? 8192 : $remaining;
                $buffer = fread($fh, $read);
                if ($buffer === false) {
                    break;
                }
                echo $buffer;
                $remaining -= strlen($buffer);
                if (function_exists('flush')) {
                    flush();
                }
            }
            try {
                fclose($fh);
            } catch (\Exception $e) {
            }
        }, 206, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Accept-Ranges' => 'bytes',
            'Content-Range' => "bytes {$start}-{$end}/{$size}",
            'Content-Length' => (string) $length,
            'Cache-Control' => 'private, max-age=0, no-cache',
        ]);
    }

    /**
     * Carga un archivo de audio y lo transcribe automaticamente.
     */
    public function cargarAudioTranscribir(Request $request, TranscripcionService $transcripcionService)
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:102400'],
            'estudio_id' => ['required', 'integer'],
            'estudio_producto_id' => ['required', 'integer'],
        ]);

        if (! $request->hasFile('audio')) {
            return response()->json(['success' => false, 'message' => 'No se proporciono un archivo de audio'], 400);
        }

        $audioFile = $request->file('audio');
        if (! $audioFile || ! $audioFile->isValid()) {
            return response()->json(['success' => false, 'message' => 'Archivo de audio invalido'], 400);
        }

        $originalName = $audioFile->getClientOriginalName();
        $extension = strtolower($audioFile->getClientOriginalExtension());
        $allowedExtensions = ['mp3', 'wav', 'm4a', 'webm', 'ogg', 'flac'];

        if (! in_array($extension, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => "Formato no soportado: {$extension}. Permitidos: " . implode(', ', $allowedExtensions),
            ], 400);
        }

        $tamano = $audioFile->getSize();
        $estudioProductoId = (int) $request->estudio_producto_id;
        $estudioId = (int) $request->estudio_id;
        $usuarioId = auth()->id();

        $baseName = 'cargado_' . Str::ulid()->toBase32();
        $mp3RelativePath = $baseName . '.mp3';

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('audios');

        $tmpRelativePath = 'tmp/' . $baseName . '.' . $extension;
        $audioFile->storeAs('tmp', basename($tmpRelativePath), 'audios');

        $tmpFullPath = $disk->path($tmpRelativePath);
        $mp3FullPath = $disk->path($mp3RelativePath);

        try {
            $ffmpeg = FFMpeg\FFMpeg::create([
                'ffmpeg.binaries' => env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe'),
                'ffprobe.binaries' => env('FFPROBE_BINARIES', 'C:/ffmpeg/bin/ffprobe.exe'),
                'timeout' => (int) env('FFMPEG_TIMEOUT', 3600),
                'ffmpeg.threads' => (int) env('FFMPEG_THREADS', 12),
            ]);

            try {
                $streams = $ffmpeg->getFFProbe()->streams($tmpFullPath);
                $audioStreams = $streams->audios();
                if (! $audioStreams || $audioStreams->count() < 1) {
                    return response()->json(['success' => false, 'message' => 'El archivo no contiene audio valido'], 400);
                }

                $formatInfo = $ffmpeg->getFFProbe()->format($tmpFullPath);
                $duracion = (int) $formatInfo->get('duration');
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'No se pudo procesar el audio'], 400);
            }

            $format = new Mp3;
            $format->setAudioCodec('libmp3lame');
            $format->setAudioKiloBitrate((int) env('AUDIO_MP3_KBPS', 64));

            $audio = $ffmpeg->open($tmpFullPath);
            $audio->save($format, $mp3FullPath);

            if (! file_exists($mp3FullPath) || filesize($mp3FullPath) < 1024) {
                return response()->json(['success' => false, 'message' => 'Error al convertir el audio.'], 500);
            }

            Log::info('Audio cargado, iniciando transcripcion', [
                'estudio_producto_id' => $estudioProductoId,
                'archivo' => $originalName,
            ]);

            $transcripcion = $transcripcionService->transcribir($tmpFullPath);

            Log::info('Transcripcion completada', [
                'longitud_texto' => strlen($transcripcion['texto']),
            ]);

            $estudioAudio = new EstudioAudio;
            $estudioAudio->estudio_id = $estudioId;
            $estudioAudio->estudio_producto_id = $estudioProductoId;
            $estudioAudio->audio = $mp3RelativePath;
            $estudioAudio->tipo = 'cargado';
            $estudioAudio->nombre_original = $originalName;
            $estudioAudio->formato = $extension;
            $estudioAudio->duracion = $duracion;
            $estudioAudio->tamano = $tamano;
            $estudioAudio->usuario_id = $usuarioId;
            $estudioAudio->transcripcion = $transcripcion['texto'];
            $estudioAudio->save();

            return response()->json([
                'success' => true,
                'message' => 'Audio cargado y transcrito exitosamente',
                'audio_id' => $estudioAudio->id,
                'audio_file' => $mp3RelativePath,
                'transcripcion' => $transcripcion['texto'],
                'duracion' => $duracion,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en carga/transcripcion de audio', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error procesando el audio: ' . $e->getMessage(),
            ], 500);
        } finally {
            try {
                $disk->delete($tmpRelativePath);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Elimina un audio cargado y su archivo.
     */
    public function eliminarAudioCargado(int $id)
    {
        try {
            $estudioAudio = EstudioAudio::findOrFail($id);

            if ($estudioAudio->tipo !== 'cargado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar audios cargados, no grabados',
                ], 403);
            }

            $disk = Storage::disk('audios');
            if ($estudioAudio->audio && $disk->exists($estudioAudio->audio)) {
                $disk->delete($estudioAudio->audio);
            }

            $estudioAudio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Audio eliminado correctamente',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Audio no encontrado',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error eliminando audio cargado', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error eliminando el audio',
            ], 500);
        }
    }
}
