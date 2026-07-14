<?php

namespace App\Services\Estudios;

use App\Models\Estudio\EstudioAudio;
use App\Services\BaseService;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EstudioAudioService extends BaseService
{
    public function uploadAudio(UploadedFile $file, int $estudioId, int $estudioProductoId): array
    {
        $baseName = 'audio_'.Str::ulid()->toBase32();
        $guessedExt = $file->guessExtension() ?: 'dat';

        $tmpRelativePath = 'tmp/'.$baseName.'.'.$guessedExt;
        $mp3RelativePath = $baseName.'.mp3';

        $disk = Storage::disk('audios');
        $tmpFullPath = $disk->path($tmpRelativePath);
        $mp3FullPath = $disk->path($mp3RelativePath);

        $file->storeAs('tmp', $baseName.'.'.$guessedExt, 'audios');

        try {
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe'),
                'ffprobe.binaries' => env('FFPROBE_BINARIES', 'C:/ffmpeg/bin/ffprobe.exe'),
                'timeout' => (int) env('FFMPEG_TIMEOUT', 3600),
                'ffmpeg.threads' => (int) env('FFMPEG_THREADS', 12),
            ]);

            $streams = $ffmpeg->getFFProbe()->streams($tmpFullPath);
            $audioStreams = $streams->audios();
            if (! $audioStreams || $audioStreams->count() < 1) {
                return $this->validationError(['audio' => ['El archivo no contiene audio válido']]);
            }

            $format = new Mp3;
            $format->setAudioCodec('libmp3lame');
            $format->setAudioKiloBitrate((int) env('AUDIO_MP3_KBPS', 64));

            $audio = $ffmpeg->open($tmpFullPath);
            $audio->save($format, $mp3FullPath);

            if (! file_exists($mp3FullPath) || filesize($mp3FullPath) < 1024) {
                return $this->error('Error al convertir el audio.', 500);
            }

            $estudioAudio = EstudioAudio::create([
                'estudio_id' => $estudioId,
                'estudio_producto_id' => $estudioProductoId,
                'audio' => $mp3RelativePath,
                'tipo' => 'audio',
            ]);

            return $this->created($estudioAudio, 'Audio subido con éxito');
        } catch (\Exception $e) {
            Log::error('Error al convertir audio: '.$e->getMessage());

            return $this->error('Error al convertir el audio.', 500);
        } finally {
            try {
                $disk->delete($tmpRelativePath);
            } catch (\Exception $e) {
            }
        }
    }

    public function listarPorProducto(int $estudioProductoId): array
    {
        return $this->success(
            EstudioAudio::where('estudio_producto_id', $estudioProductoId)->get()
        );
    }

    public function stream(string $filename)
    {
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
            fclose($fh);
        }, 206, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Accept-Ranges' => 'bytes',
            'Content-Range' => "bytes {$start}-{$end}/{$size}",
            'Content-Length' => (string) $length,
            'Cache-Control' => 'private, max-age=0, no-cache',
        ]);
    }

    public function cargarYTranscribir(UploadedFile $file, int $estudioId, int $estudioProductoId): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['mp3', 'wav', 'm4a', 'webm', 'ogg', 'flac'];

        if (! in_array($extension, $allowedExtensions)) {
            return $this->validationError([
                'audio' => ["Formato no soportado: {$extension}. Permitidos: ".implode(', ', $allowedExtensions)],
            ]);
        }

        $tamano = $file->getSize();
        $usuarioId = Auth::id();

        $baseName = 'cargado_'.Str::ulid()->toBase32();
        $mp3RelativePath = $baseName.'.mp3';

        $disk = Storage::disk('audios');
        $tmpRelativePath = 'tmp/'.$baseName.'.'.$extension;
        $file->storeAs('tmp', basename($tmpRelativePath), 'audios');

        $tmpFullPath = $disk->path($tmpRelativePath);
        $mp3FullPath = $disk->path($mp3RelativePath);

        try {
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe'),
                'ffprobe.binaries' => env('FFPROBE_BINARIES', 'C:/ffmpeg/bin/ffprobe.exe'),
                'timeout' => (int) env('FFMPEG_TIMEOUT', 3600),
                'ffmpeg.threads' => (int) env('FFMPEG_THREADS', 12),
            ]);

            $streams = $ffmpeg->getFFProbe()->streams($tmpFullPath);
            $audioStreams = $streams->audios();
            if (! $audioStreams || $audioStreams->count() < 1) {
                return $this->validationError(['audio' => ['El archivo no contiene audio válido']]);
            }

            $formatInfo = $ffmpeg->getFFProbe()->format($tmpFullPath);
            $duracion = (int) $formatInfo->get('duration');

            $format = new Mp3;
            $format->setAudioCodec('libmp3lame');
            $format->setAudioKiloBitrate((int) env('AUDIO_MP3_KBPS', 64));

            $audio = $ffmpeg->open($tmpFullPath);
            $audio->save($format, $mp3FullPath);

            if (! file_exists($mp3FullPath) || filesize($mp3FullPath) < 1024) {
                return $this->error('Error al convertir el audio.', 500);
            }

            Log::info('Audio cargado, iniciando transcripcion', [
                'estudio_producto_id' => $estudioProductoId,
                'archivo' => $originalName,
            ]);

            $transcripcionTexto = '';

            $estudioAudio = EstudioAudio::create([
                'estudio_id' => $estudioId,
                'estudio_producto_id' => $estudioProductoId,
                'audio' => $mp3RelativePath,
                'tipo' => 'cargado',
                'nombre_original' => $originalName,
                'formato' => $extension,
                'duracion' => $duracion,
                'tamano' => $tamano,
                'usuario_id' => $usuarioId,
                'transcripcion' => $transcripcionTexto,
            ]);

            return $this->success([
                'audio_id' => $estudioAudio->id,
                'audio_file' => $mp3RelativePath,
                'transcripcion' => $transcripcionTexto,
                'duracion' => $duracion,
            ], 'Audio cargado y transcrito exitosamente');

        } catch (\Exception $e) {
            Log::error('Error en carga/transcripcion de audio', [
                'error' => $e->getMessage(),
            ]);

            return $this->error('Error procesando el audio: '.$e->getMessage(), 500);
        } finally {
            try {
                $disk->delete($tmpRelativePath);
            } catch (\Exception $e) {
            }
        }
    }

    public function eliminarCargado(int $id): array
    {
        try {
            $item = EstudioAudio::findOrFail($id);

            if ($item->tipo !== 'cargado') {
                return $this->validationError(['audio' => ['Solo se pueden eliminar audios cargados, no grabados']]);
            }

            $disk = Storage::disk('audios');
            if ($item->audio && $disk->exists($item->audio)) {
                $disk->delete($item->audio);
            }

            $item->delete();

            return $this->deleted();
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Audio no encontrado');
        } catch (\Exception $e) {
            Log::error('Error eliminando audio cargado: '.$e->getMessage());

            return $this->error('Error eliminando el audio', 500);
        }
    }
}
