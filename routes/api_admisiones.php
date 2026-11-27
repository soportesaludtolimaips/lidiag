<?php

use App\Http\Controllers\Admisiones\AdminAdmisionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admisiones y Pendientes de Asignar API Routes
|--------------------------------------------------------------------------
|
| Rutas API para la gestión de trazabilidad en admin_admisiones, sincronización
| con el workflow DICOM y envío a lectura médica desde Pendientes de asignar.
|
*/

Route::middleware(["auth:sanctum"])->group(function () {
    Route::post("/admisiones/listar", [AdminAdmisionController::class, "listar"])
        ->name("api.admisiones.listar");

    Route::post("/admisiones/pendientes-asignar", [AdminAdmisionController::class, "pendientesAsignar"])
        ->name("api.admisiones.pendientes-asignar");

    Route::post("/admisiones", [AdminAdmisionController::class, "store"])
        ->name("api.admisiones.store");

    Route::get("/admisiones/{id}", [AdminAdmisionController::class, "show"])
        ->whereNumber("id")
        ->name("api.admisiones.show");

    Route::patch("/admisiones/{id}/estado", [AdminAdmisionController::class, "actualizarEstado"])
        ->whereNumber("id")
        ->name("api.admisiones.estado");

    Route::post("/admisiones/{id}/asignar-lectura", [AdminAdmisionController::class, "asignarLectura"])
        ->whereNumber("id")
        ->name("api.admisiones.asignar-lectura");

    Route::post("/admisiones/{id}/cancelar", [AdminAdmisionController::class, "cancelar"])
        ->whereNumber("id")
        ->name("api.admisiones.cancelar");
});
