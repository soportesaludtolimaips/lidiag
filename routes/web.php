<?php

use Illuminate\Support\Facades\Route;

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

Route::get('config-admin-salud', function () {
    return view('configuracion.config-admin-salud');
})->name('config.admin.salud');

Route::get('config-diagnosticos', function () {
    return view('configuracion.config-diagnosticos');
})->name('config.diagnosticos');

Route::get('config-prioridades', function () {
    return view('configuracion.config-prioridades');
})->name('config.prioridades');

Route::get('config-productos', function () {
    return view('configuracion.config-productos');
})->name('config.productos');

Route::get('config-sedes', function () {
    return view('configuracion.config-sedes');
})->name('config.sedes');

Route::get('agenda', function () {
    return view('agenda');
})->name('agenda');
