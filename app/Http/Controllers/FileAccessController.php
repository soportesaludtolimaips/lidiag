<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileAccessController extends Controller
{
    public function serve(Request $request, string $disk, string $path)
    {
        if (! array_key_exists($disk, config('filesystems.disks'))) {
            abort(404);
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            abort(404);
        }

        $contents = $storage->get($path);

        if (str_ends_with($path, '.enc')) {
            $mimePath = substr($path, 0, -4);
            try {
                $contents = decrypt($contents);
            } catch (DecryptException $e) {
                abort(500, 'Error al descifrar el archivo.');
            }
        } else {
            $mimePath = $path;
        }

        $mimeType = $storage->mimeType($mimePath);

        return response($contents, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }
}
