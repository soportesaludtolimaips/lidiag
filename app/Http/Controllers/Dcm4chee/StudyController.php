<?php

namespace App\Http\Controllers\Dcm4chee;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
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
        $sedeActual = ConfigSede::find($request->sede_id);

        //Configuramos la segunda base de datos
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
            'study.mods_in_study',
            'patient.pat_id',
            'patient.pat_name',
            'patient.pat_sex',
            'patient.pat_birthdate'
        )->join('patient', 'study.patient_fk', 'patient.pk');

        if ($request->bus_nom_num_docu != "") {
            $query->where('patient.pat_id', 'like', "%$request->bus_nom_num_docu%")
                ->orWhere('patient.pat_name', 'like', "%$request->bus_nom_num_docu%");
        }

        if ($request->bus_fehc_ini != "" and $request->bus_fecha_fin != "") {
            $query->whereBetween('study.study_datetime', [$request->bus_fehc_ini, $request->bus_fecha_fin]);
        }

        $result = $query->get();
        return json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
