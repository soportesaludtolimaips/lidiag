<?php

namespace App\Services;

use App\Services\Contracts\FileServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService implements FileServiceInterface
{
    /**
     * Almacena un archivo subido en el disco y ruta indicados, generando nombre aleatorio.
     *
     * @param  UploadedFile  $file  Archivo subido por el usuario
     * @param  string  $disk  Disco de almacenamiento configurado en el sistema
     * @param  string|null  $directory  Subdirectorio destino dentro del disco
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

        return $info;
    }

    /**
     * Reemplaza un archivo existente por uno nuevo, eliminando el anterior si existe.
     *
     * @param  string  $disk  Disco de almacenamiento configurado en el sistema
     * @param  string|null  $existingPath  Ruta del archivo existente a reemplazar
     * @param  UploadedFile  $newFile  Archivo nuevo a almacenar
     * @param  string|null  $directory  Subdirectorio destino dentro del disco
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function replace(string $disk, ?string $existingPath, UploadedFile $newFile, ?string $directory = null): array
    {
        if ($existingPath) {
            $this->delete($existingPath, $disk);
        }

        return $this->store($newFile, $disk, $directory);
    }

    /**
     * Elimina un archivo del disco indicado.
     *
     * @param  string  $path  Ruta relativa del archivo a eliminar
     * @param  string  $disk  Disco de almacenamiento configurado en el sistema
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function delete(string $path, string $disk): bool
    {
        if (! Storage::disk($disk)->exists($path)) {
            return false;
        }

        $deleted = Storage::disk($disk)->delete($path);

        return $deleted;
    }

    /**
     * Verifica la integridad de un archivo comparando su hash con el hash esperado.
     *
     * @param  string  $path  Ruta relativa del archivo a verificar
     * @param  string  $disk  Disco de almacenamiento configurado en el sistema
     * @param  string  $expectedHash  Hash SHA-256 esperado del archivo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function verifyIntegrity(string $path, string $disk, string $expectedHash): bool
    {
        $actual = $this->hash($path, $disk);

        if ($actual === null) {
            return false;
        }

        return hash_equals($expectedHash, $actual);
    }

    /**
     * Calcula el hash SHA-256 de un archivo almacenado en el disco indicado.
     *
     * @param  string  $path  Ruta relativa del archivo
     * @param  string  $disk  Disco de almacenamiento configurado en el sistema
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function hash(string $path, string $disk): ?string
    {
        $fullPath = Storage::disk($disk)->path($path);

        if (! file_exists($fullPath)) {
            return null;
        }

        return hash_file('sha256', $fullPath);
    }
}
