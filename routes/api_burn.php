<?php

use App\Http\Controllers\DicomBurnerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DICOM Burn API Routes
|--------------------------------------------------------------------------
|
| Routes for generating ISO/DICOM packages and checking burn status.
|
*/

Route::middleware(['auth'])->group(function () {
    Route::post('/dicom/generate-iso', [DicomBurnerController::class, 'generateIso'])
        ->name('dicom.generate-iso');
    Route::get('/dicom/burn-status/{id}', [DicomBurnerController::class, 'status'])
        ->name('dicom.burn-status');
    Route::get('/dicom/list-drives', [DicomBurnerController::class, 'listDrives'])
        ->name('dicom.list-drives');
    Route::post('/dicom/burn', [DicomBurnerController::class, 'burn'])
        ->name('dicom.burn');
    Route::post('/dicom/cleanup', [DicomBurnerController::class, 'cleanup'])
        ->name('dicom.cleanup');
    Route::post('/dicom/generate-zip', [DicomBurnerController::class, 'generateZip'])
        ->name('dicom.generate-zip');
    Route::get('/dicom/zip-status/{id}', [DicomBurnerController::class, 'zipStatus'])
        ->name('dicom.zip-status');
    Route::get('/dicom/download-zip/{filename}', [DicomBurnerController::class, 'downloadZip'])
        ->name('dicom.download-zip');
});
