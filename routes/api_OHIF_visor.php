<?php

use App\Http\Controllers\OHIF_visor\OhifVisorController;
use Illuminate\Support\Facades\Route;

// Rutas OHIF Viewer - QIDO/WADO
// QIDO-RS: buscar estudios
Route::get('/qido/studies', [OhifVisorController::class, 'qidoStudies']);

// QIDO-RS: buscar series de un estudio (también usada por WADO)
Route::get('/wado/studies/{studyUID}/series', [OhifVisorController::class, 'qidoSeries']);

// QIDO-RS: buscar instances de una serie
Route::get('/wado/studies/{studyUID}/series/{seriesUID}/instances', [OhifVisorController::class, 'qidoInstances']);

// WADO-RS: recuperar instancia (imagen DICOM)
Route::get('/wado/studies/{studyUID}/series/{seriesUID}/instances/{objectUID}', [OhifVisorController::class, 'wadoRetrieve']);

// WADO-RS: recuperar frames (para OHIF)
Route::get('/wado/studies/{studyUID}/series/{seriesUID}/instances/{objectUID}/frames/{frameNumber}', [OhifVisorController::class, 'wadoRetrieve']);

// WADO-URI: recuperar imagen (alternativo)
Route::get('/wado', [OhifVisorController::class, 'wadoUri']);

// OHIF Metadata route
Route::get('/wado/studies/{studyUID}/metadata', [OhifVisorController::class, 'getOhifMetadataOhif']);
Route::get('/wado/studies/{studyUID}/series/{seriesUID}/metadata', [OhifVisorController::class, 'getOhifMetadataOhif']);

// Originales
Route::get('/dicom/{sedeId}/studies/{study_iuid}/metadata', [OhifVisorController::class, 'getOhifMetadata']);
Route::get('/dicom/download', [OhifVisorController::class, 'downloadDicom'])->name('dicom.download');

// Exportar imagen del estudio como JPEG (limpia o con datos quemados, estilo K-PACS)
// para enviar a sistemas externos que solo aceptan JPG/JPEG.
Route::get('/dicom/export-jpeg', [OhifVisorController::class, 'exportarJpeg'])->name('dicom.export.jpeg');

// Exporta y ENVÍA el JPEG al sistema externo configurado (services.imagen_externa)
Route::post('/dicom/enviar-jpeg', [OhifVisorController::class, 'enviarJpeg'])->name('dicom.enviar.jpeg');
Route::get('/dicom/{sedeId}/studies', [OhifVisorController::class, 'listStudies']);
