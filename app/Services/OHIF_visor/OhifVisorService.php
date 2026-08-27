<?php

namespace App\Services\OHIF_visor;

use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Instance;
use App\Models\Dcm4chee\Series;
use App\Models\Dcm4chee\Study;
use App\Models\Estudio\Estudio;
use App\Services\BaseService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OhifVisorService extends BaseService
{
    public function getOhifMetadataOhif(string $studyUID, ?string $seriesUID = null): array
    {
        try {
            $query = Instance::join('series', 'instance.series_fk', '=', 'series.pk')
                ->join('study', 'series.study_fk', '=', 'study.pk')
                ->where('study.study_iuid', $studyUID)
                ->select('series.series_iuid', 'series.modality', 'instance.sop_iuid', 'instance.inst_no', 'study.study_iuid');

            if ($seriesUID) {
                $query->where('series.series_iuid', $seriesUID);
            }

            $instances = $query->get();

            if ($instances->isEmpty()) {
                return $this->success([], 'No instances found', 404);
            }

            $metadata = [];
            foreach ($instances as $inst) {
                $modality = $inst->modality ?: 'OT';
                $metadata[] = [
                    "StudyInstanceUID" => ["vr" => "UI", "Value" => [$inst->study_iuid]],
                    "SeriesInstanceUID" => ["vr" => "UI", "Value" => [$inst->series_iuid]],
                    "SOPInstanceUID" => ["vr" => "UI", "Value" => [$inst->sop_iuid]],
                    "SOPClassUID" => ["vr" => "UI", "Value" => ["1.2.840.10008.5.1.4.1.1.2"]],
                    "Modality" => ["vr" => "CS", "Value" => [$modality]],
                    "InstanceNumber" => ["vr" => "IS", "Value" => (string) $inst->inst_no],
                    "7FE00010" => [
                        "vr" => "OW",
                        "BulkDataURI" => "https://lidiag.test/api/wado/{$inst->study_iuid}/{$inst->series_iuid}/{$inst->sop_iuid}",
                    ],
                ];
            }

            return $this->success($metadata);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function getOhifMetadata(int $sedeId, string $study_iuid): array
    {
        $sedeActual = ConfigSede::find($sedeId);

        config(['database.connections.mysql_sucursal' => [
            'driver' => 'mysql',
            'host' => env('DB_SUCURSAL_HOST', $sedeActual->ip_dcm),
            'port' => env('DB_SUCURSAL_PUERTO', $sedeActual->puerto_dcm),
            'database' => env('DB_SUCURSAL_DATABASE', $sedeActual->bd_dcm),
            'username' => env('DB_SUCURSAL_USERNAME', $sedeActual->usuario_dcm),
            'password' => env('DB_SUCURSAL_PASSWORD', $sedeActual->password_dcm),
            'charset' => env('DB_SUCURSAL_charset', 'latin1'),
            'collation' => env('DB_SUCURSAL_collation', 'latin1_swedish_ci'),
        ]]);

        $originalDefault = Config::get('database.default');
        Config::set('database.default', 'mysql_sucursal');

        try {
            $imagenes = Instance::join('series', 'instance.series_fk', '=', 'series.pk')
                ->join('study', 'series.study_fk', '=', 'study.pk')
                ->where('study.study_iuid', $study_iuid)
                ->select('series.series_iuid', 'instance.sop_iuid', 'study.study_iuid')
                ->get();

            if ($imagenes->isEmpty()) {
                return $this->success([], 'No images found', 404);
            }

            $metadata = [];
            foreach ($imagenes as $imagen) {
                $metadata[] = [
                    "0020000D" => ["vr" => "UI", "Value" => [$imagen->study_iuid]],
                    "0020000E" => ["vr" => "UI", "Value" => [$imagen->series_iuid]],
                    "00080018" => ["vr" => "UI", "Value" => [$imagen->sop_iuid]],
                    "00080060" => ["vr" => "CS", "Value" => ["CR"]],
                    "7FE00010" => [
                        "vr" => "OW",
                        "BulkDataURI" => route('dicom.download', [
                            'sedeId' => $sedeId,
                            'study' => $imagen->study_iuid,
                            'series' => $imagen->series_iuid,
                            'object' => $imagen->sop_iuid,
                        ]),
                    ],
                ];
            }

            return $this->success($metadata);
        } finally {
            Config::set('database.default', $originalDefault);
        }
    }

    public function listStudies(int $sedeId): array
    {
        $originalDefault = Config::get('database.default');
        try {
            $this->conectarSede($sedeId);

            $estudios = DB::connection('mysql_sucursal')->table('study')
                ->join('patient', 'study.patient_fk', '=', 'patient.pk')
                ->select(
                    'study.study_iuid',
                    'study.study_id',
                    'study.study_datetime',
                    'study.mods_in_study',
                    'study.num_instances',
                    'patient.pat_id',
                    'patient.pat_name',
                    'patient.pat_birthdate'
                )
                ->orderBy('study.study_datetime', 'desc')
                ->limit(100)
                ->get();

            $response = [];
            foreach ($estudios as $s) {
                $response[] = [
                    "0020000D" => ["vr" => "UI", "Value" => [$s->study_iuid]],
                    "00100010" => ["vr" => "PN", "Value" => [["Alphabetic" => $s->pat_name]]],
                    "00100020" => ["vr" => "LO", "Value" => [$s->pat_id]],
                    "00080020" => ["vr" => "DA", "Value" => [date('Ymd', strtotime($s->study_datetime))]],
                    "00080030" => ["vr" => "TM", "Value" => [date('His', strtotime($s->study_datetime))]],
                    "00080061" => ["vr" => "CS", "Value" => explode('\\', $s->mods_in_study)],
                    "00081030" => ["vr" => "LO", "Value" => [$s->study_id ?? '']],
                    "00201208" => ["vr" => "IS", "Value" => [(string) $s->num_instances]],
                ];
            }

            return $this->success($response);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        } finally {
            Config::set('database.default', $originalDefault);
        }
    }

    public function downloadDicom(array $params): array
    {
        $sede = ConfigSede::find($params['sedeId']);

        $wadoUrl = "http://{$sede->ip_dcm}:8080/wado?requestType=WADO"
            ."&studyUID={$params['study']}"
            ."&seriesUID={$params['series']}"
            ."&objectUID={$params['object']}"
            ."&contentType=application/dicom"
            ."&transferSyntax=1.2.840.10008.1.2.1";

        $response = Http::get($wadoUrl);

        if ($response->successful()) {
            return $this->success([
                'body' => $response->body(),
                'content_type' => 'application/dicom',
            ]);
        }

        return $this->error('Error al obtener DICOM', 500);
    }

    /**
     * Exporta una imagen del estudio como JPEG desde el PACS (WADO), para
     * enviarla a sistemas externos que solo aceptan JPG/JPEG.
     *
     * @param  bool  $conDatos  false = imagen limpia (solo el estudio);
     *                          true  = con datos del paciente/técnica quemados
     *                          sobre la imagen (estilo visor K-PACS),
     *                          usando el parámetro WADO `annotation`.
     */
    public function exportarJpeg(
        int $sedeId,
        string $study,
        string $series,
        string $object,
        bool $conDatos = false,
        ?int $rows = null
    ): array {
        $sede = ConfigSede::find($sedeId);
        if (! $sede) {
            return $this->notFound('Sede no encontrada');
        }

        // Base configurable: soporta dcm4chee 2.x (/wado) y arc 5.x (/dcm4chee-arc/aets/{aet}/wado)
        $base = str_replace(
            ['{ip}', '{aet}'],
            [(string) $sede->ip_dcm, (string) config('services.pacs.aet', 'DCM4CHEE')],
            (string) config('services.pacs.wado_url', 'http://{ip}:8080/wado')
        );

        $wadoUrl = $base.'?requestType=WADO'
            ."&studyUID={$study}"
            ."&seriesUID={$series}"
            ."&objectUID={$object}"
            .'&contentType=image/jpeg';

        if ($rows) {
            $wadoUrl .= "&rows={$rows}";
        }

        try {
            $response = Http::connectTimeout((int) config('services.pacs.connect_timeout', 15))
                ->timeout((int) config('services.pacs.timeout', 60))
                ->withHeaders(['Accept' => 'image/jpeg'])
                ->get($wadoUrl);

            if (! $response->successful()) {
                return $this->error('Error al obtener JPEG del PACS', $response->status(), ['details' => $response->body()]);
            }

            $body = $response->body();

            // El overlay tipo K-PACS se quema en la app (dcm4chee-arc 5.x ignora
            // el parámetro WADO `annotation`), usando datos de la BD de LIDiag.
            if ($conDatos) {
                $body = $this->quemarOverlay($body, $study, (string) $sede->ip_dcm);
            }

            return $this->success([
                'body' => $body,
                'content_type' => 'image/jpeg',
                'con_datos' => $conDatos,
            ]);
        } catch (\Throwable $e) {
            return $this->error('Fallo de conexión con el PACS: '.$e->getMessage(), 500);
        }
    }

    /**
     * Construye las líneas del overlay: primero desde la BD de LIDiag
     * (Estudio→Paciente); si no hay estudio local, cae al QIDO-RS del PACS.
     *
     * @return array{0: array<int, string>, 1: array<int, string>} [superiorIzq, superiorDer]
     */
    private function datosOverlay(string $studyUID, string $ip = ""): array
    {
        $estudio = Estudio::query()->with('paciente')->where('study_iuid', $studyUID)->first();

        if ($estudio && $estudio->paciente) {
            $p = $estudio->paciente;

            return [
                array_values(array_filter([
                    trim((string) ($p->nombres.' '.$p->pri_ape.' '.$p->seg_ape)),
                    $p->num_docu ? 'Doc: '.$p->num_docu : null,
                    $p->fec_naci ? 'Nac: '.$p->fec_naci : null,
                ])),
                array_values(array_filter([
                    $estudio->created_at ? 'Fecha: '.$estudio->created_at->format('Y-m-d H:i') : null,
                    $p->sexo ? 'Sexo: '.$p->sexo : null,
                ])),
            ];
        }

        return $this->overlayDesdeQido($studyUID, $ip);
    }

    /**
     * Fallback: obtiene demografía del estudio desde el QIDO-RS del PACS
     * (solo dcm4chee-arc 5.x; requiere services.pacs.qido_url configurado).
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function overlayDesdeQido(string $studyUID, string $ip = ""): array
    {
        $qidoBase = (string) config('services.pacs.qido_url', '');
        if ($qidoBase === '') {
            return [[], []];
        }

        $qidoBase = str_replace(['{ip}', '{aet}'], [$ip, (string) config('services.pacs.aet', 'DCM4CHEE')], $qidoBase);

        try {
            $resp = Http::connectTimeout((int) config('services.pacs.connect_timeout', 15))
                ->timeout((int) config('services.pacs.timeout', 60))
                ->acceptJson()
                ->get(rtrim($qidoBase, '/')."/studies", ['StudyInstanceUID' => $studyUID, 'limit' => 1]);

            if (! $resp->successful()) {
                return [[], []];
            }
            $j = $resp->json()[0] ?? null;
            if (! $j) {
                return [[], []];
            }

            $nombre = $j['00100010']['Value'][0]['Alphabetic'] ?? '';
            $docId = $j['00100020']['Value'][0] ?? '';
            $nac = $j['00100030']['Value'][0] ?? '';
            $fecha = $j['00080020']['Value'][0] ?? '';
            $mods = $j['00080061']['Value'] ?? [];

            return [
                array_values(array_filter([
                    $nombre ? str_replace('^', ' ', (string) $nombre) : null,
                    $docId ? 'ID: '.$docId : null,
                    $nac ? 'Nac: '.$nac : null,
                ])),
                array_values(array_filter([
                    $fecha ? 'Fecha: '.$fecha : null,
                    $mods ? 'Mod: '.implode(',', (array) $mods) : null,
                ])),
            ];
        } catch (\Throwable $e) {
            return [[], []];
        }
    }

    /**
     * Quema sobre el JPEG los datos del paciente/estudio (estilo visor K-PACS),
     * tomados de la BD de LIDiag por study_iuid. Si algo falla, devuelve el
     * JPEG original sin overlay.
     */
    private function quemarOverlay(string $jpeg, string $studyUID, string $ip = ""): string
    {
        $img = @imagecreatefromstring($jpeg);
        if (! $img) {
            return $jpeg;
        }

        [$superior, $superiorDer] = $this->datosOverlay($studyUID, $ip);

        $amarillo = imagecolorallocate($img, 255, 255, 0);
        $negro = imagecolorallocate($img, 0, 0, 0);
        $font = 3;
        $lineH = imagefontheight($font) + 2;

        // Esquina superior izquierda (demografía)
        $y = 4;
        foreach ($superior as $linea) {
            imagestring($img, $font, 5, $y + 1, $linea, $negro); // sombra
            imagestring($img, $font, 4, $y, $linea, $amarillo);
            $y += $lineH;
        }

        // Esquina superior derecha (fecha/estudio)
        $w = imagesx($img);
        $y = 4;
        foreach ($superiorDer as $linea) {
            $x = $w - (imagefontwidth($font) * strlen($linea)) - 5;
            imagestring($img, $font, $x + 1, $y + 1, $linea, $negro);
            imagestring($img, $font, $x, $y, $linea, $amarillo);
            $y += $lineH;
        }

        ob_start();
        imagejpeg($img, null, 90);
        $out = (string) ob_get_clean();
        imagedestroy($img);

        return $out !== '' ? $out : $jpeg;
    }

    /**
     * Envía un JPEG al sistema externo configurado (services.imagen_externa).
     * Soporta multipart (archivo) o base64 (JSON) según IMAGEN_EXTERNA_METODO.
     */
    public function enviarImagenExterna(string $jpeg, string $filename = 'estudio.jpg'): array
    {
        if (! config('services.imagen_externa.enabled')) {
            return $this->error('Envío a sistema externo deshabilitado (IMAGEN_EXTERNA_ENABLED=false)', 422);
        }

        $url = (string) config('services.imagen_externa.url', '');
        if ($url === '') {
            return $this->error('Falta configurar IMAGEN_EXTERNA_URL', 422);
        }

        $token = config('services.imagen_externa.token');
        $metodo = (string) config('services.imagen_externa.metodo', 'multipart');
        $campo = (string) config('services.imagen_externa.campo_archivo', 'file');

        try {
            $req = Http::connectTimeout((int) config('services.imagen_externa.connect_timeout', 15))
                ->timeout((int) config('services.imagen_externa.timeout', 60));

            if ($token) {
                $req = $req->withToken($token);
            }

            $resp = $metodo === 'base64'
                ? $req->post($url, [$campo => base64_encode($jpeg), 'filename' => $filename])
                : $req->attach($campo, $jpeg, $filename)->post($url);

            if ($resp->successful()) {
                return $this->success([
                    'receptor_status' => $resp->status(),
                    'respuesta' => $resp->json() ?? $resp->body(),
                ], 'Imagen enviada al sistema externo');
            }

            return $this->error('El receptor respondió error', $resp->status(), ['details' => $resp->body()]);
        } catch (\Throwable $e) {
            return $this->error('Fallo al enviar al receptor: '.$e->getMessage(), 500);
        }
    }

    public function qidoStudies(): array
    {
        $estudios = Study::with(['patient:pk,pat_id,pat_name', 'series'])
            ->select(
                'pk',
                'study_iuid',
                'study_id',
                'study_datetime',
                'mods_in_study',
                'num_instances',
                'patient_fk'
            )
            ->orderBy('study_datetime', 'desc')
            ->limit(10)
            ->get();

        $response = [];
        foreach ($estudios as $s) {
            $patientName = $s->patient->pat_name ?? '';
            if (is_string($patientName) && strpos($patientName, '^') !== false) {
                $parts = explode('^', $patientName);
                $patientName = ['Alphabetic' => ($parts[1] ?? '').'^'.($parts[0] ?? '')];
            }

            $studyDate = $s->study_datetime ? date('Ymd', strtotime($s->study_datetime)) : '';
            $studyTime = $s->study_datetime ? date('His', strtotime($s->study_datetime)) : '';
            $modalities = explode('\\', $s->mods_in_study);

            $response[] = [
                "StudyInstanceUID" => ["vr" => "UI", "Value" => [$s->study_iuid]],
                "PatientName" => ["vr" => "PN", "Value" => [$patientName]],
                "PatientID" => ["vr" => "LO", "Value" => [$s->patient->pat_id ?? '']],
                "StudyDate" => ["vr" => "DA", "Value" => [$studyDate]],
                "StudyTime" => ["vr" => "TM", "Value" => [$studyTime]],
                "ModalitiesInStudy" => ["vr" => "CS", "Value" => $modalities],
                "StudyDescription" => ["vr" => "LO", "Value" => [$s->study_id ?? '']],
                "NumberOfStudyRelatedSeries" => ["vr" => "IS", "Value" => [(string) $s->series->count()]],
                "NumberOfStudyRelatedInstances" => ["vr" => "IS", "Value" => [(string) $s->num_instances]],
                "studyInstanceUid" => $s->study_iuid,
                "patientName" => is_array($patientName) ? ($patientName['Alphabetic'] ?? '') : $patientName,
                "patientId" => $s->patient->pat_id ?? '',
                "studyDate" => $studyDate,
                "studyTime" => $studyTime,
                "modalitiesInStudy" => $modalities,
                "studyDescription" => $s->study_id ?? '',
                "numberOfStudyRelatedSeries" => $s->series->count(),
                "numberOfStudyRelatedInstances" => (int) $s->num_instances,
            ];
        }

        return $this->success($response);
    }

    public function qidoSeries(string $studyUID): array
    {
        try {
            $series = Series::with('study')
                ->whereHas('study', function ($q) use ($studyUID) {
                    $q->where('study_iuid', $studyUID);
                })
                ->select('pk', 'series_iuid', 'series_no', 'modality', 'num_instances', 'study_fk')
                ->get();

            $response = [];
            foreach ($series as $s) {
                $response[] = [
                    "SeriesInstanceUID" => ["vr" => "UI", "Value" => [$s->series_iuid]],
                    "SeriesNumber" => ["vr" => "IS", "Value" => [$s->series_no]],
                    "Modality" => ["vr" => "CS", "Value" => [$s->modality]],
                    "NumberOfSeriesRelatedInstances" => ["vr" => "IS", "Value" => (string) $s->num_instances],
                ];
            }

            return $this->success($response);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function qidoInstances(string $studyUID, string $seriesUID): array
    {
        try {
            $instances = Instance::join('series', 'instance.series_fk', '=', 'series.pk')
                ->join('study', 'series.study_fk', '=', 'study.pk')
                ->where('study.study_iuid', $studyUID)
                ->where('series.series_iuid', $seriesUID)
                ->select('instance.sop_iuid', 'instance.inst_no')
                ->get();

            $response = [];
            foreach ($instances as $inst) {
                $response[] = [
                    "SOPInstanceUID" => ["vr" => "UI", "Value" => [$inst->sop_iuid]],
                    "InstanceNumber" => ["vr" => "IS", "Value" => (string) $inst->inst_no],
                ];
            }

            return $this->success($response);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function wadoRetrieve(string $studyUID, string $seriesUID, string $objectUID): array
    {
        return $this->wadoProxy($studyUID, $seriesUID, $objectUID);
    }

    public function wadoUri(array $params): array
    {
        return $this->wadoProxy(
            $params['studyUID'],
            $params['seriesUID'],
            $params['objectUID']
        );
    }

    private function conectarSede(int $sedeId): void
    {
        $sedeActual = ConfigSede::findOrFail($sedeId);

        config(['database.connections.mysql_sucursal' => [
            'driver' => 'mysql',
            'host' => $sedeActual->ip_dcm,
            'port' => $sedeActual->puerto_dcm,
            'database' => $sedeActual->bd_dcm,
            'username' => $sedeActual->usuario_dcm,
            'password' => $sedeActual->password_dcm,
            'charset' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'prefix' => '',
            'strict' => false,
        ]]);

        DB::purge('mysql_sucursal');
        Config::set('database.default', 'mysql_sucursal');
    }

    private function wadoProxy(string $studyUID, string $seriesUID, string $objectUID): array
    {
        $dcmIp = '10.35.47.3';

        $wadoUrl = "http://{$dcmIp}:8080/wado?requestType=WADO"
            ."&studyUID={$studyUID}"
            ."&seriesUID={$seriesUID}"
            ."&objectUID={$objectUID}"
            ."&contentType=image/jpeg";

        try {
            $response = Http::withHeaders([
                'Accept' => 'image/jpeg',
            ])->get($wadoUrl);

            if ($response->successful()) {
                return $this->success([
                    'body' => $response->body(),
                    'content_type' => 'image/jpeg',
                ]);
            }

            return $this->error('Error al obtener DICOM', $response->status(), ['details' => $response->body()]);
        } catch (\Exception $e) {
            return $this->error('Connection failed: '.$e->getMessage(), 500);
        }
    }
}
