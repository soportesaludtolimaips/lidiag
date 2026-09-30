<?php

use App\Http\Controllers\Liquidacion\LiquidacionController;
use Illuminate\Support\Facades\Route;

Route::middleware(["auth"])->group(function () {

    // Liquidación de pagos a médicos por sede y rango de fechas
    Route::get("/liquidacion", [LiquidacionController::class, "calcular"])
        ->name("api.liquidacion.calcular")
        ->middleware(["permission:liquidacion.index"]);

});
