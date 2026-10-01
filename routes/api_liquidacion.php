<?php

use App\Http\Controllers\Liquidacion\LiquidacionController;
use Illuminate\Support\Facades\Route;

Route::middleware(["auth"])->group(function () {

    // Descargar liquidación del periodo en Excel
    Route::get("/liquidacion/exportar", [LiquidacionController::class, "exportar"])
        ->name("api.liquidacion.exportar")
        ->middleware(["permission:liquidacion.exportar"]);

    // Productividad por médico para el corte de mes (lee lo congelado)
    Route::get("/liquidacion/productividad", [LiquidacionController::class, "productividad"])
        ->name("api.liquidacion.productividad")
        ->middleware(["permission:liquidacion.index"]);

    // Liquidación de pagos a médicos por sede y rango de fechas
    Route::get("/liquidacion", [LiquidacionController::class, "calcular"])
        ->name("api.liquidacion.calcular")
        ->middleware(["permission:liquidacion.index"]);

});
