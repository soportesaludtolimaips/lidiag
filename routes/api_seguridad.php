<?php

use App\Http\Controllers\Seguridad\AuditLogController;
use App\Http\Controllers\Seguridad\BackupMonitorController;
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
    // Usuarios — rutas por método con permisos individuales
    Route::get('/seguridad-usuarios', [UserController::class, 'index'])
        ->name('api.seguridad.usuarios.index')
        ->middleware(['permission:seguridad.usuarios.index']);

    Route::post('/seguridad-usuarios', [UserController::class, 'store'])
        ->name('api.seguridad.usuarios.store')
        ->middleware(['permission:seguridad.usuarios.create']);

    Route::get('/seguridad-usuarios/{id}', [UserController::class, 'show'])
        ->name('api.seguridad.usuarios.show');

    Route::delete('/seguridad-usuarios/{id}', [UserController::class, 'destroy'])
        ->name('api.seguridad.usuarios.destroy')
        ->middleware(['permission:seguridad.usuarios.destroy']);

    Route::post('/seguridad/usuarios/update', [UserController::class, 'update'])
        ->name('api.seguridad.update')
        ->middleware(['permission:seguridad.usuarios.edit']);

    Route::post('/seguridad-actualizar-perfil/{idUsua}', [UserController::class, 'actualizarPerfil'])
        ->name('api.seguridad.actualizar.perfil');

    Route::put('/seguridad-usuarios/estado/{idUsua}', [UserController::class, 'estadoUsuario'])
        ->name('api.seguridad.usuario.estado')
        ->middleware(['permission:seguridad.usuarios.edit']);

    Route::get('/user.listarUsuarios/{tipo_user}/{modulo}', [UserController::class, 'listarUsuarios'])
        ->name('api.user.listarUsuarios')
        ->middleware(['permission:seguridad.usuarios.index']);

    // Roles — rutas por método con permisos individuales
    Route::get('/seguridad-roles', [RolController::class, 'index'])
        ->name('api.seguridad.roles.index')
        ->middleware(['permission:seguridad.roles.index']);

    Route::post('/seguridad-roles', [RolController::class, 'store'])
        ->name('api.seguridad.roles.store')
        ->middleware(['permission:seguridad.roles.create']);

    Route::get('/seguridad-roles/{id}', [RolController::class, 'show'])
        ->name('api.seguridad.roles.show')
        ->middleware(['permission:seguridad.roles.show']);

    Route::put('/seguridad-roles/{id}', [RolController::class, 'update'])
        ->name('api.seguridad.roles.update')
        ->middleware(['permission:seguridad.roles.edit']);

    Route::delete('/seguridad-roles/{id}', [RolController::class, 'destroy'])
        ->name('api.seguridad.roles.destroy')
        ->middleware(['permission:seguridad.roles.destroy']);

    Route::put('/seguridad-roles/estado/{id}', [RolController::class, 'estadoRol'])
        ->name('api.seguridad.roles.estado')
        ->middleware(['permission:seguridad.roles.edit']);

    Route::get('/usuario-por-modulo/{modulo_id}', [UserController::class, 'usuariosPorModulo'])
        ->name('api.usuarios.por.modulo')
        ->middleware(['permission:seguridad.usuarios.index']);

    Route::get('/seguridad-listarPermisosUsuario/{id}', [UserController::class, 'listarPermisosUsuario'])
        ->name('api.seguridad.listarPermisosUsuario')
        ->middleware(['permission:seguridad.usuarios.show']);

    // Auditoría
    Route::get('/seguridad-auditoria', [AuditLogController::class, 'index'])
        ->name('api.seguridad.auditoria.index')
        ->middleware(['permission:seguridad.auditoria.index']);

    Route::get('/seguridad-auditoria/eventos', [AuditLogController::class, 'eventos'])
        ->name('api.seguridad.auditoria.eventos')
        ->middleware(['permission:seguridad.auditoria.index']);

    // Monitoreo de Backups
    Route::get('/seguridad-backups/estado', [BackupMonitorController::class, 'estado'])
        ->name('api.seguridad.backups.estado')
        ->middleware(['permission:seguridad.backups.index']);

    Route::post('/seguridad-backups/ejecutar-db', [BackupMonitorController::class, 'ejecutarBackupDb'])
        ->name('api.seguridad.backups.ejecutar-db')
        ->middleware(['permission:seguridad.backups.ejecutar']);
});
