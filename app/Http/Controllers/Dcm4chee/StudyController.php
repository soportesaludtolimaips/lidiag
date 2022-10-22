<?php

namespace App\Http\Controllers\Dcm4chee;

use App\Http\Controllers\Controller;
use App\Models\Dcm4chee\Study;
use Illuminate\Support\Facades\Config;

class StudyController extends Controller
{
    public function index()
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

        return Study::where('pk', 1)->get();
        //return $estudios;
    }
}
