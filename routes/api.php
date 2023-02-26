<?php

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

Route::resource('config-admin-salud', ConfigAdminSaludController::class)->names('config.admin.salud')->except(['create', 'show']);
Route::resource('config-diagnosticos', ConfigDiagnosticoController::class)->names('config.diagnosticos')->except(['create', 'show']);
Route::resource('config-prioridades', ConfigPrioridadController::class)->names('config.prioridades')->except(['create', 'show']);
Route::resource('config-productos', ConfigProductoController::class)->names('config.productos')->except(['create']);
Route::resource('config-sucursales', ConfigSucursalController::class)->names('config.sucursales')->except(['create', 'show']);

Route::post('study.listarEstudios', [StudyController::class, 'listarEstudios'])->name('study.listarEstudios');

Route::get('user.listarUsuarios/{tipo_user}', [UserController::class, 'listarUsuarios'])->name('user.listarUsuarios');

Route::resource('estudios', EstudioController::class)->names('estudios')->except(['create', 'show']);
Route::resource('estudios-productos', EstudioProductoController::class)->names('estudios.productos');
Route::resource('estudios-diagnosticos', EstudioDiagnosticoController::class)->names('estudios.diagnosticos');

Route::post('estudio-leerEstudio', [EstudioController::class, 'leerEstudio'])->name('estudio.leerEstudio');
Route::get('estudio-listarPendientesTrascribir', [EstudioController::class, 'listarPendientesTrascribir'])->name('estudio.listarPendientesTrascribir');
Route::post('estudio-guardarTranscripcion', [EstudioController::class, 'guardarTranscripcion'])->name('estudio.guardarTranscripcion');
Route::get('estudio-listarPendientesMedico', [EstudioController::class, 'listarPendientesMedico'])->name('estudio.listarPendientesMedico');

//Route::get('seguridad-usuario-obtenerUsuario/{seguridad_usuario}', [UserController::class, 'obtenerUsuario'])->name('seguridad.usuarios.obtenerUsuario');
//Route::get('seguridad-usuarios/', [UserController::class, 'obtenerUsuario'])->name('seguridad.usuarios.asdfjlkasdjflkasjdlkf');
//Route::put('seguridad-usuarios/estado/{seguridad_usuario}', [UserController::class, 'estadoUsuario'])->name('seguridad.usuarios.estadoUsuario');
Route::resource('seguridad-usuarios', UserController::class)->names('seguridad.usuarios');
Route::resource('seguridad-roles', RolController::class)->names('seguridad.roles');
