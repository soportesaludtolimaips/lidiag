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
        ->middleware(['permission:seguridad.usuarios.index|seguridad.usuarios.create|seguridad.usuarios.edit|seguridad.usuarios.show|seguridad.usuarios.destroy']);

    Route::post('/seguridad/usuarios/update', [UserController::class, 'update'])
        ->name('api.seguridad.update')
        ->middleware(['permission:seguridad.usuarios.edit']);

    Route::post('/seguridad-actualizar-perfil/{idUsua}', [UserController::class, 'actualizarPerfil'])
        ->name('api.seguridad.actualizar.perfil');

    Route::put('/seguridad-usuarios/estado/{idUsua}', [UserController::class, 'estadoUsuario'])
        ->name('api.seguridad.usuario.estado')
        ->middleware(['permission:seguridad.usuarios.edit']);

    Route::get('/user.listarUsuarios/{tipo_user}/{modulo}', [UserController::class, 'listarUsuarios'])
        ->name('api.user.listarUsuarios');

    Route::resource('/seguridad-roles', RolController::class)
        ->names('api.seguridad.roles')
        ->middleware(['permission:seguridad.roles.index|seguridad.roles.create|seguridad.roles.edit|seguridad.roles.show|seguridad.roles.destroy']);

    Route::put('/seguridad-roles/estado/{id}', [RolController::class, 'estadoRol'])
        ->name('api.seguridad.roles.estado')
        ->middleware(['permission:seguridad.roles.edit']);

    Route::get('/usuario-por-modulo/{modulo_id}', [UserController::class, 'usuariosPorModulo'])
        ->name('api.usuarios.por.modulo')
        ->middleware(['permission:seguridad.usuarios.index']);

    Route::get('/seguridad-listarPermisosUsuario/{id}', [UserController::class, 'listarPermisosUsuario'])
        ->name('api.seguridad.listarPermisosUsuario')
        ->middleware(['permission:seguridad.usuarios.show']);

    Route::get('/seguridad-auditoria', [AuditLogController::class, 'index'])
        ->name('api.seguridad.auditoria.index')
        ->middleware(['permission:seguridad.auditoria.index']);

    Route::get('/seguridad-auditoria/eventos', [AuditLogController::class, 'eventos'])
        ->name('api.seguridad.auditoria.eventos')
        ->middleware(['permission:seguridad.auditoria.index']);
});
