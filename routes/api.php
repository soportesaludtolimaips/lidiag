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

Route::middleware(['auth'])->get('/user', function (Request $request) {
    return $request->user();
});

// Ruta de prueba pública
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});

Route::middleware('auth')->get('/debug-session', function (Request $request) {
    $sessionId = session()->getId();
    $sessionRecord = DB::table('sessions')->where('id', $sessionId)->first();
    $now = time();

    $cookieHeader = $request->header('Cookie', '');
    $cookies = [];
    foreach (explode(';', $cookieHeader) as $pair) {
        $parts = explode('=', trim($pair), 2);
        if (count($parts) === 2) {
            $cookies[$parts[0]] = substr($parts[1], 0, 80) . '...';
        }
    }

    $allSessions = DB::table('sessions')
        ->where('user_id', '=', 1)
        ->orWhere('id', '=', $sessionId)
        ->get()
        ->map(function ($s) {
            return [
                'id' => $s->id,
                'user_id' => $s->user_id,
                'last_activity' => $s->last_activity,
                'last_activity_readable' => date('Y-m-d H:i:s', $s->last_activity),
                'ip_address' => $s->ip_address,
                'user_agent' => substr($s->user_agent ?? '', 0, 100),
            ];
        });

    $sessionFromCookie = null;
    try {
        $sessionFromCookie = DB::table('sessions')->find($sessionId);
    } catch (\Throwable $e) {
        logger()->warning('debug-session: fallo al leer sesión', [
            'session_id' => $sessionId,
            'error' => $e->getMessage(),
        ]);
    }

    return [
        'session_id' => $sessionId,
        'auth_check' => auth()->check(),
        'auth_check_web' => auth()->guard('web')->check(),
        'user_id_in_session' => optional($sessionFromCookie)->user_id,
        'session_driver' => config('session.driver'),
        'session_lifetime_config' => config('session.lifetime'),
        'now_readable' => date('Y-m-d H:i:s', $now),
        'last_activity_readable' => $sessionFromCookie
            ? date('Y-m-d H:i:s', $sessionFromCookie->last_activity)
            : null,
        'session_in_db' => $sessionFromCookie ? 'yes' : 'no',
        'count_sessions_for_user_1' => DB::table('sessions')->where('user_id', 1)->count(),
        'total_sessions_in_db' => DB::table('sessions')->count(),
        'cookies_in_header' => $cookies,
        'all_relevant_sessions' => $allSessions,
        'stateful_domains' => config('sanctum.stateful'),
        'headers' => [
            'origin' => $request->header('Origin'),
            'referer' => $request->header('Referer'),
            'host' => $request->header('Host'),
        ],
    ];
});

Route::middleware('auth:sanctum')->get('/debug-session-auth', function (Request $request) {
    return [
        'authenticated' => true,
        'user' => $request->user()?->only(['id', 'name', 'email']),
    ];
});

// Debug: ver si llega aquí (requiere autenticación)
Route::middleware('auth')->get('/debug', function () {
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

// Rutas de Citas
require __DIR__.'/api_citas.php';
