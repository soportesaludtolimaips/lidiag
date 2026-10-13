<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Storage;

class FileAccessService extends BaseService
{
    /**
     * Sirve el contenido de un archivo desde un disco, descifrándolo si está en formato .enc.
     *
     * @param  string  $disk  Disco de almacenamiento configurado en el sistema
     * @param  string  $path  Ruta relativa del archivo dentro del disco
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function serve(string $disk, string $path): mixed
    {
        if (! array_key_exists($disk, config('filesystems.disks'))) {
            return $this->error('Disk not found', 404);
        }

        if (str_contains($path, '..') || str_contains($path, '%2e%2e')) {
            return $this->error('Ruta inválida', 400);
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            return $this->notFound('Archivo no encontrado');
        }

        $contents = $storage->get($path);

        if (str_ends_with($path, '.enc')) {
            $mimePath = substr($path, 0, -4);
            try {
                $contents = decrypt($contents);
            } catch (DecryptException $e) {
                return $this->error('Error al descifrar el archivo.', 500);
            }
        } else {
            $mimePath = $path;
        }

        $mimeType = $storage->mimeType($mimePath);

        return [
            'contents' => $contents,
            'mime' => $mimeType,
        ];
    }
}
