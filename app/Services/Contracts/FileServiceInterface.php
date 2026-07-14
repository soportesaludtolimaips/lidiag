<?php

namespace App\Services\Contracts;

use Illuminate\Http\UploadedFile;

interface FileServiceInterface
{
    public function store(UploadedFile $file, string $disk, ?string $directory = null): array;

    public function replace(string $disk, ?string $existingPath, UploadedFile $newFile, ?string $directory = null): array;

    public function delete(string $path, string $disk): bool;

    public function verifyIntegrity(string $path, string $disk, string $expectedHash): bool;

    public function hash(string $path, string $disk): ?string;
}
