<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/* Route::middleware('auth:api:sanctum')->get('/user', function (Request $request) {
    return $request->user();
}); */

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Ruta de prueba pública
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});

/*
 * Rutas API organizadas por módulos en archivos separados:
 * - api_configuracion.php
 * - api_estudios.php
 * - api_ecografias.php
 * - api_seguridad.php
 * - api_interfaces.php
 * - api_agendamiento_dashboard.php
 */
