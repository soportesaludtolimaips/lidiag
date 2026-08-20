<?php

use App\Http\Controllers\Contratacion\ContratoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {

    Route::resource('/contratos', ContratoController::class)
        ->names('api.contratos')
        ->except(['create', 'edit'])
        ->middleware(['permission:contratos.index|contratos.create|contratos.show|contratos.edit|contratos.destroy']);

    Route::put('/contratos/{id}/estado', [ContratoController::class, 'toggleEstado'])
        ->name('api.contratos.estado')
        ->middleware(['permission:contratos.edit']);

    Route::post('/contratos/{contratoId}/detalle', [ContratoController::class, 'storeDetalle'])
        ->name('api.contratos.detalle.store')
        ->middleware(['permission:contratos.detalle.create']);

    Route::put('/contratos/detalle/{detalleId}', [ContratoController::class, 'updateDetalle'])
        ->name('api.contratos.detalle.update')
        ->middleware(['permission:contratos.detalle.edit']);

    Route::delete('/contratos/detalle/{detalleId}', [ContratoController::class, 'destroyDetalle'])
        ->name('api.contratos.detalle.destroy')
        ->middleware(['permission:contratos.detalle.destroy']);

    Route::put('/contratos/{contratoId}/detalles', [ContratoController::class, 'syncDetalles'])
        ->name('api.contratos.detalles.sync')
        ->middleware(['permission:contratos.detalle.edit']);

    Route::get('/contratos/{contratoId}/plantilla', [ContratoController::class, 'exportPlantilla'])
        ->name('api.contratos.plantilla')
        ->middleware(['permission:contratos.index']);

    Route::post('/contratos/{contratoId}/importar', [ContratoController::class, 'importDetalles'])
        ->name('api.contratos.importar')
        ->middleware(['permission:contratos.detalle.create']);

});
