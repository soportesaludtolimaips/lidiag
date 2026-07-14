<?php

namespace App\Http\Controllers\Importar;

use App\Http\Controllers\Controller;
use App\Services\Importar\ImportarDicomService;
use Illuminate\Http\JsonResponse;

class ImportarDicom extends Controller
{
    public function __construct(
        private readonly ImportarDicomService $service,
    ) {}

    public function importarDicom(): JsonResponse
    {
        $result = $this->service->importarDicom();

        return response()->json($result['data'], $result['status']);
    }
}
