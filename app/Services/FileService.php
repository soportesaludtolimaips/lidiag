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
     * Flujo del proceso:
     * 1. Extrae el nombre original y determina la extensión correcta del archivo.
     * 2. Genera un nombre criptográficamente seguro de 50 caracteres aleatorios.
     * 3. Resuelve defensivamente la ruta temporal del archivo (getRealPath o getPathname) para Windows / mod_fcgid.
     * 4. Almacena el archivo en el disco indicado mediante stream o put directo.
     * 5. Calcula y retorna los metadatos del archivo incluyendo hash SHA-256.
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
        $extension = $file->extension() ?: $file->getClientOriginalExtension();
        $storedName = Str::random(50).($extension ? '.'.$extension : '');
        $targetDirectory = trim($directory ?? '', '/');
        $storedPath = $targetDirectory !== '' ? "{$targetDirectory}/{$storedName}" : $storedName;

        // Resuelve de forma defensiva la ruta del archivo temporal en Windows / FastCGI
        $filePath = $file->getRealPath();
        if (! $filePath || ! file_exists($filePath)) {
            $filePath = $file->getPathname();
        }

        if ($filePath && file_exists($filePath) && is_readable($filePath)) {
            $stream = @fopen($filePath, 'r');
            if (is_resource($stream)) {
                Storage::disk($disk)->put($storedPath, $stream);
                fclose($stream);
            } else {
                Storage::disk($disk)->put($storedPath, (string) file_get_contents($filePath));
            }
        } else {
            $storedPath = $file->storeAs($directory ?? '', $storedName, $disk);
        }

        $info = [
            'path' => $storedPath,
            'filename' => $storedName,
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
        if ($existingPath && trim($existingPath) !== '') {
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
        $path = trim($path);
        if ($path === '') {
            return false;
        }

        if (! Storage::disk($disk)->exists($path)) {
            // Verificar si el archivo existe con el prefijo plantillas_lecturas/
            if (! str_starts_with($path, 'plantillas_lecturas/') && Storage::disk($disk)->exists("plantillas_lecturas/{$path}")) {
                return Storage::disk($disk)->delete("plantillas_lecturas/{$path}");
            }

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
