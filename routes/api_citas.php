<?php

use App\Http\Controllers\Agenda\AgendaCitaController;
use Illuminate\Support\Facades\Route;

// Webhook DEBE ir antes de {cita} para que "webhook" no sea capturado como parámetro
Route::match(['get', 'post'], '/citas/webhook', [AgendaCitaController::class, 'webhook'])->name('api.citas.webhook');
Route::post('/citas/test/enviar', [AgendaCitaController::class, 'testEnviar'])->name('api.citas.test.enviar');

Route::prefix('citas')->group(function () {
    Route::get('/', [AgendaCitaController::class, 'index'])->name('api.citas.index');
    Route::post('/', [AgendaCitaController::class, 'store'])->name('api.citas.store');
    Route::get('/servicios', [AgendaCitaController::class, 'servicios'])->name('api.citas.servicios');
    Route::get('/productos/{servicio_cita_id}', [AgendaCitaController::class, 'productosPorServicio'])->name('api.citas.productos');
    Route::get('/disponibilidad', [AgendaCitaController::class, 'disponibilidad'])->name('api.citas.disponibilidad');
    Route::get('/pendientes', [AgendaCitaController::class, 'pendientes'])->name('api.citas.pendientes');

    Route::get('/{cita}', [AgendaCitaController::class, 'show'])->name('api.citas.show');
    Route::put('/{cita}', [AgendaCitaController::class, 'update'])->name('api.citas.update');
    Route::delete('/{cita}', [AgendaCitaController::class, 'destroy'])->name('api.citas.destroy');
    Route::post('/{cita}/asignar', [AgendaCitaController::class, 'asignar'])->name('api.citas.asignar');
    Route::post('/{cita}/confirmar', [AgendaCitaController::class, 'confirmar'])->name('api.citas.confirmar');
    Route::post('/{cita}/cancelar', [AgendaCitaController::class, 'cancelar'])->name('api.citas.cancelar');
    Route::post('/{cita}/cumplir', [AgendaCitaController::class, 'cumplir'])->name('api.citas.cumplir');
    Route::post('/{cita}/no-asistio', [AgendaCitaController::class, 'noAsistio'])->name('api.citas.noAsistio');
    Route::post('/{cita}/rechazar', [AgendaCitaController::class, 'rechazar'])->name('api.citas.rechazar');
    Route::get('/{cita}/conversacion', [AgendaCitaController::class, 'verConversacion'])->name('api.citas.conversacion');
});

Route::apiResource('/citas-rechazo-motivos', \App\Http\Controllers\Agenda\ConfigCitaRechazoMotivoController::class)
    ->names('api.citas.rechazo.motivos')
    ->except(['create', 'edit', 'show']);


