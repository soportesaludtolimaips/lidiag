<?php

namespace App\Services\Contracts;

use Illuminate\Http\UploadedFile;

interface FileServiceInterface
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
    public function store(UploadedFile $file, string $disk, ?string $directory = null): array;

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
    public function replace(string $disk, ?string $existingPath, UploadedFile $newFile, ?string $directory = null): array;

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
    public function delete(string $path, string $disk): bool;

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
    public function verifyIntegrity(string $path, string $disk, string $expectedHash): bool;

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
    public function hash(string $path, string $disk): ?string;
}
