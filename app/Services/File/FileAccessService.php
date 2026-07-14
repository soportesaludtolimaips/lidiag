<?php

namespace App\Services\File;

use App\Services\BaseService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Storage;

class FileAccessService extends BaseService
{
    public function serve(string $disk, string $path): mixed
    {
        if (! array_key_exists($disk, config('filesystems.disks'))) {
            return $this->notFound('Disco no encontrado.');
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            return $this->notFound('Archivo no encontrado.');
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

        return $this->success([
            'contents' => $contents,
            'mime_type' => $mimeType,
        ]);
    }
}
