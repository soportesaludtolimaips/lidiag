<?php

namespace App\Http\Controllers\Dcm4chee;

use App\Http\Controllers\Controller;
use App\Models\Dcm4chee\Study;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use DB;

class StudyController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function listarEstudios(Request $request, Study $study)
    {

        //return $request;
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

        $estudios = new Study();
        $query = $estudios->query();

        $query->select(
            'study.pk as study_pk',
            'study.study_iuid',
            'study.study_id',
            'study.study_datetime',
            'study.accession_no',
            'study.study_desc',
            'patient.pat_id',
            'patient.pat_name',
            'patient.pat_sex',
            'patient.pat_birthdate'
        )->join('patient', 'study.patient_fk', 'patient.pk');

        if ($request->bus_nom_num_docu) {
            $query->where('patient.pat_id', 'like', "%$request->bus_nom_num_docu%")
                ->orWhere('patient.pat_name', 'like', "%$request->bus_nom_num_docu%");
        }

        if ($request->fehc_ini and $request->fecha_fin) {
            $query->whereBetween('study.study_datetime', [$request->fehc_ini, $request->fecha_fin]);
        }

        $result = $query->get();
        return response()->json($result);
    }
}
