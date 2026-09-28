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

    /** Discos accesibles para cualquier usuario autenticado (la ruta ya aplica middleware auth). */
    private array $authOnlyDisks = ['avatars'];

    public function handle(Request $request, Closure $next): Response
    {
        $disk = $request->route('disk');

        if (! $disk || ! array_key_exists($disk, config('filesystems.disks'))) {
            abort(404, 'Disco no encontrado');
        }

        if (isset($this->diskPermissions[$disk])) {
            if (! $request->user()?->can($this->diskPermissions[$disk])) {
                abort(403, 'Forbidden');
            }
        } elseif (! in_array($disk, $this->authOnlyDisks, true)) {
            abort(403, 'Acceso no autorizado a este disco');
        }

        return $next($request);
    }
}
