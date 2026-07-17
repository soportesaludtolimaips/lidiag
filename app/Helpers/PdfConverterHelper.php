<?php

namespace App\Helpers;

class PdfConverterHelper
{
    public static function convert(string $sourcePath, string $destinationPath): bool
    {
        $soffice = config('app.libreoffice_path');
        if (! $soffice) {
            $soffice = base_path('storage/LibreOfficePortable/App/libreoffice/program/soffice.exe');
        }

        if (! file_exists($soffice)) {
            \Log::error("LibreOffice no encontrado en: {$soffice}");

            return false;
        }

        if (! file_exists($sourcePath)) {
            \Log::error("DOCX no encontrado: {$sourcePath}");

            return false;
        }

        $outDir = dirname($destinationPath);
        if (! is_dir($outDir)) {
            @mkdir($outDir, 0775, true);
        }

        $tmpProfile = storage_path('app/lo-profile-'.uniqid());

        $command = sprintf(
            '"%s" -env:UserInstallation=file:///%s --headless --convert-to pdf --outdir "%s" "%s" 2>&1',
            $soffice,
            str_replace('\\', '/', $tmpProfile),
            $outDir,
            $sourcePath
        );

        shell_exec($command);

        if (is_dir($tmpProfile)) {
            @rmdir($tmpProfile);
        }

        if (! file_exists($destinationPath)) {
            \Log::error('Error convirtiendo DOCX a PDF: '.$command);

            return false;
        }

        return true;
    }
}
