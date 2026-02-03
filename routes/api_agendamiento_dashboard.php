<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashBoardController;

/*
|--------------------------------------------------------------------------
| Agendamiento y Dashboard API Routes
|--------------------------------------------------------------------------
|
| Aquí se definen todas las rutas API relacionadas con el agendamiento
| y el dashboard del sistema
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    /*
     * Agendamiento API Endpoints
     */
    Route::resource('agenda-disponibilidad', App\Http\Controllers\Agenda\AgendaDisponibilidaController::class)
        ->names('api.agenda.disponibilidad')
        ->except(['create', 'edit', '']);

    Route::resource('agenda-detalle', App\Http\Controllers\Agenda\AgendaDisponibilidaDetalleController::class)
        ->names('api.agenda.detalle')
        ->except(['create', 'edit', 'update']);

    /*
     * Dashboard API Endpoints
     */
    Route::get('/reportes.dashboard', [DashBoardController::class, 'dashboard'])
        ->name('api.reportes.dashboard');
});
