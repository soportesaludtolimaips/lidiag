<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Seguridad\UserController;
use App\Http\Controllers\Seguridad\RolController;

use App\Http\Controllers\Configuracion\ConfigAdminSaludController;
use App\Http\Controllers\Configuracion\ConfigProductoController;
use App\Http\Controllers\Configuracion\ConfigDiagnosticoController;
use App\Http\Controllers\Configuracion\ConfigPrioridadController;
use App\Http\Controllers\Configuracion\ConfigSucursalController;

use App\Http\Controllers\Dcm4chee\StudyController;
use App\Http\Controllers\Estudios\EstudioController;
use App\Http\Controllers\Estudios\EstudioProductoController;
use App\Http\Controllers\Estudios\EstudioDiagnosticoController;
use App\Http\Controllers\Prueba;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/* Route::get('/', function () {
    return view('auth.login');
}); */




Auth::routes();

Route::post('/autenticacion/login', [LoginController::class, 'login']);
Route::post('/autenticacion/logout', [LoginController::class, 'logout']);

Route::resource('/config-admin-salud', ConfigAdminSaludController::class)->names('config.admin.salud')->except(['create', 'show']);
Route::resource('/config-diagnosticos', ConfigDiagnosticoController::class)->names('config.diagnosticos')->except(['create', 'show']);
Route::resource('/config-prioridades', ConfigPrioridadController::class)->names('config.prioridades')->except(['create', 'show']);
Route::resource('/config-productos', ConfigProductoController::class)->names('config.productos')->except(['create']);
Route::resource('/config-sucursales', ConfigSucursalController::class)->names('config.sucursales')->except(['create', 'show']);

Route::post('/study.listarEstudios', [StudyController::class, 'listarEstudios'])->name('study.listarEstudios');

Route::get('/user.listarUsuarios/{tipo_user}', [UserController::class, 'listarUsuarios'])->name('user.listarUsuarios');

Route::resource('/estudios', EstudioController::class)->names('estudios')->except(['create', 'show']);
Route::resource('/estudios-productos', EstudioProductoController::class)->names('estudios.productos');
Route::resource('/estudios-diagnosticos', EstudioDiagnosticoController::class)->names('estudios.diagnosticos');

Route::post('/estudio-leerEstudio', [EstudioController::class, 'leerEstudio'])->name('estudio.leerEstudio');
Route::get('/estudio-listarPendientesTrascribir', [EstudioController::class, 'listarPendientesTrascribir'])->name('estudio.listarPendientesTrascribir');
Route::post('/estudio-guardarTranscripcion', [EstudioController::class, 'guardarTranscripcion'])->name('estudio.guardarTranscripcion');
Route::get('/estudio-listarPendientesMedico', [EstudioController::class, 'listarPendientesMedico'])->name('estudio.listarPendientesMedico');

Route::resource('/seguridad-usuarios', UserController::class)->names('seguridad.usuarios');
Route::resource('/seguridad-roles', RolController::class)->names('seguridad.roles');

Route::get('/{optional?}', function () {
    return view('layouts.app');
})->name('basepath')->where('optional', '.*');
