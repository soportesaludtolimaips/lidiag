<?php

use App\Http\Controllers\Agenda\AgendaDisponibilidaController;
use App\Http\Controllers\Agenda\AgendaDisponibilidaDetalleController;
use App\Http\Controllers\Dashboard\DashBoardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agendamiento y Dashboard API Routes
|--------------------------------------------------------------------------
|
| Aquí se definen todas las rutas API relacionadas con el agendamiento
| y el dashboard del sistema
|
*/

Route::middleware(['auth'])->group(function () {
    /*
     * Agendamiento API Endpoints
     */
    Route::resource('agenda-disponibilidad', AgendaDisponibilidaController::class)
        ->names('api.agenda.disponibilidad')
        ->except(['create', 'edit', '']);

    Route::resource('agenda-detalle', AgendaDisponibilidaDetalleController::class)
        ->names('api.agenda.detalle')
        ->except(['create', 'edit', 'update']);

    /*
     * Dashboard API Endpoints
     */
    Route::get('/reportes.dashboard', [DashBoardController::class, 'dashboard'])
        ->name('api.reportes.dashboard')
        // Este dashboard se consume desde la misma app (Blade + Vue). Forzamos sesión web
        // para evitar 401 cuando Sanctum no reconoce la request como "stateful".
        ->withoutMiddleware(['auth:sanctum'])
        ->middleware(['web', 'auth']);
});
