<?php

use App\Http\Controllers\Agenda\AgendaDisponibilidaController;
use App\Http\Controllers\Agenda\AgendaDisponibilidaDetalleController;
use App\Http\Controllers\Dashboard\DashBoardController;
use App\Http\Controllers\Reportes\ReporteProduccion;
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
        ->name('api.reportes.dashboard');

    /*
     * Reportes Operativos Interactivos (Consolidados y Semáforos)
     */
    Route::get('/reportes-resumen-red', [ReporteProduccion::class, 'apiResumenRed'])
        ->name('api.reportes.resumen.red');
    Route::get('/reportes-semaforo-represados', [ReporteProduccion::class, 'apiSemaforoRepresados'])
        ->name('api.reportes.semaforo.represados');
    Route::get('/reportes-citas-whatsapp', [ReporteProduccion::class, 'apiCitasWhatsApp'])
        ->name('api.reportes.citas.whatsapp');
    Route::get('/reportes-almacenamiento', [ReporteProduccion::class, 'apiAlmacenamiento'])
        ->name('api.reportes.almacenamiento');
});
