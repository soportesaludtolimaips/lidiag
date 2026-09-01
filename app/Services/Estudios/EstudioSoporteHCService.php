<?php

namespace App\Services\Estudios;

use App\Models\Estudio\EstudioSoportesHC;
use App\Services\BaseService;

class EstudioSoporteHCService extends BaseService
{
    /**
     * Lista los soportes de historia clínica asociados a un estudio
     *
     * @param  int  $estudioId  ID del estudio
     *
     * @returns array Respuesta con la lista de soportes del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarPorEstudio(int $estudioId): array
    {
        return $this->success(
            EstudioSoportesHC::where('estudio_id', $estudioId)->get()
        );
    }

    /**
     * Descarga un archivo soporte de historia clínica encriptado
     *
     * @param  string  $filename  Nombre del archivo encriptado a descargar
     *
     * @returns array Respuesta con la ruta y nombre del archivo o error 404
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function descargar(string $filename): array
    {
        $path = storage_path('app/soportes_hc/'.$filename);

        if (! file_exists($path)) {
            return $this->notFound('Archivo no encontrado');
        }

        return $this->success([
            'path' => $path,
            'filename' => $filename,
        ]);
    }
}
