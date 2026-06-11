<?php

use App\Http\Controllers\Interface\SAHIController;
use App\Http\Controllers\Interface\TeleRadiologiaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Interfaces API Routes
|--------------------------------------------------------------------------
|
| Aquí se definen todas las rutas API relacionadas con las interfaces
| externas (Tele Radiología, SAHI, etc.)
|
*/

/*
 * Recepción de lecturas TLIMAGEN → SaludTolima (HMAC, sin auth).
 * Cliente envía X-Timestamp y X-Signature (ver docs/TELERADIOLOGIA_WEBHOOK_CLIENTE.md).
 */

Route::post('/tele-radiologia-recibir-lectura', [TeleRadiologiaController::class, 'recibirLectura'])
    ->name('api.tele.radiologia.recibir.lectura');

Route::middleware(['auth'])->group(function () {
    /*
     * Tele Radiologia API Endpoints
     */
    Route::post('/tele-radiologia-enviar-estudio', [TeleRadiologiaController::class, 'enviarEstudio'])
        ->name('api.tele.radiologia.enviar.estudio')
        ->middleware(['permission:tele.ragiologia.enviar|estudio.asignar,web']);

    Route::post('/tele-radiologia-listar-estudios', [TeleRadiologiaController::class, 'listarEstudios'])
        ->name('api.tele.radiologia.listar.estudios')
        ->middleware(['permission:tele.ragiologia.index|estudio.asignar,web']);

    Route::post('/tele-radiologia-enviar-orden', [TeleRadiologiaController::class, 'enviarOrdenTeleRadiologia'])
        ->name('api.tele.radiologia.enviar.orden')
        ->middleware(['permission:tele.ragiologia.enviar|estudio.asignar,web']);

    /*
     * Interface SAHI API Endpoints
     */
    Route::get('/interface-sahi-listar-atenciones/{numDocu}', [SAHIController::class, 'listarAtenciones'])
        ->name('api.sahi.listar.atenciones');

    Route::get('/interface-sahi-listar-listarProductosDiagnosticos/{idAtencion}', [SAHIController::class, 'listarProductosDiagnosticos'])
        ->name('api.sahi.listar.listarProductosDiagnosticos');

    Route::post('/interface-sahi/listar/productos/atencion', [SAHIController::class, 'listarProductosAtencion'])
        ->name('api.sahi.estudios.listar.productos.atencion');
});
