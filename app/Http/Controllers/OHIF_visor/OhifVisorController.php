<?php

namespace App\Http\Controllers\OHIF_visor;

use App\Http\Controllers\Controller;
use App\Services\OHIF_visor\OhifVisorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OhifVisorController extends Controller
{
    /**
     * Inyecta el servicio del visor OHIF.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly OhifVisorService $service,
    ) {}

    /**
     * Obtiene los metadatos OHIF de un estudio DICOM por su UID.
     *
     * @param  string  $studyUID  UID del estudio DICOM.
     * @param  string|null  $seriesUID  UID de la serie DICOM (opcional).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function getOhifMetadataOhif(string $studyUID, ?string $seriesUID = null): JsonResponse
    {
        $result = $this->service->getOhifMetadataOhif($studyUID, $seriesUID);

        return response()->json($result['data'] ?? [], $result['status'] ?? 404);
    }

    /**
     * Obtiene los metadatos de un estudio por sede e identificador IUID.
     *
     * @param  int  $sedeId  Identificador de la sede.
     * @param  string  $study_iuid  Identificador IUID del estudio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function getOhifMetadata(int $sedeId, string $study_iuid): JsonResponse
    {
        $result = $this->service->getOhifMetadata($sedeId, $study_iuid);

        return response()->json($result['data'] ?? [], $result['status'] ?? 404);
    }

    /**
     * Lista los estudios DICOM disponibles en una sede para el visor.
     *
     * @param  int  $sedeId  Identificador de la sede.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listStudies(int $sedeId): JsonResponse
    {
        $result = $this->service->listStudies($sedeId);

        return response()->json($result['data'] ?? [], $result['status'] ?? 500);
    }

    /**
     * Descarga un archivo DICOM individual (estudio, serie u objeto) desde el PACS.
     *
     * @param  Request  $request  Solicitud HTTP con los parámetros sedeId, study, series y object.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function downloadDicom(Request $request): JsonResponse
    {
        $result = $this->service->downloadDicom([
            'sedeId' => $request->query('sedeId'),
            'study' => $request->query('study'),
            'series' => $request->query('series'),
            'object' => $request->query('object'),
        ]);

        if (isset($result['data']['body'])) {
            return response($result['data']['body'])
                ->header('Content-Type', $result['data']['content_type'] ?? 'application/dicom');
        }

        return response()->json(['error' => $result['message']], $result['status']);
    }

    /**
     * Expone el servicio QIDO-RS de estudios del PACS.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function qidoStudies(): JsonResponse
    {
        $result = $this->service->qidoStudies();

        return response()->json($result['data'] ?? []);
    }

    /**
     * Expone el servicio QIDO-RS de series de un estudio del PACS.
     *
     * @param  string  $studyUID  UID del estudio DICOM.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function qidoSeries(string $studyUID): JsonResponse
    {
        $result = $this->service->qidoSeries($studyUID);

        return response()->json($result['data'] ?? []);
    }

    /**
     * Expone el servicio QIDO-RS de instancias de una serie del PACS.
     *
     * @param  string  $studyUID  UID del estudio DICOM.
     * @param  string  $seriesUID  UID de la serie DICOM.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function qidoInstances(string $studyUID, string $seriesUID): JsonResponse
    {
        $result = $this->service->qidoInstances($studyUID, $seriesUID);

        return response()->json($result['data'] ?? []);
    }

    /**
     * Recupera una instancia DICOM vía WADO-RS y devuelve su contenido binario.
     *
     * @param  string  $studyUID  UID del estudio DICOM.
     * @param  string  $seriesUID  UID de la serie DICOM.
     * @param  string  $objectUID  UID del objeto (instancia) DICOM.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function wadoRetrieve(string $studyUID, string $seriesUID, string $objectUID)
    {
        $result = $this->service->wadoProxy($studyUID, $seriesUID, $objectUID);

        if (isset($result['data']['body'])) {
            return response($result['data']['body'], 200, [
                'Content-Type' => $result['data']['content_type'] ?? 'image/jpeg',
            ]);
        }

        return response()->json(['error' => $result['message']], $result['status']);
    }

    /**
     * Expone el endpoint WADO-URI delegando en la recuperación por UIDs.
     *
     * @param  Request  $request  Solicitud HTTP con los parámetros studyUID, seriesUID y objectUID.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function wadoUri(Request $request)
    {
        return $this->wadoRetrieve(
            $request->query('studyUID', ''),
            $request->query('seriesUID', ''),
            $request->query('objectUID', '')
        );
    }
}
