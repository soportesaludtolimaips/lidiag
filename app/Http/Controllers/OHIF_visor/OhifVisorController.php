<?php

namespace App\Http\Controllers\OHIF_visor;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Http\Request;
use App\Models\Dcm4chee\Instance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class OhifVisorController extends Controller
{
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
                        'study'  => $imagen->study_iuid,
                        'series' => $imagen->series_iuid,
                        'object' => $imagen->sop_iuid
                    ])
                ]
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
            'driver'    => 'mysql',
            'host'      => $sedeActual->ip_dcm,
            'port'      => $sedeActual->puerto_dcm,
            'database'  => $sedeActual->bd_dcm,
            'username'  => $sedeActual->usuario_dcm,
            'password'  => $sedeActual->password_dcm,
            'charset'   => 'latin1', // Muy importante para dcm4chee 2.16
            'collation' => 'latin1_swedish_ci',
            'prefix'    => '',
            'strict'    => false,
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
                    "00201208" => ["vr" => "IS", "Value" => [(string)$s->num_instances]], // NumberOfStudyRelatedInstances
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

        // Agregamos &transferSyntax=1.2.840.10008.1.2.1
        // Esto le ordena a dcm4chee: "Descomprime la imagen antes de enviármela"
        $wadoUrl = "http://{$sede->ip_dcm}:8080/wado?requestType=WADO"
            . "&studyUID={$study}"
            . "&seriesUID={$series}"
            . "&objectUID={$object}"
            . "&contentType=application/dicom"
            . "&transferSyntax=1.2.840.10008.1.2.1";

        $response = Http::get($wadoUrl);

        if ($response->successful()) {
            return response($response->body())
                ->header('Content-Type', 'application/dicom')
                ->header('Access-Control-Allow-Origin', '*');
        }

        return response()->json(['error' => 'Error al obtener DICOM'], 500);
    }
}
