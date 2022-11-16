<?php

namespace App\Http\Controllers\Dcm4chee;

use App\Http\Controllers\Controller;
use App\Models\Dcm4chee\Study;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use DB;

class StudyController extends Controller
{
    public function listarEstudios(Request $request, Study $study)
    {

        //Configuramos la segunda base de datos
        config(['database.connections.mysql_sucursal' => [
            'driver' => 'mysql',
            'host' => env('DB_SUCURSAL_HOST', '127.0.0.1'),
            'database' => env('DB_SUCURSAL_DATABASE', 'pacsdb'),
            'username' => env('DB_SUCURSAL_USERNAME', 'root'),
            'password' => env('DB_SUCURSAL_PASSWORD', ''),
        ]]);

        //Aplicamos la nueva configuracion de bade datos
        Config::set('database.default', 'mysql_sucursal');


        $query = DB::table('study')->select([
            'study.pk as study_pk', 'study.study_iuid', 'study.study_id', 'study.study_datetime', 'study.accession_no', 'study.study_desc',
            'patient.pat_id', 'patient.pat_name', 'patient.pat_sex', 'patient.pat_birthdate'
        ])
            ->join('patient', 'study.patient_fk', 'patient.pk')
            ->where('patient.pk', 2)->get();
        return response()->json($query);
    }
}
