<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dcm4chee\StudyController;

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
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::view('config-admin-salud', 'configuracion.config-admin-salud')->name('config.admin.salud');
Route::view('config-diagnosticos', 'configuracion.config-diagnosticos')->name('config.diagnosticos');
Route::view('config-prioridades', 'configuracion.config-prioridades')->name('config.prioridades');
Route::view('config-productos', 'configuracion.config-productos')->name('config.productos');
Route::view('config-sucursales', 'configuracion.config-sucursales')->name('config.sucursales');


Route::view('agenda', 'agenda.agenda')->name('agenda.agenda');
