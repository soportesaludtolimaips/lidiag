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

    // Cobertura de productos: cuáles del catálogo no tienen tarifa en el contrato
    Route::get('/contratos/{id}/cobertura', [ContratoController::class, 'cobertura'])
        ->name('api.contratos.cobertura')
        ->middleware(['permission:contratos.show']);

    // Previsualizar el diff del detalle de médicos (no escribe nada)
    Route::post('/contratos/{contratoId}/medicos/preview', [ContratoController::class, 'previewMedicos'])
        ->name('api.contratos.medicos.preview')
        ->middleware(['permission:contratos.detalle.edit']);

    // Sincronizar médicos-producto del contrato (diff, no destructivo)
    Route::put('/contratos/{contratoId}/medicos', [ContratoController::class, 'syncMedicos'])
        ->name('api.contratos.medicos.sync')
        ->middleware(['permission:contratos.detalle.edit']);

    // Plantilla de detalle del contrato (productos del catálogo, valores por médico)
    Route::get('/contratos/{contratoId}/plantilla-detalle', [ContratoController::class, 'exportPlantillaDetalle'])
        ->name('api.contratos.plantilla.detalle')
        ->middleware(['permission:contratos.index']);

    // Importar médicos-producto al contrato desde Excel (crea contratos_medicos)
    Route::post('/contratos/{contratoId}/importar-medicos', [ContratoController::class, 'importarMedicos'])
        ->name('api.contratos.medicos.importar')
        ->middleware(['permission:contratos.detalle.create']);

});
