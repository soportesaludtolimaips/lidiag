<?php

namespace App\Http\Controllers;

use App\Services\FileAccessService;
use Illuminate\Http\Request;

class FileAccessController extends Controller
{
    /**
     * Inyecta el servicio de acceso a archivos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly FileAccessService $service,
    ) {}

    /**
     * Sirve un archivo desde el disco indicado validando el acceso autorizado.
     *
     * @param  Request  $request  Solicitud HTTP con los datos de acceso.
     * @param  string  $disk  Nombre del disco configurado.
     * @param  string  $path  Ruta relativa del archivo dentro del disco.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function serve(Request $request, string $disk, string $path)
    {
        $result = $this->service->serve($disk, $path);

        if (isset($result['contents'])) {
            return response($result['contents'], 200, [
                'Content-Type' => $result['mime'],
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            ]);
        }

        abort($result['status'], $result['message']);
    }
}
