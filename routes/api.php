<?php

use App\Http\Controllers\Agenda\AgendaController;
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

Route::post('estudio-leer', [EstudioController::class, 'leerEstudio'])->name('estudio.leer');
Route::get('estudio-listarPendientesMedico', [EstudioController::class, 'listarPendientesMedico'])->name('estudio.listarPendientesMedico');
