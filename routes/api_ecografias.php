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

    Route::post('/interface-sahi-ecografias/listar/productos/atencion', [App\Http\Controllers\Interface\SAHIEcografiasController::class, 'listarProductosAtencion'])
        ->name('api.sahi.listar.productos.atencion')
        ->middleware(['permission:ecografias.admision|ecografias.index,web']);

    Route::post('/interface-sahi-ecografias/enviar/ecografia-producto', [App\Http\Controllers\Interface\SAHIEcografiasEnviarController::class, 'enviarPorEcografiaProducto'])
        ->name('api.sahi.ecografias.enviar.ecografiaProducto')
        ->middleware(['permission:ecografias.leer|ecografias.reporte,web']);

    Route::get('/interface-sahi-ecografias/estado/ecografia-producto/{ecografia_producto_id}', [App\Http\Controllers\Interface\SAHIEcografiasEnviarController::class, 'estadoPorEcografiaProducto'])
        ->name('api.sahi.ecografias.estado.ecografiaProducto')
        ->middleware(['permission:ecografias.admision|ecografias.index|ecografias.leer,web']);

    /**
     * Consentimientos (Ecografías)
     */
    Route::post('/ecografias-consentimientos/token', [App\Http\Controllers\Ecografias\EcografiaConsentimientosController::class, 'crearToken'])
        ->name('api.ecografias.consentimientos.token')
        ->middleware(['permission:ecografias.consentimientos.capturar_firma,web']);

    Route::post('/ecografias-consentimientos/firmar-medico', [App\Http\Controllers\Ecografias\EcografiaConsentimientosController::class, 'firmarMedico'])
        ->name('api.ecografias.consentimientos.firmar_medico')
        ->middleware(['permission:ecografias.consentimientos.firmar_medico,web']);

    Route::get('/ecografias-consentimientos/vigente/{ecografia_producto_id}', [App\Http\Controllers\Ecografias\EcografiaConsentimientosController::class, 'vigentePorEcografiaProducto'])
        ->name('api.ecografias.consentimientos.vigente')
        ->middleware(['permission:ecografias.consentimientos.show|ecografias.consentimientos.index,web']);

    Route::get('/ecografias-consentimientos', [App\Http\Controllers\Ecografias\EcografiaConsentimientosController::class, 'index'])
        ->name('api.ecografias.consentimientos.index')
        ->middleware(['permission:ecografias.consentimientos.index,web']);

    Route::post('/ecografias-consentimientos/{id}/pdf', [App\Http\Controllers\Ecografias\EcografiaConsentimientosController::class, 'generarPdf'])
        ->name('api.ecografias.consentimientos.pdf.generar')
        ->middleware(['permission:ecografias.consentimientos.pdf.generar,web']);

    Route::get('/ecografias-consentimientos/{id}/pdf/download', [App\Http\Controllers\Ecografias\EcografiaConsentimientosController::class, 'descargarPdf'])
        ->name('api.ecografias.consentimientos.pdf.descargar')
        ->middleware(['permission:ecografias.consentimientos.pdf.descargar,web']);

    Route::post('/ecografias-consentimientos/zip', [App\Http\Controllers\Ecografias\EcografiaConsentimientosController::class, 'generarZip'])
        ->name('api.ecografias.consentimientos.zip.generar')
        ->middleware(['permission:ecografias.consentimientos.zip.generar,web']);

    Route::post('/ecografias-consentimientos/{id}/anular', [App\Http\Controllers\Ecografias\EcografiaConsentimientosController::class, 'anular'])
        ->name('api.ecografias.consentimientos.anular')
        ->middleware(['permission:ecografias.consentimientos.anular,web']);

    /**
     * Plantillas de consentimiento (WYSIWYG) - módulo Ecografías
     */
    Route::get('/ecografias-consentimientos-plantillas', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'index'])
        ->name('api.ecografias.consentimientos.plantillas.index')
        ->middleware(['permission:ecografias.consentimientos.plantillas.index,web']);

    Route::get('/ecografias-consentimientos-plantillas/{id}', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'show'])
        ->name('api.ecografias.consentimientos.plantillas.show')
        ->middleware(['permission:ecografias.consentimientos.plantillas.index,web']);

    Route::post('/ecografias-consentimientos-plantillas', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'store'])
        ->name('api.ecografias.consentimientos.plantillas.store')
        ->middleware(['permission:ecografias.consentimientos.plantillas.create,web']);

    Route::put('/ecografias-consentimientos-plantillas/{id}', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'update'])
        ->name('api.ecografias.consentimientos.plantillas.update')
        ->middleware(['permission:ecografias.consentimientos.plantillas.edit,web']);

    Route::post('/ecografias-consentimientos-plantillas/{id}/vigente', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'setVigente'])
        ->name('api.ecografias.consentimientos.plantillas.vigente')
        ->middleware(['permission:ecografias.consentimientos.plantillas.edit,web']);

    Route::get('/ecografias-consentimientos-plantillas/producto/{productoId}/versions', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'versions'])
        ->name('api.ecografias.consentimientos.plantillas.versions')
        ->middleware(['permission:ecografias.consentimientos.plantillas.index,web']);

    Route::delete('/ecografias-consentimientos-plantillas/{id}', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'destroy'])
        ->name('api.ecografias.consentimientos.plantillas.destroy')
        ->middleware(['permission:ecografias.consentimientos.plantillas.destroy,web']);

    Route::get('/ecografias-consentimientos-plantillas/{id}/preview-pdf', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'previewPdf'])
        ->name('api.ecografias.consentimientos.plantillas.preview_pdf')
        ->middleware(['permission:ecografias.consentimientos.plantillas.preview_pdf,web']);
});
