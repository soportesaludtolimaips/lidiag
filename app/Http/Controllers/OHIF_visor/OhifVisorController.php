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
            return response()->json($result['data'])
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'DICOM, Content-Type, Authorization');
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
                ->header('Content-Type', 'application/dicom')
                ->header('Access-Control-Allow-Origin', '*');
        }

        return response()->json(['error' => $result['message']], $result['status']);
    }

    /**
     * Exporta una imagen del estudio como JPEG.
     * Query: sedeId, study, series, object, conDatos (0/1), rows (opcional).
     */
    public function exportarJpeg(Request $request)
    {
        $result = $this->service->exportarJpeg(
            (int) $request->query('sedeId'),
            (string) $request->query('study'),
            (string) $request->query('series'),
            (string) $request->query('object'),
            $request->boolean('conDatos'),
            $request->query('rows') ? (int) $request->query('rows') : null,
        );

        if ($result['status'] !== 200) {
            return response()->json($result, $result['status']);
        }

        $sufijo = $result['data']['con_datos'] ? 'con_datos' : 'limpia';

        return response($result['data']['body'], 200)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'inline; filename="estudio_'.$sufijo.'.jpg"');
    }

    /**
     * Exporta el JPEG (limpio o con datos) y lo envía al sistema externo configurado.
     */
    public function enviarJpeg(Request $request): JsonResponse
    {
        $exp = $this->service->exportarJpeg(
            (int) $request->query('sedeId'),
            (string) $request->query('study'),
            (string) $request->query('series'),
            (string) $request->query('object'),
            $request->boolean('conDatos'),
            $request->query('rows') ? (int) $request->query('rows') : null,
        );

        if ($exp['status'] !== 200) {
            return response()->json($exp, $exp['status']);
        }

        $filename = 'estudio_'.($exp['data']['con_datos'] ? 'con_datos' : 'limpia').'.jpg';
        $res = $this->service->enviarImagenExterna($exp['data']['body'], $filename);

        return response()->json($res, $res['status']);
    }

    public function qidoStudies(): JsonResponse
    {
        $result = $this->service->qidoStudies();

        return response()->json($result['data'])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'DICOM, Content-Type, Authorization');
    }

    public function qidoSeries(string $studyUID): JsonResponse
    {
        $result = $this->service->qidoSeries($studyUID);

        return response()->json($result['data'])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'DICOM, Content-Type, Authorization');
    }

    public function qidoInstances(string $studyUID, string $seriesUID): JsonResponse
    {
        $result = $this->service->qidoInstances($studyUID, $seriesUID);

        return response()->json($result['data'])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'DICOM, Content-Type, Authorization');
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
