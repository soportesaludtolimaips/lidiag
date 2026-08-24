<?php

use App\Http\Controllers\Contratacion\ContratoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {

    // Plantilla general de productos (config_productos activos)
    Route::get('/contratos/plantilla-general', [ContratoController::class, 'exportPlantillaGeneral'])
        ->name('api.contratos.plantilla.general')
        ->middleware(['permission:contratos.index']);

    Route::resource('/contratos', ContratoController::class)
        ->names('api.contratos')
        ->except(['create', 'edit'])
        ->middleware(['permission:contratos.index|contratos.create|contratos.show|contratos.edit|contratos.destroy']);

    Route::put('/contratos/{id}/estado', [ContratoController::class, 'toggleEstado'])
        ->name('api.contratos.estado')
        ->middleware(['permission:contratos.edit']);

    // Sincronizar médicos-producto del contrato (delete all + insert)
    Route::put('/contratos/{contratoId}/medicos', [ContratoController::class, 'syncMedicos'])
        ->name('api.contratos.medicos.sync')
        ->middleware(['permission:contratos.detalle.edit']);

    // Importar productos al contrato desde Excel (crea contratos_productos)
    Route::post('/contratos/{contratoId}/importar-productos', [ContratoController::class, 'importarProductos'])
        ->name('api.contratos.productos.importar')
        ->middleware(['permission:contratos.detalle.create']);

    // Plantilla de detalle del contrato (productos configurados, valores por médico)
    Route::get('/contratos/{contratoId}/plantilla-detalle', [ContratoController::class, 'exportPlantillaDetalle'])
        ->name('api.contratos.plantilla.detalle')
        ->middleware(['permission:contratos.index']);

    // Importar médicos-producto al contrato desde Excel (crea contratos_medicos)
    Route::post('/contratos/{contratoId}/importar-medicos', [ContratoController::class, 'importarMedicos'])
        ->name('api.contratos.medicos.importar')
        ->middleware(['permission:contratos.detalle.create']);

});
