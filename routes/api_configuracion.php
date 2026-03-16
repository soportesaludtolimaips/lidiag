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

// Rutas Públicas (Accesibles sin sesión, ej. para selección de sede en Login)
Route::get('/config-sedes/{configsede}/buscarPorId', [App\Http\Controllers\Configuracion\ConfigSedeController::class, 'buscarPorId'])
    ->name('api.config.sedes.buscarPorId');

// Rutas protegidas (requieren autenticación)
// Nota: en API usamos Sanctum (stateful) para que funcione con sesión/cookies.
Route::middleware(['auth:sanctum'])->group(function () {

    // Diagnosticos
    Route::resource('/config-diagnosticos', App\Http\Controllers\Configuracion\ConfigDiagnosticoController::class)
        ->names('api.config.diagnosticos')
        ->except(['create', 'show']);

    // Prioridades
    Route::resource('/config-prioridades', App\Http\Controllers\Configuracion\ConfigPrioridadController::class)
        ->names('api.config.prioridades')
        ->except(['create', 'show']);

    // Productos
    Route::resource('/config-productos', App\Http\Controllers\Configuracion\ConfigProductoController::class)
        ->names('api.config.productos')
        ->except(['create']);

    // Modulos
    Route::get('/config-modulos-listar', [App\Http\Controllers\Configuracion\ConfigModulosController::class, 'listarModulos'])
        ->name('api.config.modulos.listar');

    // Tipos de Documentos
    Route::get('/config-tipos-documentos', [App\Http\Controllers\Configuracion\ConfigTipoDocumentoController::class, 'index'])
        ->name('api.config.tipos.documentos.index');

    Route::resource('/config-tipos-documentos', App\Http\Controllers\Configuracion\ConfigTipoDocumentoController::class)
        ->names('api.config.tipos.documentos')
        ->except(['create', 'edit']);

    Route::get('/config-tipos-documentos/listar/activos', [App\Http\Controllers\Configuracion\ConfigTipoDocumentoController::class, 'listarActivos'])
        ->name('api.config.tipos.documentos.listar.activos');

    // Administradiras de salud
    Route::resource('/config-admin-salud', App\Http\Controllers\Configuracion\ConfigAdminSaludController::class)
        ->names('api.config.admin.salud')
        ->except(['create', 'show']);

    // Sedes
    Route::resource('/config-sedes', App\Http\Controllers\Configuracion\ConfigSedeController::class)
        ->names('api.config.sedes')
        ->except(['create', 'edit']);

    Route::get('/config-sedes/listar/sedes/activas', [App\Http\Controllers\Configuracion\ConfigSedeController::class, 'listarSedesActivas'])
        ->name('api.config.sedes.listarSedesActivas');

    // Plantillas
    Route::resource('/config-plantillas', App\Http\Controllers\Configuracion\ConfigPlantillaController::class)
        ->names('api.config.plantillas')
        ->except(['create', 'edit']);

    Route::get('/config-tipos-plantillas/listar/activos', [App\Http\Controllers\Configuracion\ConfigPlantillaTipoController::class, 'listarActivos'])
        ->name('api.config.tipos.plantillas.listar.activos');

    // Tipos de Plantillas (Rx, Ecografías, Consentimientos, etc.)
    Route::resource('/config-tipos-plantillas', App\Http\Controllers\Configuracion\ConfigPlantillaTipoController::class)
        ->names('api.config.tipos.plantillas')
        ->except(['create', 'edit']);

    // Consemtimientos informados
    Route::resource('/config-consentimiento-informado', App\Http\Controllers\Configuracion\ConsentimientoInformadoController::class)
        ->names('api.config.consentimiento.informado')
        ->except(['create', 'edit']);

    Route::get('/config-consentimiento-informado/listar/activos', [App\Http\Controllers\Configuracion\ConsentimientoInformadoController::class, 'listarActivos'])
        ->name('api.config.consentimiento.informado.listar.activos');

    /**
     * Plantillas de Consentimiento (WYSIWYG + versionado) para múltiples módulos (Rx/Ecografías/Oftalmología)
     * Relación: consentimiento_plantillas.config_modulo_id -> config_modulos.id
     */
    Route::get('/consentimientos-plantillas', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'index'])
        ->name('api.consentimientos.plantillas.index');

    Route::post('/consentimientos-plantillas/{id}/vigente', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'setVigente'])
        ->name('api.consentimientos.plantillas.vigente');

    Route::get('/consentimientos-plantillas/{id}/preview-pdf', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'previewPdf'])
        ->name('api.consentimientos.plantillas.preview_pdf');

    Route::get('/consentimientos-plantillas/producto/{productoId}/versions', [App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController::class, 'versions'])
        ->name('api.consentimientos.plantillas.versions');
});
