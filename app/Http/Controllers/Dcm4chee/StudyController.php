<?php

namespace App\Http\Controllers\Dcm4chee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dcm4chee\StudyListRequest;
use App\Services\Dcm4chee\StudyService;
use Illuminate\Http\JsonResponse;

class StudyController extends Controller
{
    public function __construct(
        private readonly StudyService $service,
    ) {}

    public function listarEstudios(StudyListRequest $request): JsonResponse
    {
        $result = $this->service->listarEstudios($request->validated());

        return response()->json($result, $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
