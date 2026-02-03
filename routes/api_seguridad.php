<?php

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

Route::middleware(['auth:sanctum'])->group(function () {
    /*
     * Seguridad API Endpoints
     */
    Route::resource('/seguridad-usuarios', App\Http\Controllers\Seguridad\UserController::class)
        ->names('api.seguridad.usuarios')
        ->except('update')
        ->middleware(['permission:seguridad.usuarios.index|seguridad.usuarios.create|seguridad.usuarios.edit|seguridad.usuarios.show|seguridad.usuarios.destroy,web']);

    Route::post('/seguridad/usuarios/update', [App\Http\Controllers\Seguridad\UserController::class, 'update'])
        ->name('api.seguridad.update')
        ->middleware(['permission:seguridad.usuarios.edit,web']);

    Route::put('/seguridad-actualizar-perfil/{idUsua}', [App\Http\Controllers\Seguridad\UserController::class, 'actualizarPerfil'])
        ->name('api.seguridad.actualizar.perfil');

    Route::put('/seguridad-usuarios/estado/{idUsua}', [App\Http\Controllers\Seguridad\UserController::class, 'estadoUsuario'])
        ->name('api.seguridad.usuario.estado')
        ->middleware(['permission:seguridad.usuarios.edit,web']);

    Route::get('/user.listarUsuarios/{tipo_user}/{modulo}', [App\Http\Controllers\Seguridad\UserController::class, 'listarUsuarios'])
        ->name('api.user.listarUsuarios');

    Route::resource('/seguridad-roles', App\Http\Controllers\Seguridad\RolController::class)
        ->names('api.seguridad.roles')
        ->middleware(['permission:seguridad.roles.index|seguridad.roles.create|seguridad.roles.edit|seguridad.roles.show|seguridad.roles.destroy,web']);

    Route::get('/usuario-por-modulo/{modulo_id}', [App\Http\Controllers\Seguridad\UserController::class, 'usuariosPorModulo'])
        ->name('api.usuarios.por.modulo')
        ->middleware(['permission:seguridad.usuarios.index,web']);

    Route::get('/seguridad-listarPermisosUsuario/{id}', [App\Http\Controllers\Seguridad\UserController::class, 'listarPermisosUsuario'])
        ->name('api.seguridad.listarPermisosUsuario')
        ->middleware(['permission:seguridad.usuarios.show,web']);
});
