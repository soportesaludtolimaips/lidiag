<?php

use App\Http\Controllers\Configuracion\ConfigAdminSaludController;
use App\Http\Controllers\Configuracion\ConfigDiagnosticoController;
use App\Http\Controllers\Configuracion\ConfigModulosController;
use App\Http\Controllers\Configuracion\ConfigPlantillaController;
use App\Http\Controllers\Configuracion\ConfigPlantillaTipoController;
use App\Http\Controllers\Configuracion\ConfigPrioridadController;
use App\Http\Controllers\Configuracion\ConfigProductoController;
use App\Http\Controllers\Configuracion\ConfigRecomendacionProductoController;
use App\Http\Controllers\Configuracion\ConfigSedeController;
use App\Http\Controllers\Configuracion\ConfigServicioCitasController;
use App\Http\Controllers\Configuracion\ConfigTipoDocumentoController;
use App\Http\Controllers\Configuracion\ConsentimientoInformadoController;
use App\Http\Controllers\Configuracion\ServicioProductoController;
use App\Http\Controllers\Consentimientos\ConsentimientoPlantillasController;
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
Route::get('/config-sedes/{configsede}/buscarPorId', [ConfigSedeController::class, 'buscarPorId'])
    ->name('api.config.sedes.buscarPorId');

// Rutas protegidas (requieren autenticación)
// Nota: en API usamos Sanctum (stateful) para que funcione con sesión/cookies.
Route::middleware(['auth'])->group(function () {

    // Diagnosticos
    Route::resource('/config-diagnosticos', ConfigDiagnosticoController::class)
        ->names('api.config.diagnosticos')
        ->except(['create', 'show']);

    // Prioridades
    Route::resource('/config-prioridades', ConfigPrioridadController::class)
        ->names('api.config.prioridades')
        ->except(['create', 'show']);

    // Productos
    Route::resource('/config-productos', ConfigProductoController::class)
        ->names('api.config.productos')
        ->except(['create']);

    // Recomendaciones de productos
    Route::resource('/config-recomendacion-productos', ConfigRecomendacionProductoController::class)
        ->names('api.config.recomendacion.productos')
        ->except(['create']);

    // Modulos
    Route::get('/config-modulos-listar', [ConfigModulosController::class, 'listarModulos'])
        ->name('api.config.modulos.listar');

    // Tipos de Documentos
    Route::get('/config-tipos-documentos', [ConfigTipoDocumentoController::class, 'index'])
        ->name('api.config.tipos.documentos.index');

    Route::resource('/config-tipos-documentos', ConfigTipoDocumentoController::class)
        ->names('api.config.tipos.documentos')
        ->except(['create', 'edit']);

    Route::get('/config-tipos-documentos/listar/activos', [ConfigTipoDocumentoController::class, 'listarActivos'])
        ->name('api.config.tipos.documentos.listar.activos');

    // Administradiras de salud
    Route::resource('/config-admin-salud', ConfigAdminSaludController::class)
        ->names('api.config.admin.salud')
        ->except(['create', 'show']);

    // Sedes
    Route::resource('/config-sedes', ConfigSedeController::class)
        ->names('api.config.sedes')
        ->except(['create', 'edit']);

    Route::get('/config-sedes/listar/sedes/activas', [ConfigSedeController::class, 'listarSedesActivas'])
        ->name('api.config.sedes.listarSedesActivas');

    // Plantillas
    Route::resource('/config-plantillas', ConfigPlantillaController::class)
        ->names('api.config.plantillas')
        ->except(['create', 'edit']);

    Route::get('/config-tipos-plantillas/listar/activos', [ConfigPlantillaTipoController::class, 'listarActivos'])
        ->name('api.config.tipos.plantillas.listar.activos');

    // Tipos de Plantillas (Rx, Ecografías, Consentimientos, etc.)
    Route::resource('/config-tipos-plantillas', ConfigPlantillaTipoController::class)
        ->names('api.config.tipos.plantillas')
        ->except(['create', 'edit']);

    // Consemtimientos informados
    Route::resource('/config-consentimiento-informado', ConsentimientoInformadoController::class)
        ->names('api.config.consentimiento.informado')
        ->except(['create', 'edit']);

    Route::get('/config-consentimiento-informado/listar/activos', [ConsentimientoInformadoController::class, 'listarActivos'])
        ->name('api.config.consentimiento.informado.listar.activos');

    /**
     * Plantillas de Consentimiento (WYSIWYG + versionado) para múltiples módulos (Rx/Ecografías/Oftalmología)
     * Relación: consentimiento_plantillas.config_modulo_id -> config_modulos.id
     */
    Route::get('/consentimientos-plantillas', [ConsentimientoPlantillasController::class, 'index'])
        ->name('api.consentimientos.plantillas.index');

    Route::post('/consentimientos-plantillas/{id}/vigente', [ConsentimientoPlantillasController::class, 'setVigente'])
        ->name('api.consentimientos.plantillas.vigente');

    Route::get('/consentimientos-plantillas/{id}/preview-pdf', [ConsentimientoPlantillasController::class, 'previewPdf'])
        ->name('api.consentimientos.plantillas.preview_pdf');

    Route::get('/consentimientos-plantillas/producto/{productoId}/versions', [ConsentimientoPlantillasController::class, 'versions'])
        ->name('api.consentimientos.plantillas.versions');

    // Servicio de Citas
    Route::get('/config-servicio-citas/mis-servicios', [ConfigServicioCitasController::class, 'misServicios'])
        ->name('api.config.servicio.citas.mis_servicios');

    Route::resource('/config-servicio-citas', ConfigServicioCitasController::class)
        ->names('api.config.servicio.citas')
        ->except(['create', 'edit']);

    // Números WhatsApp por sede
    Route::get('/config-sede-whatsapp-numbers', [\App\Http\Controllers\Configuracion\ConfigSedeWhatsappNumberController::class, 'index'])
        ->name('api.config.sede.whatsapp.numbers.index');

    Route::post('/config-sede-whatsapp-numbers', [\App\Http\Controllers\Configuracion\ConfigSedeWhatsappNumberController::class, 'store'])
        ->name('api.config.sede.whatsapp.numbers.store');

    Route::put('/config-sede-whatsapp-numbers/{id}', [\App\Http\Controllers\Configuracion\ConfigSedeWhatsappNumberController::class, 'update'])
        ->name('api.config.sede.whatsapp.numbers.update');

    Route::delete('/config-sede-whatsapp-numbers/{id}', [\App\Http\Controllers\Configuracion\ConfigSedeWhatsappNumberController::class, 'destroy'])
        ->name('api.config.sede.whatsapp.numbers.destroy');

    // Servicio Citas <-> Productos
    Route::get('/servicio-producto', [ServicioProductoController::class, 'index'])
        ->name('api.config.servicio.producto.index');

    Route::post('/servicio-producto', [ServicioProductoController::class, 'store'])
        ->name('api.config.servicio.producto.store');

    Route::delete('/servicio-producto/{id}', [ServicioProductoController::class, 'destroy'])
        ->name('api.config.servicio.producto.destroy');

    Route::get('/servicio-producto/productos-no-vinculados', [ServicioProductoController::class, 'productosNoVinculados'])
        ->name('api.config.servicio.producto.no_vinculados');
});
