<?php

use App\Http\Controllers\Api\DosimetriaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dosimetria/dashboard', [DosimetriaController::class, 'index'])
        ->name('api.dosimetria.dashboard');

    Route::post('/dosimetria/registros', [DosimetriaController::class, 'store'])
        ->name('api.dosimetria.registros');
});
