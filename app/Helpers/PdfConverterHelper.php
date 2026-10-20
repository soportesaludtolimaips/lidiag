<?php

namespace App\Helpers;

class PdfConverterHelper
{
    /**
     * Convierte un archivo DOCX a PDF utilizando LibreOffice en modo headless.
     *
     * @param  string  $sourcePath  - La ruta del archivo DOCX a convertir.
     * @param  string  $destinationPath  - La ruta donde se guardará el archivo PDF resultante.
     * @return bool - true si la conversión es exitosa, false en caso contrario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public static function convert(string $sourcePath, string $destinationPath): bool
    {
        $sourcePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sourcePath);
        $destinationPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $destinationPath);

        if (! file_exists($sourcePath)) {
            \Log::error("DOCX no encontrado: {$sourcePath}");

            return false;
        }

        $outDir = dirname($destinationPath);
        if (! is_dir($outDir)) {
            @mkdir($outDir, 0775, true);
        }

        try {
            $soffice = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, base_path('storage/app_lidiag/LibreOfficePortable/App/libreoffice/program/soffice.com'));

            $command = escapeshellarg($soffice).' --headless --norestore --convert-to pdf --outdir '.escapeshellarg($outDir).' '.escapeshellarg($sourcePath);

            shell_exec($command.' 2>&1');
        } catch (\Throwable $e) {
            \Log::error('Error ejecutando comando de conversión: '.$e->getMessage());

            return false;
        }

        for ($attempt = 0; $attempt < 15; $attempt++) {
            clearstatcache(true, $destinationPath);
            if (file_exists($destinationPath)) {
                return true;
            }
            usleep(200000); // Esperar 200ms
        }

        \Log::error('Error convirtiendo DOCX a PDF (archivo no generado): '.$command);

        return false;
    }
}
