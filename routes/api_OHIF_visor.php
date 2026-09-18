<?php

use App\Http\Controllers\OHIF_visor\OhifVisorController;
use Illuminate\Support\Facades\Route;

/*
 * Rutas OHIF Viewer - QIDO/WADO
 *
 * SEGURIDAD (auditoría 2026-08-24): estas rutas servían imágenes y metadatos
 * DICOM (PHI) sin autenticación. Ahora requieren sesión válida; el visor las
 * consume desde el mismo origen, por lo que la cookie de sesión viaja sola.
 * Si en el futuro el visor se sirve desde otro host, migrar a tokens Sanctum.
 */
Route::middleware(['auth'])->group(function () {

    // QIDO-RS: buscar estudios
    Route::get('/qido/studies', [OhifVisorController::class, 'qidoStudies'])
        ->name('api.ohif.qido.studies');

    // QIDO-RS: buscar series de un estudio (también usada por WADO)
    Route::get('/wado/studies/{studyUID}/series', [OhifVisorController::class, 'qidoSeries'])
        ->name('api.ohif.qido.series');

    // QIDO-RS: buscar instances de una serie
    Route::get('/wado/studies/{studyUID}/series/{seriesUID}/instances', [OhifVisorController::class, 'qidoInstances'])
        ->name('api.ohif.qido.instances');

    // WADO-RS: recuperar instancia (imagen DICOM)
    Route::get('/wado/studies/{studyUID}/series/{seriesUID}/instances/{objectUID}', [OhifVisorController::class, 'wadoRetrieve'])
        ->name('api.ohif.wado.retrieve');

    // WADO-RS: recuperar frames (para OHIF)
    Route::get('/wado/studies/{studyUID}/series/{seriesUID}/instances/{objectUID}/frames/{frameNumber}', [OhifVisorController::class, 'wadoRetrieve'])
        ->name('api.ohif.wado.frames');

    // WADO-URI: recuperar imagen (alternativo)
    Route::get('/wado', [OhifVisorController::class, 'wadoUri'])
        ->name('api.ohif.wado.uri');

    // OHIF Metadata route
    Route::get('/wado/studies/{studyUID}/metadata', [OhifVisorController::class, 'getOhifMetadataOhif'])
        ->name('api.ohif.metadata.local');
    Route::get('/wado/studies/{studyUID}/series/{seriesUID}/metadata', [OhifVisorController::class, 'getOhifMetadataOhif'])
        ->name('api.ohif.metadata.local.series');

    // Originales
    Route::get('/dicom/{sedeId}/studies/{study_iuid}/metadata', [OhifVisorController::class, 'getOhifMetadata'])
        ->name('api.ohif.metadata.remote');
    Route::get('/dicom/download', [OhifVisorController::class, 'downloadDicom'])
        ->name('dicom.download');
    Route::get('/dicom/{sedeId}/studies', [OhifVisorController::class, 'listStudies'])
        ->name('api.ohif.list.remote');

});
