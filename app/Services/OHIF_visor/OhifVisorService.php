<?php

namespace App\Services\OHIF_visor;

use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Instance;
use App\Models\Dcm4chee\Series;
use App\Models\Dcm4chee\Study;
use App\Services\BaseService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OhifVisorService extends BaseService
{
    /**
     * Obtiene los metadatos DICOM de las instancias de un estudio para el visor OHIF.
     *
     * @param  string  $studyUID  UID del estudio DICOM
     * @param  string|null  $seriesUID  UID de la serie DICOM para filtrar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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
                        "BulkDataURI" => route('api.ohif.wado.retrieve', [
                            'studyUID' => $inst->study_iuid,
                            'seriesUID' => $inst->series_iuid,
                            'objectUID' => $inst->sop_iuid,
                        ]),
                    ],
                ];
            }

            return $this->success($metadata);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Obtiene los metadatos DICOM de un estudio consultando la base de la sede dcm4chee.
     *
     * @param  int  $sedeId  Identificador de la sede con la conexión al PACS
     * @param  string  $study_iuid  UID del estudio DICOM
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function getOhifMetadata(int $sedeId, string $study_iuid): array
    {
        $sedeActual = ConfigSede::find($sedeId);
        if (! $sedeActual) {
            return $this->error('Sede no encontrada.', 404);
        }

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

    /**
     * Lista los estudios recientes de la sede en formato de metadatos QIDO para el visor.
     *
     * @param  int  $sedeId  Identificador de la sede con la conexión al PACS
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Descarga un archivo DICOM del PACS de la sede mediante la petición WADO.
     *
     * @param  array  $params  Parámetros: sedeId, study, series, object
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function downloadDicom(array $params): array
    {
        $sede = ConfigSede::find($params['sedeId'] ?? null);
        if (! $sede) {
            return $this->error('Sede no encontrada.', 404);
        }

        $wadoUrl = "http://{$sede->ip_dcm}:{$sede->puerto_dcm}/wado?requestType=WADO"
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
     * Obtiene la lista de estudios recientes en formato QIDO-RS desde la base dcm4chee local.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Obtiene las series de un estudio en formato QIDO-RS desde la base dcm4chee local.
     *
     * @param  string  $studyUID  UID del estudio DICOM
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Obtiene las instancias de una serie en formato QIDO-RS desde la base dcm4chee local.
     *
     * @param  string  $studyUID  UID del estudio DICOM
     * @param  string  $seriesUID  UID de la serie DICOM
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Recupera una imagen JPEG de una instancia DICOM mediante WADO-RS desde el PACS.
     *
     * @param  string  $studyUID  UID del estudio DICOM
     * @param  string  $seriesUID  UID de la serie DICOM
     * @param  string  $objectUID  UID de la instancia DICOM
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function wadoRetrieve(string $studyUID, string $seriesUID, string $objectUID): array
    {
        return $this->wadoProxy($studyUID, $seriesUID, $objectUID);
    }

    /**
     * Sirve una imagen DICOM vía WADO-URI redirigiendo la petición al PACS de la sede activa.
     *
     * @param  array  $params  Parámetros: studyUID, seriesUID, objectUID
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function wadoUri(array $params): array
    {
        return $this->wadoProxy(
            $params['studyUID'],
            $params['seriesUID'],
            $params['objectUID']
        );
    }

    /**
     * Configura y activa la conexión a la base de datos dcm4chee de la sede indicada.
     *
     * @param  int  $sedeId  Identificador de la sede con la conexión al PACS
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Realiza la petición WADO al PACS de la sede activa y devuelve la imagen JPEG resultante.
     *
     * @param  string  $studyUID  UID del estudio DICOM
     * @param  string  $seriesUID  UID de la serie DICOM
     * @param  string  $objectUID  UID de la instancia DICOM
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function wadoProxy(string $studyUID, string $seriesUID, string $objectUID): array
    {
        $sede = ConfigSede::where('estado', true)->first();
        if (! $sede) {
            return $this->error('No hay una sede activa configurada para conectar al PACS.', 500);
        }

        $wadoUrl = "http://{$sede->ip_dcm}:{$sede->puerto_dcm}/wado?requestType=WADO"
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
