<?php

use App\Http\Controllers\Api\DosimetriaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Rutas canónicas del módulo Dosis
    Route::get('/dosis/dashboard', [DosimetriaController::class, 'index'])
        ->name('api.dosis.dashboard');

    Route::post('/dosis/registros', [DosimetriaController::class, 'store'])
        ->name('api.dosis.registros');

    // Alias de retrocompatibilidad
    Route::get('/dosimetria/dashboard', [DosimetriaController::class, 'index'])
        ->name('api.dosimetria.dashboard');

    Route::post('/dosimetria/registros', [DosimetriaController::class, 'store'])
        ->name('api.dosimetria.registros');
});
