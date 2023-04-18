<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Configuracion\ConfigAdminSaludController;
use App\Http\Controllers\Configuracion\ConfigProductoController;
use App\Http\Controllers\Configuracion\ConfigDiagnosticoController;
use App\Http\Controllers\Configuracion\ConfigPrioridadController;
use App\Http\Controllers\Configuracion\ConfigSucursalController;

use App\Http\Controllers\Dcm4chee\StudyController;
use App\Http\Controllers\Estudios\EstudioController;
use App\Http\Controllers\Estudios\EstudioProductoController;
use App\Http\Controllers\Estudios\EstudioDiagnosticoController;
use App\Http\Controllers\Seguridad\RolController;
use App\Http\Controllers\Seguridad\UserController;

/*
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/* Route::middleware('auth:api:sanctum')->get('/user', function (Request $request) {
    return $request->user();
}); */

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});




//Route::get('seguridad-usuario-obtenerUsuario/{seguridad_usuario}', [UserController::class, 'obtenerUsuario'])->name('seguridad.usuarios.obtenerUsuario');
//Route::get('seguridad-usuarios/', [UserController::class, 'obtenerUsuario'])->name('seguridad.usuarios.asdfjlkasdjflkasjdlkf');
//Route::put('seguridad-usuarios/estado/{seguridad_usuario}', [UserController::class, 'estadoUsuario'])->name('seguridad.usuarios.estadoUsuario');
