<?php

namespace App\Http\Controllers\OHIF_visor;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Instance;
use App\Models\Dcm4chee\Series;
use App\Models\Dcm4chee\Study;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OhifVisorController extends Controller
{
    /**
     * OHIF Metadata - version sin sedes
     */
    public function getOhifMetadataOhif($studyUID, $seriesUID = null)
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
                return response()->json([], 404);
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

            return response()->json($metadata)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'DICOM, Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getOhifMetadata($sedeId, $study_iuid)
    {

        $sedeActual = ConfigSede::find($sedeId);

        // Configuramos la segunda base de datos
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

        // Aplicamos la nueva configuracion de bade datos
        Config::set('database.default', 'mysql_sucursal');

        // Tu consulta Eloquent original
        $imagenes = Instance::join('series', 'instance.series_fk', '=', 'series.pk')
            ->join('study', 'series.study_fk', '=', 'study.pk')
            ->where('study.study_iuid', $study_iuid)
            ->select('series.series_iuid', 'instance.sop_iuid', 'study.study_iuid')
            ->get();

        if ($imagenes->isEmpty()) {
            return response()->json([], 404);
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
                        'sedeId' => $sedeId, // <--- IMPORTANTÍSIMO: Pásalo aquí
                        'study' => $imagen->study_iuid,
                        'series' => $imagen->series_iuid,
                        'object' => $imagen->sop_iuid,
                    ]),
                ],
            ];
        }

        return response()->json($metadata);
    }

    /**
     * Establece la conexión dinámica a la base de datos de la sede.
     */
    private function conectarSede($sedeId)
    {
        $sedeActual = ConfigSede::findOrFail($sedeId);

        // Definimos la configuración para la conexión 'mysql_sucursal'
        config(['database.connections.mysql_sucursal' => [
            'driver' => 'mysql',
            'host' => $sedeActual->ip_dcm,
            'port' => $sedeActual->puerto_dcm,
            'database' => $sedeActual->bd_dcm,
            'username' => $sedeActual->usuario_dcm,
            'password' => $sedeActual->password_dcm,
            'charset' => 'latin1', // Muy importante para dcm4chee 2.16
            'collation' => 'latin1_swedish_ci',
            'prefix' => '',
            'strict' => false,
        ]]);

        // Limpiamos cualquier caché de conexión previa y establecemos la nueva por defecto
        DB::purge('mysql_sucursal');
        Config::set('database.default', 'mysql_sucursal');
    }

    /**
     * Lista los estudios para la tabla de búsqueda de OHIF (QIDO-RS).
     */
    public function listStudies($sedeId)
    {
        return $this->getStudiesForSede($sedeId);
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
                // El formato de respuesta debe ser un array de objetos DICOM JSON
                $response[] = [
                    "0020000D" => ["vr" => "UI", "Value" => [$s->study_iuid]], // StudyInstanceUID
                    "00100010" => ["vr" => "PN", "Value" => [["Alphabetic" => $s->pat_name]]], // PatientName
                    "00100020" => ["vr" => "LO", "Value" => [$s->pat_id]], // PatientID
                    "00080020" => ["vr" => "DA", "Value" => [date('Ymd', strtotime($s->study_datetime))]], // StudyDate
                    "00080030" => ["vr" => "TM", "Value" => [date('His', strtotime($s->study_datetime))]], // StudyTime
                    "00080061" => ["vr" => "CS", "Value" => explode('\\', $s->mods_in_study)], // Modalidades
                    "00081030" => ["vr" => "LO", "Value" => [$s->study_id ?? '']], // StudyDescription
                    "00201208" => ["vr" => "IS", "Value" => [(string) $s->num_instances]], // NumberOfStudyRelatedInstances
                ];
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function downloadDicom(Request $request)
    {
        $sede = ConfigSede::find($request->query('sedeId'));
        $study = $request->query('study');
        $series = $request->query('series');
        $object = $request->query('object');

        $wadoUrl = "http://{$sede->ip_dcm}:8080/wado?requestType=WADO"
            ."&studyUID={$study}"
            ."&seriesUID={$series}"
            ."&objectUID={$object}"
            ."&contentType=application/dicom"
            ."&transferSyntax=1.2.840.10008.1.2.1";

        $response = Http::get($wadoUrl);

        if ($response->successful()) {
            return response($response->body())
                ->header('Content-Type', 'application/dicom')
                ->header('Access-Control-Allow-Origin', '*');
        }

        return response()->json(['error' => 'Error al obtener DICOM'], 500);
    }

    /**
     * QIDO-RS: Buscar estudios (para OHIF Viewer) - SIN SEDES
     */
    public function qidoStudies(Request $request)
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
                // Formato DICOM standard
                "StudyInstanceUID" => ["vr" => "UI", "Value" => [$s->study_iuid]],
                "PatientName" => ["vr" => "PN", "Value" => [$patientName]],
                "PatientID" => ["vr" => "LO", "Value" => [$s->patient->pat_id ?? '']],
                "StudyDate" => ["vr" => "DA", "Value" => [$studyDate]],
                "StudyTime" => ["vr" => "TM", "Value" => [$studyTime]],
                "ModalitiesInStudy" => ["vr" => "CS", "Value" => $modalities],
                "StudyDescription" => ["vr" => "LO", "Value" => [$s->study_id ?? '']],
                "NumberOfStudyRelatedSeries" => ["vr" => "IS", "Value" => [(string) $s->series->count()]],
                "NumberOfStudyRelatedInstances" => ["vr" => "IS", "Value" => [(string) $s->num_instances]],
                // Formato OHIF (camelCase) - necesario para StudyBrowser
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

        return response()->json($response)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'DICOM, Content-Type, Authorization');
    }

    /**
     * QIDO-RS: Buscar series de un estudio
     */
    public function qidoSeries($studyUID)
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

            return response()->json($response)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'DICOM, Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * QIDO-RS: Buscar instances de una serie
     */
    public function qidoInstances($studyUID, $seriesUID)
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

            return response()->json($response)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'DICOM, Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * WADO-RS: Recuperar instancia DICOM
     */
    public function wadoRetrieve($studyUID, $seriesUID, $objectUID)
    {
        return $this->wadoProxy($studyUID, $seriesUID, $objectUID);
    }

    /**
     * WADO-URI: Recuperar imagen (alternativo)
     */
    public function wadoUri(Request $request)
    {
        return $this->wadoProxy(
            $request->query('studyUID'),
            $request->query('seriesUID'),
            $request->query('objectUID')
        );
    }

    /**
     * Proxy interno para obtener DICOM de dcm4chee
     */
    private function wadoProxy($studyUID, $seriesUID, $objectUID)
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
                return response($response->body(), 200, [
                    'Content-Type' => 'image/jpeg',
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => 'DICOM, Content-Type, Authorization',
                ]);
            }

            return response()->json(['error' => 'Error al obtener DICOM', 'details' => $response->body()], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Connection failed', 'message' => $e->getMessage()], 500);
        }
    }
}
