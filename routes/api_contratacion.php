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

    // Plantilla 1: Tarifas de Entidad / Hospital (descarga Excel)
    Route::get('/contratos/{contratoId}/plantilla-entidad', [ContratoController::class, 'exportPlantillaEntidad'])
        ->name('api.contratos.plantilla.entidad')
        ->middleware(['permission:contratos.index|contratos.show']);

    // Plantilla 2: Tarifas de Médico Lector (descarga Excel personalizada por médico)
    Route::get('/contratos/{contratoId}/medicos/{medicoId}/plantilla', [ContratoController::class, 'exportPlantillaMedico'])
        ->name('api.contratos.plantilla.medico')
        ->middleware(['permission:contratos.index|contratos.show']);

    // Vista previa no destructiva de tarifas de Entidad / Hospital
    Route::post('/contratos/{contratoId}/preview-importar-entidad', [ContratoController::class, 'previewImportarEntidad'])
        ->name('api.contratos.preview.importar.entidad')
        ->middleware(['permission:contratos.edit|contratos.detalle.edit']);

    // Confirmación y persistencia de tarifas de Entidad / Hospital
    Route::post('/contratos/{contratoId}/confirmar-importar-entidad', [ContratoController::class, 'confirmarImportarEntidad'])
        ->name('api.contratos.confirmar.importar.entidad')
        ->middleware(['permission:contratos.edit|contratos.detalle.edit']);

    // Vista previa no destructiva de tarifas de Médicos Lectores (validación estricta A4)
    Route::post('/contratos/{contratoId}/preview-importar-medicos', [ContratoController::class, 'previewImportarMedicos'])
        ->name('api.contratos.preview.importar.medicos')
        ->middleware(['permission:contratos.detalle.create|contratos.detalle.edit']);

    // Confirmación y persistencia de tarifas de Médicos Lectores
    Route::post('/contratos/{contratoId}/confirmar-importar-medicos', [ContratoController::class, 'confirmarImportarMedicos'])
        ->name('api.contratos.confirmar.importar.medicos')
        ->middleware(['permission:contratos.detalle.create|contratos.detalle.edit']);

    // Plantilla de detalle del contrato (legacy)
    Route::get('/contratos/{contratoId}/plantilla-detalle', [ContratoController::class, 'exportPlantillaDetalle'])
        ->name('api.contratos.plantilla.detalle')
        ->middleware(['permission:contratos.index']);

    // Importar médicos-producto al contrato desde Excel (crea contratos_medicos)
    Route::post('/contratos/{contratoId}/importar-medicos', [ContratoController::class, 'importarMedicos'])
        ->name('api.contratos.medicos.importar')
        ->middleware(['permission:contratos.detalle.create']);

});
