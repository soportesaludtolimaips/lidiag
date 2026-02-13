<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Configuración API Routes
|--------------------------------------------------------------------------
|
| Aquí se definen todas las rutas API relacionadas con la configuración
| del sistema (productos, sedes, plantillas, tipos de documentos, etc.)
|
*/

// Rutas públicas para catálogos (sin autenticación)
Route::resource('/config-diagnosticos', App\Http\Controllers\Configuracion\ConfigDiagnosticoController::class)
    ->names('api.config.diagnosticos')
    ->except(['create', 'show'])
    ->middleware([]);

Route::resource('/config-prioridades', App\Http\Controllers\Configuracion\ConfigPrioridadController::class)
    ->names('api.config.prioridades')
    ->except(['create', 'show'])
    ->middleware([]);

Route::resource('/config-productos', App\Http\Controllers\Configuracion\ConfigProductoController::class)
    ->names('api.config.productos')
    ->except(['create'])
    ->middleware([]);

Route::get('/config-modulos-listar', [App\Http\Controllers\Configuracion\ConfigModulosController::class, 'listarModulos'])
    ->name('api.config.modulos.listar')
    ->middleware([]);

Route::resource('/config-tipos-documentos', App\Http\Controllers\Configuracion\ConfigTipoDocumentoController::class)
    ->names('api.config.tipos.documentos')
    ->except(['create', 'edit'])
    ->middleware([]);

Route::get('/config-tipos-documentos/listar/activos', [App\Http\Controllers\Configuracion\ConfigTipoDocumentoController::class, 'listarActivos'])
    ->name('api.config.tipos.documentos.listar.activos')
    ->middleware([]);

Route::get('/config-sedes/listar/sedes/activas', [App\Http\Controllers\Configuracion\ConfigSedeController::class, 'listarSedesActivas'])
    ->name('api.config.sedes.listarSedesActivas')
    ->middleware([]);

// Catálogo público: buscar sede por ID (usado por UI al seleccionar sede)
Route::get('/config-sedes/{configsede}/buscarPorId', [App\Http\Controllers\Configuracion\ConfigSedeController::class, 'buscarPorId'])
    ->name('api.config.sedes.buscarPorId')
    ->middleware([]);

// Rutas protegidas (requieren autenticación)
// Nota: en API usamos Sanctum (stateful) para que funcione con sesión/cookies.
Route::middleware(['auth:sanctum'])->group(function () {
    /*
     * Configuración API Endpoints
     */
    Route::resource('/config-admin-salud', App\Http\Controllers\Configuracion\ConfigAdminSaludController::class)
        ->names('api.config.admin.salud')
        ->except(['create', 'show'])
        ->middleware(['permission:config.admin.salud.index|config.admin.salud.create|config.admin.salud.edit|config.admin.salud.destroy,web']);

    Route::post('/config/sedes/updateWitchPlantillas', [App\Http\Controllers\Configuracion\ConfigSedeController::class, 'update'])
        ->name('api.config.sedes.update')
        ->middleware(['permission:config.sedes.edit,web']);

    Route::resource('/config-sedes', App\Http\Controllers\Configuracion\ConfigSedeController::class)
        ->names('api.config.sedes')
        ->except(['create', 'show', 'update'])
        ->middleware(['permission:config.sedes.index|config.sedes.create|config.sedes.edit|config.sedes.destroy,web']);

    Route::resource('/config-plantillas', App\Http\Controllers\Configuracion\ConfigPlantillaController::class)
        ->names('api.config.plantillas')
        ->except(['create', 'edit'])
        ->middleware([]);

    // Catálogo usado por UI (Vue) — evitar 401 si Sanctum no trata la request como stateful.
    Route::get('/config-plantillas/listar/activos/{tipo_id}', [App\Http\Controllers\Configuracion\ConfigPlantillaController::class, 'listarActivos'])
        ->name('api.config.plantillas.listar.activos')
        ->withoutMiddleware(['auth:sanctum']);

    Route::resource('/config-tipos-plantillas', App\Http\Controllers\Configuracion\ConfigPlantillaTipoController::class)
        ->names('api.config.tipos.plantillas')
        ->except(['create', 'edit'])
        ->middleware([]);

    // Catálogo usado por UI (Vue) — evitar 401 si Sanctum no trata la request como stateful.
    Route::get('/config-tipos-plantillas/listar/activos', [App\Http\Controllers\Configuracion\ConfigPlantillaTipoController::class, 'listarActivos'])
        ->name('api.config.tipos.plantillas.listar.activos')
        ->withoutMiddleware(['auth:sanctum']);

    Route::resource('/config-consentimiento-informado', App\Http\Controllers\Configuracion\ConsentimientoInformadoController::class)
        ->names('api.config.consentimiento.informado')
        ->except(['create', 'edit'])
        ->middleware(['permission:config.consentimiento_informado.index|config.consentimiento_informado.create|config.consentimiento_informado.edit|config.consentimiento_informado.destroy,web']);

    Route::get('/config-consentimiento-informado/listar/activos', [App\Http\Controllers\Configuracion\ConsentimientoInformadoController::class, 'listarActivos'])
        ->name('api.config.consentimiento.informado.listar.activos')
        ->middleware(['permission:config.consentimiento_informado.index,web']);

    /**
     * Plantillas de Consentimiento (WYSIWYG + versionado) para múltiples módulos (Rx/Ecografías/Oftalmología)
     * Relación: consentimiento_plantillas.config_modulo_id -> config_modulos.id
     */
    Route::get('/consentimientos-plantillas', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'index'])
        ->name('api.consentimientos.plantillas.index')
        ->middleware(['permission:ecografias.consentimientos.plantillas.index,web']);

    Route::get('/consentimientos-plantillas/{id}', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'show'])
        ->name('api.consentimientos.plantillas.show')
        ->middleware(['permission:ecografias.consentimientos.plantillas.index,web']);

    Route::post('/consentimientos-plantillas', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'store'])
        ->name('api.consentimientos.plantillas.store')
        ->middleware(['permission:ecografias.consentimientos.plantillas.create,web']);

    Route::put('/consentimientos-plantillas/{id}', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'update'])
        ->name('api.consentimientos.plantillas.update')
        ->middleware(['permission:ecografias.consentimientos.plantillas.edit,web']);

    Route::post('/consentimientos-plantillas/{id}/vigente', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'setVigente'])
        ->name('api.consentimientos.plantillas.vigente')
        ->middleware(['permission:ecografias.consentimientos.plantillas.edit,web']);

    Route::get('/consentimientos-plantillas/producto/{productoId}/versions', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'versions'])
        ->name('api.consentimientos.plantillas.versions')
        ->middleware(['permission:ecografias.consentimientos.plantillas.index,web']);

    Route::delete('/consentimientos-plantillas/{id}', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'destroy'])
        ->name('api.consentimientos.plantillas.destroy')
        ->middleware(['permission:ecografias.consentimientos.plantillas.destroy,web']);

    Route::get('/consentimientos-plantillas/{id}/preview-pdf', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'previewPdf'])
        ->name('api.consentimientos.plantillas.preview_pdf')
        ->middleware(['permission:ecografias.consentimientos.plantillas.preview_pdf,web']);
});
