<?php

use App\Http\Controllers\OHIF_visor\OhifVisorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Debug: ver si llega aquí
Route::get('/debug', function () {
    return response()->json([
        'message' => 'Debug works',
        'routes' => \Route::getRoutes()->getRoutesByName(),
    ]);
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

// Rutas OHIF Viewer - FUERA del grupo api
require __DIR__.'/api_OHIF_visor.php';

// Ruta de prueba directa - SIN middleware
Route::get('/test-ohif', [OhifVisorController::class, 'qidoStudies']);
