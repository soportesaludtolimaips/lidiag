<?php

namespace App\Http\Controllers\OHIF_visor;

use App\Http\Controllers\Controller;
use App\Services\OHIF_visor\OhifVisorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OhifVisorController extends Controller
{
    public function __construct(
        private readonly OhifVisorService $service,
    ) {}

    public function getOhifMetadataOhif(string $studyUID, ?string $seriesUID = null): JsonResponse
    {
        $result = $this->service->getOhifMetadataOhif($studyUID, $seriesUID);

        if (! empty($result['data'])) {
            return response()->json($result['data']);
        }

        return response()->json([], 404);
    }

    public function getOhifMetadata(int $sedeId, string $study_iuid): JsonResponse
    {
        $result = $this->service->getOhifMetadata($sedeId, $study_iuid);

        if (! empty($result['data'])) {
            return response()->json($result['data']);
        }

        return response()->json([], 404);
    }

    public function listStudies(int $sedeId): JsonResponse
    {
        $result = $this->service->listStudies($sedeId);

        return response()->json($result, $result["status"]);
    }

    public function downloadDicom(Request $request): JsonResponse
    {
        $result = $this->service->downloadDicom(
            (int) $request->query('sedeId'),
            $request->query('study'),
            $request->query('series'),
            $request->query('object')
        );

        if (isset($result['contents'])) {
            return response($result['contents'])
                ->header('Content-Type', 'application/dicom');
        }

        return response()->json(['error' => $result['message']], $result['status']);
    }

    public function qidoStudies(): JsonResponse
    {
        $result = $this->service->qidoStudies();

        return response()->json($result['data']);
    }

    public function qidoSeries(string $studyUID): JsonResponse
    {
        $result = $this->service->qidoSeries($studyUID);

        return response()->json($result['data']);
    }

    public function qidoInstances(string $studyUID, string $seriesUID): JsonResponse
    {
        $result = $this->service->qidoInstances($studyUID, $seriesUID);

        return response()->json($result['data']);
    }

    public function wadoRetrieve(string $studyUID, string $seriesUID, string $objectUID)
    {
        $result = $this->service->wadoProxy($studyUID, $seriesUID, $objectUID);

        if (isset($result['contents'])) {
            return response($result['contents'], 200, $result['headers']);
        }

        return response()->json(['error' => $result['message']], $result['status']);
    }

    public function wadoUri(Request $request)
    {
        return $this->wadoRetrieve(
            $request->query('studyUID'),
            $request->query('seriesUID'),
            $request->query('objectUID')
        );
    }
}
