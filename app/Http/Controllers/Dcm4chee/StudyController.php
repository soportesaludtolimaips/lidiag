<?php

namespace App\Http\Controllers\Dcm4chee;

use App\Http\Controllers\Controller;
use App\Models\Dcm4chee\Study;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

class StudyController extends Controller
{
    public function listarEstudios(Request $request, Study $study)
    {
        /*  //Configuramos la segunda base de datos
        config(['database.connections.mysql_sucursal' => [
            'driver' => 'mysql',
            'host' => env('DB_SUCURSAL_HOST', '127.0.0.1'),
            'database' => env('DB_SUCURSAL_DATABASE', 'pacsdb'),
            'username' => env('DB_SUCURSAL_USERNAME', 'root'),
            'password' => env('DB_SUCURSAL_PASSWORD', ''),
        ]]);

        //Aplicamos la nueva configuracion de bade datos
        Config::set('database.default', 'mysql_sucursal');
*/
        //$studyes = Study::where('pk', 1)->get();

        //nueva conexion

        Config::set('database.connections.mysql', 'lidiag');


        /* Config::set('database.connections.mysql_sucursal.database', 'pacsdb');
        $query = (new Query)->select(['study.pk as id_estudy', 'study.study_datetime', 'study.accession_no', 'study.study_desc', 'patient.pk', 'patient.pat_id', 'patient.pat_name'])
            ->join('patient', 'study.patient_fk', 'patient.pk')
            ->where('patient.pk', 2)->get();

        return $query;
        return response()->json($query); */
    }
}
