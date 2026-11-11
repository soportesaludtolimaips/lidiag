<?php

namespace App\Http\Controllers\Importar;

use App\Http\Controllers\Controller;
use App\Services\Importar\ImportarDicomService;
use Illuminate\Http\JsonResponse;

class ImportarDicom extends Controller
{
    /**
     * Inyecta el servicio de importación DICOM.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly ImportarDicomService $service,
    ) {}

    /**
     * Ejecuta la importación de estudios DICOM pendientes al sistema.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function importarDicom(): JsonResponse
    {
        $result = $this->service->importar();

        return response()->json($result, $result["status"]);
    }
}
