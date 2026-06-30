<?php

use App\Http\Controllers\Seguridad\AuditLogController;
use App\Http\Controllers\Seguridad\RolController;
use App\Http\Controllers\Seguridad\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Seguridad API Routes
|--------------------------------------------------------------------------
|
| Aquí se definen todas las rutas API relacionadas con la seguridad
| del sistema (usuarios, roles, permisos, perfiles, etc.)
|
*/

Route::middleware(['auth'])->group(function () {
    /*
     * Seguridad API Endpoints
     */
    Route::resource('/seguridad-usuarios', UserController::class)
        ->names('api.seguridad.usuarios')
        ->except('update')
        ->middleware(['permission:seguridad.usuarios.index|seguridad.usuarios.create|seguridad.usuarios.edit|seguridad.usuarios.show|seguridad.usuarios.destroy,web']);

    Route::post('/seguridad/usuarios/update', [UserController::class, 'update'])
        ->name('api.seguridad.update')
        ->middleware(['permission:seguridad.usuarios.edit,web']);

    Route::post('/seguridad-actualizar-perfil/{idUsua}', [UserController::class, 'actualizarPerfil'])
        ->name('api.seguridad.actualizar.perfil');

    Route::put('/seguridad-usuarios/estado/{idUsua}', [UserController::class, 'estadoUsuario'])
        ->name('api.seguridad.usuario.estado')
        ->middleware(['permission:seguridad.usuarios.edit,web']);

    Route::get('/user.listarUsuarios/{tipo_user}/{modulo}', [UserController::class, 'listarUsuarios'])
        ->name('api.user.listarUsuarios')
        // Este endpoint se consume desde vistas Blade + Vue (misma app). En algunos entornos
        // `auth:sanctum` puede dar 401 si el dominio no se reconoce como "stateful" (cookies/sesión).
        // Forzamos sesión web para evitar 401 al usuario logueado.
        ->withoutMiddleware(['auth:sanctum'])
        ->middleware(['web', 'auth']);

    Route::resource('/seguridad-roles', RolController::class)
        ->names('api.seguridad.roles')
        ->middleware(['permission:seguridad.roles.index|seguridad.roles.create|seguridad.roles.edit|seguridad.roles.show|seguridad.roles.destroy,web']);

    Route::get('/usuario-por-modulo/{modulo_id}', [UserController::class, 'usuariosPorModulo'])
        ->name('api.usuarios.por.modulo')
        ->middleware(['permission:seguridad.usuarios.index,web']);

    Route::get('/seguridad-listarPermisosUsuario/{id}', [UserController::class, 'listarPermisosUsuario'])
        ->name('api.seguridad.listarPermisosUsuario')
        ->middleware(['permission:seguridad.usuarios.show,web']);

    Route::get('/seguridad-auditoria', [AuditLogController::class, 'index'])
        ->name('api.seguridad.auditoria.index')
        ->middleware(['permission:seguridad.auditoria.index,web']);

    Route::get('/seguridad-auditoria/eventos', [AuditLogController::class, 'eventos'])
        ->name('api.seguridad.auditoria.eventos')
        ->middleware(['permission:seguridad.auditoria.index,web']);
});
