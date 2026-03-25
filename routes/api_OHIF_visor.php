<?php

use App\Http\Controllers\Dashboard\DashBoardController;
use App\Http\Controllers\OHIF_visor\OhifVisorController;
use Illuminate\Support\Facades\Route;

// Fíjate en el orden: dicom -> {sedeId} -> studies -> {study_iuid} -> metadata
Route::get('/dicom/{sedeId}/studies/{study_iuid}/metadata', [OhifVisorController::class, 'getOhifMetadata']);

// Y la ruta de descarga (BulkData)
Route::get('/dicom/download', [OhifVisorController::class, 'downloadDicom'])->name('dicom.download');

// Ruta para listar estudios: https://lidiag.test/api/dicom/{sedeId}/studies
Route::get('/dicom/{sedeId}/studies', [OhifVisorController::class, 'listStudies']);
