<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ecografías API Routes
|--------------------------------------------------------------------------
|
| Aquí se definen todas las rutas API relacionadas con las ecografías
| (CRUD, lecturas, reportes, interfaces SAHI, etc.)
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    /*
     * Ecografias API Endpoints
     */
    Route::resource('ecografias', App\Http\Controllers\Ecografias\EcografiaController::class)
        ->names('api.ecografias')
        ->except(['create', 'edit']);

    Route::post('ecografias-filtar', [App\Http\Controllers\Ecografias\EcografiaController::class, 'filtarEcografias'])
        ->name('api.ecografias.filtar')
        ->middleware(['permission:ecografias.index|ecografias.admision,web']);

    Route::post('ecografia-anular/{id}', [App\Http\Controllers\Ecografias\EcografiaController::class, 'anularEcografia'])
        ->name('api.ecografias.anular')
        ->middleware(['permission:ecografias.anular,web']);

    Route::put('ecografia-lectura/{ecografia_id}', [App\Http\Controllers\Ecografias\ecografiaLecturaController::class, 'leer'])
        ->name('api.ecografias.lectura')
        ->middleware(['permission:ecografias.leer,web']);

    Route::post('ecografia-generar-reporte/{ecografia_id}', [App\Http\Controllers\Ecografias\EcografiaController::class, 'generarReporte'])
        ->name('api.ecografias.generar.reporte')
        ->middleware(['permission:ecografias.reporte,web']);

    Route::post('/ecografia-listarPendientesPorLectura/{idMedico}', [App\Http\Controllers\Ecografias\ecografiaLecturaController::class, 'listarPendientesPorLectura'])
        ->name('api.ecografia.listarPendientesPorLectura')
        ->middleware(['permission:ecografias.leer,web']);

    Route::resource('ecografias-productos', App\Http\Controllers\Ecografias\EcografiaProductoController::class)
        ->names('api.ecografias.productos')
        ->except(['create', 'edit']);

    Route::post('/ecografia-guardarLectura', [App\Http\Controllers\Ecografias\ecografiaLecturaController::class, 'guardarLectura'])
        ->name('api.ecografia.guardarLectura');

    Route::post('/interface-sahi-ecografias/listar/productos/atencion', [App\Http\Controllers\Interface\SAHIEcografiasController::class, 'listarProductosAtencion'])
        ->name('api.sahi.listar.productos.atencion')
        ->middleware(['permission:ecografias.admision|ecografias.index,web']);

    Route::post('/interface-sahi-ecografias/enviar/ecografia-producto', [App\Http\Controllers\Interface\SAHIEcografiasEnviarController::class, 'enviarPorEcografiaProducto'])
        ->name('api.sahi.ecografias.enviar.ecografiaProducto')
        ->middleware(['permission:ecografias.leer|ecografias.reporte,web']);

    Route::get('/interface-sahi-ecografias/estado/ecografia-producto/{ecografia_producto_id}', [App\Http\Controllers\Interface\SAHIEcografiasEnviarController::class, 'estadoPorEcografiaProducto'])
        ->name('api.sahi.ecografias.estado.ecografiaProducto')
        ->middleware(['permission:ecografias.admision|ecografias.index|ecografias.leer,web']);
});
