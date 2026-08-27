<?php

use App\Http\Controllers\Consentimientos\ConsentimientoAdjuntoController;
use App\Http\Controllers\Consentimientos\ConsentimientoBloqueoController;
use App\Http\Controllers\Consentimientos\ConsentimientoDocumentoController;
use App\Http\Controllers\Consentimientos\ConsentimientoEstadisticaController;
use App\Http\Controllers\Consentimientos\ConsentimientoMarcaController;
use App\Http\Controllers\Consentimientos\ConsentimientoPlantillaImagenController;
use App\Http\Controllers\Consentimientos\ConsentimientoPlantillaProductoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API — Consentimientos Informados (módulo genérico)
|--------------------------------------------------------------------------
|
| Flujo: generar -> crear token -> firma paciente (por token, sin login)
| -> firma médico -> PDF. La autorización por permisos se aplica en el
| controlador; la firma del paciente es pública (token) y va en web.php.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/consentimientos', [ConsentimientoDocumentoController::class, 'index'])
        ->name('api.consentimientos.index');

    Route::get('/consentimientos/{id}', [ConsentimientoDocumentoController::class, 'show'])
        ->whereNumber('id')
        ->name('api.consentimientos.show');

    Route::get('/consentimientos/buscar-paciente', [ConsentimientoDocumentoController::class, 'buscarPaciente'])
        ->name('api.consentimientos.buscar_paciente');

    Route::get('/consentimientos/medicos', [ConsentimientoDocumentoController::class, 'medicos'])
        ->name('api.consentimientos.medicos');

    Route::post('/consentimientos', [ConsentimientoDocumentoController::class, 'generar'])
        ->name('api.consentimientos.generar');

    Route::post('/consentimientos/token', [ConsentimientoDocumentoController::class, 'crearToken'])
        ->middleware('throttle:api-consent-create')
        ->name('api.consentimientos.token');

    Route::post('/consentimientos/firmar-medico', [ConsentimientoDocumentoController::class, 'firmarMedico'])
        ->name('api.consentimientos.firmar_medico');

    Route::post('/consentimientos/{id}/anular', [ConsentimientoDocumentoController::class, 'anular'])
        ->whereNumber('id')
        ->name('api.consentimientos.anular');

    Route::post('/consentimientos/{id}/pdf', [ConsentimientoDocumentoController::class, 'generarPdf'])
        ->whereNumber('id')
        ->name('api.consentimientos.pdf.generar');

    Route::get('/consentimientos/{id}/pdf/download', [ConsentimientoDocumentoController::class, 'descargarPdf'])
        ->whereNumber('id')
        ->name('api.consentimientos.pdf.descargar');

    /**
     * Marcas sobre imágenes del consentimiento
     */
    Route::get('/consentimientos/{documentoId}/marcas', [ConsentimientoMarcaController::class, 'index'])
        ->whereNumber('documentoId')
        ->name('api.consentimientos.marcas.index');

    Route::post('/consentimientos/{documentoId}/marcas', [ConsentimientoMarcaController::class, 'store'])
        ->whereNumber('documentoId')
        ->name('api.consentimientos.marcas.store');

    Route::put('/consentimientos/marcas/{id}', [ConsentimientoMarcaController::class, 'update'])
        ->whereNumber('id')
        ->name('api.consentimientos.marcas.update');

    Route::delete('/consentimientos/marcas/{id}', [ConsentimientoMarcaController::class, 'destroy'])
        ->whereNumber('id')
        ->name('api.consentimientos.marcas.destroy');

    /**
     * Adjuntos del consentimiento
     */
    Route::get('/consentimientos/{documentoId}/adjuntos', [ConsentimientoAdjuntoController::class, 'index'])
        ->whereNumber('documentoId')
        ->name('api.consentimientos.adjuntos.index');

    Route::post('/consentimientos/{documentoId}/adjuntos', [ConsentimientoAdjuntoController::class, 'store'])
        ->whereNumber('documentoId')
        ->name('api.consentimientos.adjuntos.store');

    Route::get('/consentimientos/adjuntos/{id}/download', [ConsentimientoAdjuntoController::class, 'download'])
        ->whereNumber('id')
        ->name('api.consentimientos.adjuntos.download');

    Route::delete('/consentimientos/adjuntos/{id}', [ConsentimientoAdjuntoController::class, 'destroy'])
        ->whereNumber('id')
        ->name('api.consentimientos.adjuntos.destroy');

    /**
     * Bloqueo de lecturas del médico
     */
    Route::get('/consentimientos/bloqueo/estado', [ConsentimientoBloqueoController::class, 'estado'])
        ->name('api.consentimientos.bloqueo.estado');

    Route::post('/consentimientos/bloqueo/liberar', [ConsentimientoBloqueoController::class, 'liberar'])
        ->name('api.consentimientos.bloqueo.liberar');

    /**
     * Asignación de plantilla a productos/sedes (M:N)
     */
    Route::get('/consentimientos-plantillas/{plantillaId}/productos', [ConsentimientoPlantillaProductoController::class, 'index'])
        ->whereNumber('plantillaId')
        ->name('api.consentimientos.plantilla_productos.index');

    Route::post('/consentimientos-plantillas/{plantillaId}/productos', [ConsentimientoPlantillaProductoController::class, 'store'])
        ->whereNumber('plantillaId')
        ->name('api.consentimientos.plantilla_productos.store');

    Route::delete('/consentimientos-plantillas/productos/{id}', [ConsentimientoPlantillaProductoController::class, 'destroy'])
        ->whereNumber('id')
        ->name('api.consentimientos.plantilla_productos.destroy');

    /**
     * Imágenes de plantilla (decorativas o marcables)
     */
    Route::get('/consentimientos-plantillas/{plantillaId}/imagenes', [ConsentimientoPlantillaImagenController::class, 'index'])
        ->whereNumber('plantillaId')
        ->name('api.consentimientos.plantilla_imagenes.index');

    Route::post('/consentimientos-plantillas/{plantillaId}/imagenes', [ConsentimientoPlantillaImagenController::class, 'store'])
        ->whereNumber('plantillaId')
        ->name('api.consentimientos.plantilla_imagenes.store');

    Route::get('/consentimientos-plantillas/imagenes/{id}/ver', [ConsentimientoPlantillaImagenController::class, 'stream'])
        ->whereNumber('id')
        ->name('api.consentimientos.plantilla_imagenes.ver');

    Route::delete('/consentimientos-plantillas/imagenes/{id}', [ConsentimientoPlantillaImagenController::class, 'destroy'])
        ->whereNumber('id')
        ->name('api.consentimientos.plantilla_imagenes.destroy');

    /**
     * Estadísticas / dashboard
     */
    Route::get('/consentimientos/estadisticas/resumen', [ConsentimientoEstadisticaController::class, 'resumen'])
        ->name('api.consentimientos.estadisticas.resumen');
    Route::get('/consentimientos/estadisticas/sede', [ConsentimientoEstadisticaController::class, 'porSede'])
        ->name('api.consentimientos.estadisticas.sede');
    Route::get('/consentimientos/estadisticas/producto', [ConsentimientoEstadisticaController::class, 'porProducto'])
        ->name('api.consentimientos.estadisticas.producto');
    Route::get('/consentimientos/estadisticas/medico', [ConsentimientoEstadisticaController::class, 'porMedico'])
        ->name('api.consentimientos.estadisticas.medico');
    Route::get('/consentimientos/estadisticas/tiempo-promedio', [ConsentimientoEstadisticaController::class, 'tiemposPromedio'])
        ->name('api.consentimientos.estadisticas.tiempo_promedio');
    Route::get('/consentimientos/estadisticas/productos-populares', [ConsentimientoEstadisticaController::class, 'productosPopulares'])
        ->name('api.consentimientos.estadisticas.productos_populares');
});
