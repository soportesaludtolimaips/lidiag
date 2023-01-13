<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dcm4chee\StudyController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\Lectura\LecturaProductoController;
use App\Http\Controllers\LecturaDiagnosticoController;

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
    return view('auth.login');
});

Auth::routes();

//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('home', function () {
    return view('home');
});

Route::view('config-admin-salud', 'configuracion.config-admin-salud')->name('config.admin.salud')->middleware('auth');
Route::view('config-diagnosticos', 'configuracion.config-diagnosticos')->name('config.diagnosticos')->middleware('auth');
Route::view('config-prioridades', 'configuracion.config-prioridades')->name('config.prioridades')->middleware('auth');
Route::view('config-productos', 'configuracion.config-productos')->name('config.productos')->middleware('auth');
Route::view('config-sucursales', 'configuracion.config-sucursales')->name('config.sucursales')->middleware('auth');

Route::view('lecturas-asingnar', 'lecturas.asignar-lectura')->name('lecturas.asignar')->middleware('auth');
