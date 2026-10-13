<?php

namespace App\Http\Controllers\Dcm4chee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dcm4chee\StudyListRequest;
use App\Services\Dcm4chee\StudyService;
use Illuminate\Http\JsonResponse;

class StudyController extends Controller
{
    /**
     * Inyecta el servicio de estudios DICOM de Dcm4chee.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly StudyService $service,
    ) {}

    /**
     * Lista los estudios DICOM almacenados en Dcm4chee según los filtros recibidos.
     *
     * @param  StudyListRequest  $request  Solicitud validada con los criterios de búsqueda.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listarEstudios(StudyListRequest $request): JsonResponse
    {
        $result = $this->service->listarEstudios($request->validated());

        return response()->json($result, $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
