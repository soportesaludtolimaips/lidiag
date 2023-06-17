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
use App\Http\Controllers\Configuracion\ConfigSedeController;

use App\Http\Controllers\Dcm4chee\StudyController;
use App\Http\Controllers\Estudios\EstudioController;
use App\Http\Controllers\Estudios\EstudioProductoController;
use App\Http\Controllers\Estudios\EstudioDiagnosticoController;

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

Route::get('/', function () {
    return view('auth/login');
});

Auth::routes();

Route::post('/autenticacion/login', [LoginController::class, 'login']);
Route::post('/autenticacion/logout', [LoginController::class, 'logout']);
Route::view('dashboard', 'modulos.dashboard.dashboard')->name('dashboard')->middleware('auth');

/**
 * Configuración
 */
Route::view('/config.adminsalud.listar', 'modulos.configuracion.config-admin-salud')->name('config.adminsalud.listar');
Route::resource('/config-admin-salud', ConfigAdminSaludController::class)->names('config.admin.salud')->except(['create', 'show']);

Route::view('/config.diagnosticos.listar', 'modulos.configuracion.config-diagnosticos')->name('config.diagnosticos.listar');
Route::resource('/config-diagnosticos', ConfigDiagnosticoController::class)->names('config.diagnosticos')->except(['create', 'show']);

Route::view('/config.prioridades.listar', 'modulos.configuracion.config-prioridades')->name('config.prioridades.listar');
Route::resource('/config-prioridades', ConfigPrioridadController::class)->names('config.prioridades')->except(['create', 'show']);

Route::view('/config.productos.listar', 'modulos.configuracion.config-productos')->name('config.productos.listar');
Route::resource('/config-productos', ConfigProductoController::class)->names('config.productos')->except(['create']);

Route::view('/config.sedes.listar', 'modulos.configuracion.config-sedes')->name('config.sedes.listar');
Route::resource('/config-sedes', ConfigSedeController::class)->names('config.sedes')->except(['create', 'show']);

/**
 * Estudios
 */
Route::view('/estudios.asignar.listar', 'modulos.estudios.asignar-estudio')->name('estudios.asignar.listar');
Route::post('/study.listarEstudios', [StudyController::class, 'listarEstudios'])->name('study.listarEstudios');

Route::get('/user.listarUsuarios/{tipo_user}', [UserController::class, 'listarUsuarios'])->name('user.listarUsuarios');

Route::resource('/estudios', EstudioController::class)->names('estudios')->except(['create', 'show']);
Route::resource('/estudios-productos', EstudioProductoController::class)->names('estudios.productos');
Route::resource('/estudios-diagnosticos', EstudioDiagnosticoController::class)->names('estudios.diagnosticos');

Route::view('/estudios.leer.estudio.listar', 'modulos.estudios.leer-estudio')->name('estudios.leer.estudio.listar');
Route::post('/estudio-leerEstudio', [EstudioController::class, 'leerEstudio'])->name('estudio.leerEstudio');

Route::view('/estudios.transcribir.listar', 'modulos.estudios.transcribir-estudio')->name('estudios.transcribir.listar');
Route::get('/estudio-listarPendientesTrascribir', [EstudioController::class, 'listarPendientesTrascribir'])->name('estudio.listarPendientesTrascribir');
Route::post('/estudio-guardarTranscripcion', [EstudioController::class, 'guardarTranscripcion'])->name('estudio.guardarTranscripcion');
Route::get('/estudio-listarPendientesMedico', [EstudioController::class, 'listarPendientesMedico'])->name('estudio.listarPendientesMedico');

/**
 * Seguridad
 */
Route::view('/seguridad.usuarios.listar', 'modulos.seguridad.usuarios')->name('seguridad.usuarios.listar');
Route::resource('/seguridad-usuarios', UserController::class)->names('seguridad.usuarios');
Route::put('/seguridad-actualizar-perfil/{idUsua}', [UserController::class, 'actualizarPerfil'])->name('seguridad.actualizar.perfil');

Route::view('/seguridad.roles.listar', 'modulos.seguridad.roles')->name('seguridad.roles.listar');
Route::resource('/seguridad-roles', RolController::class)->names('seguridad.roles');

Route::view('/seguridad.perfil', 'modulos.seguridad.perfil')->name('seguridad.perfil');
Route::get('/seguridad-listarPermisosUsuario/{id}', [UserController::class, 'listarPermisosUsuario'])->name('seguridad.listarPermisosUsuario');

/* Route::get('/{optional?}', function () {
    return view('layouts.app');
})->name('basepath')->where('optional', '.*');
 */