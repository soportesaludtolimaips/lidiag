<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

/**
 * Generates a unique filename based on the original name and current timestamp.
 *
 * @param  string  $originalName  The original filename.
 * @return string The unique filename.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-06-10
 */
function generateUniqueFilename($originalName)
{
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filenameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
    $timestamp = time();
    // Puedes agregar más elementos para mayor unicidad, como el ID del usuario
    $uniqueId = uniqid();

    return "{$filenameWithoutExtension}_{$timestamp}_{$uniqueId}.{$extension}";
}

/**
 * Stores a base64-encoded signature image to the public storage disk.
 *
 * @param  string  $base64String  The base64-encoded image data.
 * @param  string  $medicoDoc  The medical doctor's document number.
 * @return string The path to the stored image.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-06-10
 */
function storeSignature($base64String, $medicoDoc)
{
    // 1. Limpiar el string si viene con el prefijo 'data:image/png;base64,'
    if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
        $base64String = substr($base64String, strpos($base64String, ',') + 1);
        $extension = strtolower($type[1]); // png, jpg, etc.
    } else {
        $extension = 'png'; // extensión por defecto
    }

    // 2. Decodificar
    $imageData = base64_decode($base64String);

    if ($imageData === false) {
        throw new \Exception('Base64 inválido');
    }

    // 3. Definir nombre y ruta (ej: firmas/93284421_timestamp.png)
    $fileName = "firmas/{$medicoDoc}_".time().".{$extension}";

    // 4. Guardar en el disco (puedes usar 'public', 's3', etc.)
    Storage::disk('public')->put($fileName, $imageData);

    // Retornamos la ruta para guardarla en la base de datos
    return $fileName;
}
