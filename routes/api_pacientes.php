<?php

use App\Http\Controllers\Pacientes\PacienteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/pacientes/listar', [PacienteController::class, 'listar'])
        ->name('api.pacientes.listar');

    Route::post('/pacientes', [PacienteController::class, 'store'])
        ->name('api.pacientes.store');

    Route::get('/pacientes/catalogos', [PacienteController::class, 'obtenerCatalogos'])
        ->name('api.pacientes.catalogos');

    Route::get('/pacientes/accession-sugerido', [PacienteController::class, 'obtenerAccessionSugerido'])
        ->name('api.pacientes.accession-sugerido');

    Route::get('/pacientes/worklist-estaciones', [PacienteController::class, 'obtenerEstacionesWorklist'])
        ->name('api.pacientes.worklist-estaciones');

    Route::post('/pacientes/enviar-worklist', [PacienteController::class, 'enviarWorklist'])
        ->name('api.pacientes.enviar-worklist');

    Route::post('/pacientes/cancelar-worklist', [PacienteController::class, 'cancelarWorklist'])
        ->name('api.pacientes.cancelar-worklist');

    Route::post('/pacientes/worklist-reconciliar', [PacienteController::class, 'reconciliarWorklist'])
        ->name('api.pacientes.worklist-reconciliar');

    Route::get('/pacientes/{id}', [PacienteController::class, 'show'])
        ->name('api.pacientes.show');

    Route::put('/pacientes/{id}', [PacienteController::class, 'update'])
        ->name('api.pacientes.update');

    Route::delete('/pacientes/{id}', [PacienteController::class, 'destroy'])
        ->name('api.pacientes.destroy');
});
