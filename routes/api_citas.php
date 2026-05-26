<?php

use App\Http\Controllers\Citas\CitaController;
use Illuminate\Support\Facades\Route;

Route::prefix('citas')->group(function () {
    Route::get('/', [CitaController::class, 'index'])->name('api.citas.index');
    Route::post('/', [CitaController::class, 'store'])->name('api.citas.store');
    Route::get('/servicios', [CitaController::class, 'servicios'])->name('api.citas.servicios');
    Route::get('/productos/{servicio_cita_id}', [CitaController::class, 'productosPorServicio'])->name('api.citas.productos');
    Route::get('/disponibilidad', [CitaController::class, 'disponibilidad'])->name('api.citas.disponibilidad');
    Route::get('/{cita}', [CitaController::class, 'show'])->name('api.citas.show');
    Route::put('/{cita}', [CitaController::class, 'update'])->name('api.citas.update');
    Route::delete('/{cita}', [CitaController::class, 'destroy'])->name('api.citas.destroy');
    Route::post('/{cita}/asignar', [CitaController::class, 'asignar'])->name('api.citas.asignar');
    Route::post('/{cita}/confirmar', [CitaController::class, 'confirmar'])->name('api.citas.confirmar');
    Route::post('/{cita}/cancelar', [CitaController::class, 'cancelar'])->name('api.citas.cancelar');
    Route::post('/{cita}/cumplir', [CitaController::class, 'cumplir'])->name('api.citas.cumplir');
    Route::post('/{cita}/no-asistio', [CitaController::class, 'noAsistio'])->name('api.citas.noAsistio');
    Route::get('/{cita}/conversacion', [CitaController::class, 'verConversacion'])->name('api.citas.conversacion');
});

Route::post('/citas/webhook', [CitaController::class, 'webhook'])->name('api.citas.webhook');
Route::post('/citas/test/enviar', [CitaController::class, 'testEnviar'])->name('api.citas.test.enviar');
