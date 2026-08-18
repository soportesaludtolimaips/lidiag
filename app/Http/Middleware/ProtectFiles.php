<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectFiles
{
    private array $diskPermissions = [
        'firmas_consntimientos' => 'ecografias.consentimientos.show',
        'files_consentimientos' => 'ecografias.consentimientos.pdf.descargar',
        'reporte_lecturas' => 'ecografias.reporte',
        'reporte_lecturas_ecografias' => 'ecografias.reporte',
        'firmas_medicos' => 'ecografias.index',
        'img_dicom' => 'ecografias.leer',
        'plantillas_lecturas' => 'ecografias.reporte',
        'soportes_hc' => 'estudios.index',
        'audios' => 'estudios.leer',
        'files_help_desk' => 'help-desk.index',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $disk = $request->route('disk');

        if ($disk && isset($this->diskPermissions[$disk])) {
            $permission = $this->diskPermissions[$disk];

            if (! $request->user()?->can($permission)) {
                abort(403, 'Forbidden');
            }
        }

        return $next($request);
    }
}
