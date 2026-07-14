<?php

namespace App\Services;

use App\Services\Contracts\FileServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService implements FileServiceInterface
{
    public function store(UploadedFile $file, string $disk, ?string $directory = null): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->extension();
        $storedName = Str::random(50).'.'.$extension;
        $storedPath = $file->storeAs($directory ?? '', $storedName, $disk);

        $info = [
            'path' => $storedPath,
            'hash' => $this->hash($storedPath, $disk),
            'original_name' => $originalName,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ];

        activity()
            ->withProperties(['disk' => $disk, 'hash' => $info['hash'], 'original_name' => $originalName])
            ->event('archivo_subido')
            ->log("Archivo subido: {$originalName}");

        return $info;
    }

    public function replace(string $disk, ?string $existingPath, UploadedFile $newFile, ?string $directory = null): array
    {
        if ($existingPath) {
            $this->delete($existingPath, $disk);
        }

        return $this->store($newFile, $disk, $directory);
    }

    public function delete(string $path, string $disk): bool
    {
        if (! Storage::disk($disk)->exists($path)) {
            return false;
        }

        $deleted = Storage::disk($disk)->delete($path);

        if ($deleted) {
            activity()
                ->withProperties(['disk' => $disk, 'path' => $path])
                ->event('archivo_eliminado')
                ->log("Archivo eliminado: {$path}");
        }

        return $deleted;
    }

    public function verifyIntegrity(string $path, string $disk, string $expectedHash): bool
    {
        $actual = $this->hash($path, $disk);

        if ($actual === null) {
            return false;
        }

        return hash_equals($expectedHash, $actual);
    }

    public function hash(string $path, string $disk): ?string
    {
        $fullPath = Storage::disk($disk)->path($path);

        if (! file_exists($fullPath)) {
            return null;
        }

        return hash_file('sha256', $fullPath);
    }
}
