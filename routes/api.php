<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Configuracion\ConfigAdminSaludController;
use App\Http\Controllers\Configuracion\ConfigProductoController;
use App\Http\Controllers\Configuracion\ConfigDiagnosticoController;
use App\Http\Controllers\Configuracion\ConfigSucursalController;

use App\Http\Controllers\Dcm4chee\StudyController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::resource('config-admin-salud', ConfigAdminSaludController::class)->names('config.admin.salud')->except(['create', 'show']);
Route::resource('config-diagnosticos', ConfigDiagnosticoController::class)->names('config.diagnosticos')->except(['create', 'show']);
Route::resource('config-productos', ConfigProductoController::class)->names('config.productos')->except(['create', 'show']);
Route::resource('config-sucursales', ConfigSucursalController::class)->names('config.sucursales')->except(['create', 'show']);

Route::get('study', [StudyController::class, 'index'])->name('study.index');
