<?php

namespace App\Services\Estudios;

use App\Models\Estudio\EstudioSoportesHC;
use App\Services\BaseService;

class EstudioSoporteHCService extends BaseService
{
    public function listarPorEstudio(int $estudioId): array
    {
        return $this->success(
            EstudioSoportesHC::where('estudio_id', $estudioId)->get()
        );
    }

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
