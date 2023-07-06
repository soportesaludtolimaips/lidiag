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

        $sedeActual = ConfigSede::find($request->sedeActual);

        //Configuramos la segunda base de datos
        config(['database.connections.mysql_sucursal' => [
            'driver' => 'mysql',
            'host' => env('DB_SUCURSAL_HOST', $sedeActual->ip_dcm),
            'database' => env('DB_SUCURSAL_DATABASE', $sedeActual->bd_dcm),
            'username' => env('DB_SUCURSAL_USERNAME', $sedeActual->usuario_dcm),
            'password' => env('DB_SUCURSAL_PASSWORD', $sedeActual->password_dcm),
        ]]);

        //Aplicamos la nueva configuracion de bade datos
        Config::set('database.default', 'mysql_sucursal');
        $connections = DB::getConnections();
        foreach ($connections as $name => $connection) {
            echo "Conexión: " . $name . "\n";
            echo "Driver: " . $connection->getDriverName() . "\n";
            echo "Host: " . $connection->getConfig('host') . "\n";
            echo "Database: " . $connection->getConfig('database') . "\n";
            echo "Username: " . $connection->getConfig('username') . "\n";
            echo "Password: " . $connection->getConfig('password') . "\n";
            echo "\n";
        }

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
