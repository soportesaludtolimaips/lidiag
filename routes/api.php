<?php

use App\Http\Controllers\Api\BackupUploadController;
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

Route::middleware(['auth'])->get('/user', function (Request $request) {
    return $request->user();
});

// Ruta de prueba pública
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});

// Endpoint receptor de backups de bases de datos DCM por sedes (autenticado por Bearer Token)
Route::post('/backups/upload-dcm', [BackupUploadController::class, 'uploadDcm']);

/**
 * Diagnóstico mínimo de sesión para el interceptor 401 del frontend.
 * Solo informa si la sesión es válida y a qué usuario pertenece;
 * NO expone cookies, IPs, user-agents ni sesiones de otros usuarios.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
Route::middleware('auth')->get('/debug-session', function () {
    return [
        'auth_check' => auth()->check(),
        'user_id_in_session' => auth()->id(),
    ];
});

/*
 * Endpoints eliminados por fuga de información (auditoría de seguridad 2026-08-24):
 * - GET /api/debug-session-auth: exponía email/nombre del usuario autenticado vía sanctum.
 * - GET /api/debug: volcaba el registro completo de rutas nombradas de la aplicación.
 */

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

/*
 * ELIMINADA por auditoría de seguridad 2026-08-24: exponía estudios DICOM
 * (PHI) sin autenticación:
 * Route::get('/test-ohif', [OhifVisorController::class, 'qidoStudies']);
 */

// Rutas de Citas
require __DIR__.'/api_citas.php';

// Rutas de DICOM Burn
require __DIR__.'/api_burn.php';
