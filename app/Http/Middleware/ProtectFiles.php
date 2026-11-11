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

    /**
     * Valida el acceso al disco solicitado antes de continuar con la petición.
     *
     * Responde 404 si el disco no existe y 403 si el usuario no tiene el
     * permiso configurado para el disco ni pertenece a los discos permitidos.
     *
     * @param  Request  $request  Petición HTTP actual.
     * @param  Closure  $next  Siguiente middleware de la cadena.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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
