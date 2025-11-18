<?php

namespace App\Helpers;

function generateUniqueFilename($originalName)
{
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filenameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
    $timestamp = time();
    // Puedes agregar más elementos para mayor unicidad, como el ID del usuario
    $uniqueId = uniqid();

    return "{$filenameWithoutExtension}_{$timestamp}_{$uniqueId}.{$extension}";
}
