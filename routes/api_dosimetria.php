<?php

use App\Http\Controllers\Api\DosimetriaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum,web'])->group(function () {
    // Rutas canónicas del módulo Dosis
    Route::get('/dosis/dashboard', [DosimetriaController::class, 'index'])
        ->name('api.dosis.dashboard');

    Route::post('/dosis/registros', [DosimetriaController::class, 'store'])
        ->name('api.dosis.registros');

    Route::post('/dosis/heartbeat', [DosimetriaController::class, 'heartbeat'])
        ->name('api.dosis.heartbeat');

    Route::get('/dosis/exportar', [DosimetriaController::class, 'exportar'])
        ->name('api.dosis.exportar');

    // Alias de retrocompatibilidad
    Route::get('/dosimetria/dashboard', [DosimetriaController::class, 'index'])
        ->name('api.dosimetria.dashboard');

    Route::post('/dosimetria/registros', [DosimetriaController::class, 'store'])
        ->name('api.dosimetria.registros');

    Route::post('/dosimetria/heartbeat', [DosimetriaController::class, 'heartbeat'])
        ->name('api.dosimetria.heartbeat');

    Route::get('/dosimetria/exportar', [DosimetriaController::class, 'exportar'])
        ->name('api.dosimetria.exportar');
});
