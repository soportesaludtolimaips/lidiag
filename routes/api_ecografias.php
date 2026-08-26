<?php

use App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController;
use App\Http\Controllers\Ecografias\EcografiaConsentimientosController;
use App\Http\Controllers\Ecografias\EcografiaController;
use App\Http\Controllers\Ecografias\ecografiaLecturaController;
use App\Http\Controllers\Ecografias\EcografiaProductoController;
use App\Http\Controllers\Interface\SAHIEcografiasController;
use App\Http\Controllers\Interface\SAHIEcografiasEnviarController;
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

Route::middleware(['auth'])->group(function () {
    /*
     * Ecografias API Endpoints
     */
    Route::resource('ecografias', EcografiaController::class)
        ->names('api.ecografias')
        ->except(['create', 'edit']);

    Route::post('ecografias-filtar', [EcografiaController::class, 'filtarEcografias'])
        ->name('api.ecografias.filtar')
        ->middleware(['permission:ecografias.index|ecografias.admision,web']);

    Route::post('ecografia-anular/{id}', [EcografiaController::class, 'anularEcografia'])
        ->name('api.ecografias.anular')
        ->middleware(['permission:ecografias.anular,web']);

    Route::put('ecografia-lectura/{ecografia_id}', [ecografiaLecturaController::class, 'leer'])
        ->middleware('throttle:api-lectura')
        ->name('api.ecografias.lectura')
        ->middleware(['permission:ecografias.leer,web', 'consent.bloqueo']);

    Route::post('ecografia-generar-reporte/{ecografia_id}', [EcografiaController::class, 'generarReporte'])
        ->name('api.ecografias.generar.reporte')
        ->middleware(['permission:ecografias.reporte,web']);

    Route::post('/ecografia-listarPendientesPorLectura/{idMedico}', [ecografiaLecturaController::class, 'listarPendientesPorLectura'])
        ->middleware('throttle:api-lectura')
        ->name('api.ecografia.listarPendientesPorLectura')
        ->middleware(['permission:ecografias.leer,web']);

    Route::resource('ecografias-productos', EcografiaProductoController::class)
        ->names('api.ecografias.productos')
        ->except(['create', 'edit']);

    Route::post('/interface-sahi-ecografias/listar/productos/atencion', [SAHIEcografiasController::class, 'listarProductosAtencion'])
        ->middleware('throttle:api-sahi')
        ->name('api.sahi.listar.productos.atencion')
        ->middleware(['permission:ecografias.admision|ecografias.index,web']);

    Route::post('/interface-sahi-ecografias/enviar/ecografia-producto', [SAHIEcografiasEnviarController::class, 'enviarPorEcografiaProducto'])
        ->middleware('throttle:api-sahi')
        ->name('api.sahi.ecografias.enviar.ecografiaProducto')
        ->middleware(['permission:ecografias.leer|ecografias.reporte,web']);

    Route::get('/interface-sahi-ecografias/estado/ecografia-producto/{ecografia_producto_id}', [SAHIEcografiasEnviarController::class, 'estadoPorEcografiaProducto'])
        ->middleware('throttle:api-sahi')
        ->name('api.sahi.ecografias.estado.ecografiaProducto')
        ->middleware(['permission:ecografias.admision|ecografias.index|ecografias.leer,web']);

    /**
     * Consentimientos (Ecografías)
     */
    Route::post('/ecografias-consentimientos/token', [EcografiaConsentimientosController::class, 'crearToken'])
        ->middleware('throttle:api-consent-create')
        ->name('api.ecografias.consentimientos.token')
        ->middleware(['permission:ecografias.consentimientos.capturar_firma,web']);

    Route::post('/ecografias-consentimientos/firmar-medico', [EcografiaConsentimientosController::class, 'firmarMedico'])
        ->name('api.ecografias.consentimientos.firmar_medico')
        ->middleware(['permission:ecografias.consentimientos.firmar_medico,web']);

    Route::get('/ecografias-consentimientos/vigente/{ecografia_producto_id}', [EcografiaConsentimientosController::class, 'vigentePorEcografiaProducto'])
        ->name('api.ecografias.consentimientos.vigente')
        ->middleware(['permission:ecografias.consentimientos.show|ecografias.consentimientos.index,web']);

    Route::get('/ecografias-consentimientos', [EcografiaConsentimientosController::class, 'index'])
        ->name('api.ecografias.consentimientos.index')
        ->middleware(['permission:ecografias.consentimientos.index,web']);

    Route::post('/ecografias-consentimientos/{id}/pdf', [EcografiaConsentimientosController::class, 'generarPdf'])
        ->name('api.ecografias.consentimientos.pdf.generar')
        ->middleware(['permission:ecografias.consentimientos.pdf.generar,web']);

    Route::get('/ecografias-consentimientos/{id}/pdf/download', [EcografiaConsentimientosController::class, 'descargarPdf'])
        ->name('api.ecografias.consentimientos.pdf.descargar')
        ->middleware(['permission:ecografias.consentimientos.pdf.descargar,web']);

    Route::post('/ecografias-consentimientos/zip', [EcografiaConsentimientosController::class, 'generarZip'])
        ->name('api.ecografias.consentimientos.zip.generar')
        ->middleware(['permission:ecografias.consentimientos.zip.generar,web']);

    Route::post('/ecografias-consentimientos/{id}/anular', [EcografiaConsentimientosController::class, 'anular'])
        ->name('api.ecografias.consentimientos.anular')
        ->middleware(['permission:ecografias.consentimientos.anular,web']);

    /**
     * Plantillas de consentimiento (WYSIWYG) - módulo Ecografías
     */
    Route::get('/ecografias-consentimientos-plantillas', [ConsentimientoPlantillasController::class, 'index'])
        ->name('api.ecografias.consentimientos.plantillas.index')
        ->middleware(['permission:ecografias.consentimientos.plantillas.index,web']);

    Route::get('/ecografias-consentimientos-plantillas/{id}', [ConsentimientoPlantillasController::class, 'show'])
        ->name('api.ecografias.consentimientos.plantillas.show')
        ->middleware(['permission:ecografias.consentimientos.plantillas.index,web']);

    Route::post('/ecografias-consentimientos-plantillas', [ConsentimientoPlantillasController::class, 'store'])
        ->name('api.ecografias.consentimientos.plantillas.store')
        ->middleware(['permission:ecografias.consentimientos.plantillas.create,web']);

    Route::put('/ecografias-consentimientos-plantillas/{id}', [ConsentimientoPlantillasController::class, 'update'])
        ->name('api.ecografias.consentimientos.plantillas.update')
        ->middleware(['permission:ecografias.consentimientos.plantillas.edit,web']);

    Route::post('/ecografias-consentimientos-plantillas/{id}/vigente', [ConsentimientoPlantillasController::class, 'setVigente'])
        ->name('api.ecografias.consentimientos.plantillas.vigente')
        ->middleware(['permission:ecografias.consentimientos.plantillas.edit,web']);

    Route::get('/ecografias-consentimientos-plantillas/producto/{productoId}/versions', [ConsentimientoPlantillasController::class, 'versions'])
        ->name('api.ecografias.consentimientos.plantillas.versions')
        ->middleware(['permission:ecografias.consentimientos.plantillas.index,web']);

    Route::delete('/ecografias-consentimientos-plantillas/{id}', [ConsentimientoPlantillasController::class, 'destroy'])
        ->name('api.ecografias.consentimientos.plantillas.destroy')
        ->middleware(['permission:ecografias.consentimientos.plantillas.destroy,web']);

    Route::get('/ecografias-consentimientos-plantillas/{id}/preview-pdf', [ConsentimientoPlantillasController::class, 'previewPdf'])
        ->name('api.ecografias.consentimientos.plantillas.preview_pdf')
        ->middleware(['permission:ecografias.consentimientos.plantillas.preview_pdf,web']);
});
