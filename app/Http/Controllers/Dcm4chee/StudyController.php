<?php

namespace App\Http\Controllers\Dcm4chee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dcm4chee\Study;

class StudyController extends Controller
{
    public function index(){
        //Configuramos la segunda base de datos
        config(['database.connections.mysql_sucursal' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'database' => 'pacsdb'
        ]]);

        //Aplicamos la nueva configuracion de bade datos
        Config::set('database.default', 'mysql_sucursal');

        $estudios = Study::where('pk', 1);
        return $estudios;
    }
}
