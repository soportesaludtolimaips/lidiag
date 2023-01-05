<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dcm4chee\StudyController;
use App\Http\Controllers\EmailController;

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

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::view('config-admin-salud', 'configuracion.config-admin-salud')->name('config.admin.salud')->middleware('auth');
Route::view('config-diagnosticos', 'configuracion.config-diagnosticos')->name('config.diagnosticos')->middleware('auth');
Route::view('config-prioridades', 'configuracion.config-prioridades')->name('config.prioridades')->middleware('auth');
Route::view('config-productos', 'configuracion.config-productos')->name('config.productos')->middleware('auth');
Route::view('config-sucursales', 'configuracion.config-sucursales')->name('config.sucursales')->middleware('auth');


Route::view('asignar-lectura', 'lecturas.asignar-lectura')->name('asignar-lectura')->middleware('auth');
