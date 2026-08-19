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
        if (! file_exists($sourcePath)) {
            \Log::error("DOCX no encontrado: {$sourcePath}");

            return false;
        }

        $outDir = dirname($destinationPath);
        if (! is_dir($outDir)) {
            @mkdir($outDir, 0775, true);
        }

        try {
            $soffice = base_path('storage/LibreOfficePortable/App/libreoffice/program/soffice.com');

            $command = escapeshellcmd('"'.$soffice.'" --headless --norestore --convert-to pdf --outdir "'.$outDir.'" "'.$sourcePath.'"');

            shell_exec($command.' 2>&1');
        } catch (\Exception $e) {
            \Log::error('Error ejecutando comando de conversión: '.$e->getMessage());

            return false;
        }

        if (! file_exists($destinationPath)) {
            \Log::error('Error convirtiendo DOCX a PDF: '.$command);

            return false;
        }

        return true;
    }
}
