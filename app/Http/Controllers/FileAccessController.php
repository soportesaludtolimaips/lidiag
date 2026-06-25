<?php

namespace App\Http\Controllers;

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
            $contents = decrypt($contents);
        }

        $mimeType = $storage->mimeType($path);

        return response($contents, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }
}
