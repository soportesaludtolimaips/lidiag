<?php

use App\Http\Controllers\Oftalmologia\OftalmologiaAdmisionController;
use App\Http\Controllers\Oftalmologia\OftalmologiaConsultaController;
use Illuminate\Support\Facades\Route;

Route::prefix('oftalmologia')->middleware(['auth:sanctum'])->group(function () {
    // Admisiones
    Route::get('atenciones', [OftalmologiaAdmisionController::class, 'index'])->name('oftalmologia.atenciones.index');
    Route::get('atenciones/sahi', [OftalmologiaAdmisionController::class, 'listarSahi'])->name('oftalmologia.atenciones.sahi');
    Route::post('admisionar', [OftalmologiaAdmisionController::class, 'store'])->name('oftalmologia.admisionar');
    Route::post('atenciones/{id}/anular', [OftalmologiaAdmisionController::class, 'anular'])->name('oftalmologia.atenciones.anular');

    // Consulta Clínica / Historia
    Route::get('consultas/{atencionId}', [OftalmologiaConsultaController::class, 'show'])->name('oftalmologia.consultas.show');
    Route::post('consultas', [OftalmologiaConsultaController::class, 'store'])->name('oftalmologia.consultas.store');
    Route::get('diagnosticos/buscar/{termino}', [OftalmologiaConsultaController::class, 'buscarDiagnosticos'])->name('oftalmologia.diagnosticos.buscar');
    Route::get('productos/buscar/{termino}', [OftalmologiaConsultaController::class, 'buscarProductos'])->name('oftalmologia.productos.buscar');
    Route::post('consultas/{atencionId}/sincronizar-sahi', [OftalmologiaConsultaController::class, 'sincronizarSahi'])->name('oftalmologia.consultas.sincronizar-sahi');
});
