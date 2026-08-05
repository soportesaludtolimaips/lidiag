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

Route::post('/dicom/generate-iso', [DicomBurnerController::class, 'generateIso'])->middleware('auth:sanctum')
    ->name('dicom.generate-iso');
Route::get('/dicom/burn-status/{id}', [DicomBurnerController::class, 'status'])->middleware('auth:sanctum')
    ->name('dicom.burn-status');
Route::get('/dicom/list-drives', [DicomBurnerController::class, 'listDrives'])->middleware('auth:sanctum')
    ->name('dicom.list-drives');
Route::post('/dicom/burn', [DicomBurnerController::class, 'burn'])->middleware('auth:sanctum')
    ->name('dicom.burn');
Route::post('/dicom/cleanup', [DicomBurnerController::class, 'cleanup'])->middleware('auth:sanctum')
    ->name('dicom.cleanup');
Route::post('/dicom/generate-zip', [DicomBurnerController::class, 'generateZip'])->middleware('auth:sanctum')
    ->name('dicom.generate-zip');
Route::get('/dicom/zip-status/{id}', [DicomBurnerController::class, 'zipStatus'])->middleware('auth:sanctum')
    ->name('dicom.zip-status');
