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
use App\Http\Controllers\Configuracion\ConfigSedeWhatsappNumberController;
use App\Http\Controllers\Configuracion\ConfigServicioController;
use App\Http\Controllers\Configuracion\ConfigTipoDocumentoController;
use App\Http\Controllers\Configuracion\ConfigWhatsappController;
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

// Rutas protegidas (requieren autenticación)
// Nota: en API usamos Sanctum (stateful) para que funcione con sesión/cookies.
Route::middleware(['auth', 'permission:config.diagnosticos.index|config.prioridades.index|config.productos.index|config.sedes.index|config.plantillas.index'])->group(function () {

    // Sedes - buscarPorId (requiere permiso config.sedes.index)
    Route::get('/config-sedes/{configsede}/buscarPorId', [ConfigSedeController::class, 'buscarPorId'])
        ->name('api.config.sedes.buscarPorId');

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

    Route::get('/config-plantillas/listar/activos/{tipoId}/{medicoId}', [ConfigPlantillaController::class, 'listarActivosPorTipo'])
        ->name('api.config.plantillas.listar.activos.por.tipo');

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
    Route::get('/config-servicio-citas/mis-servicios', [ConfigServicioController::class, 'misServicios'])
        ->name('api.config.servicio.citas.mis_servicios');

    Route::resource('/config-servicio-citas', ConfigServicioController::class)
        ->names('api.config.servicio.citas')
        ->except(['create', 'edit']);

    // Números WhatsApp por sede
    Route::get('/config-sede-whatsapp-numbers', [ConfigSedeWhatsappNumberController::class, 'index'])
        ->name('api.config.sede.whatsapp.numbers.index');

    Route::post('/config-sede-whatsapp-numbers', [ConfigSedeWhatsappNumberController::class, 'store'])
        ->name('api.config.sede.whatsapp.numbers.store');

    Route::put('/config-sede-whatsapp-numbers/{id}', [ConfigSedeWhatsappNumberController::class, 'update'])
        ->name('api.config.sede.whatsapp.numbers.update');

    Route::delete('/config-sede-whatsapp-numbers/{id}', [ConfigSedeWhatsappNumberController::class, 'destroy'])
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

    // Configuración WhatsApp Bot (saludos por sede/servicio)
    Route::get('/config-whatsapp/buscar', [ConfigWhatsappController::class, 'buscar'])
        ->name('api.config.whatsapp.buscar');

    // Protegemos store/update/destroy con permisos
    Route::post('/config-whatsapp', [ConfigWhatsappController::class, 'store'])
        ->name('api.config.whatsapp.store')
        ->middleware('permission:config.whatsapp.saludo.create');

    Route::put('/config-whatsapp/{id}', [ConfigWhatsappController::class, 'update'])
        ->name('api.config.whatsapp.update')
        ->middleware('permission:config.whatsapp.saludo.edit');

    Route::delete('/config-whatsapp/{id}', [ConfigWhatsappController::class, 'destroy'])
        ->name('api.config.whatsapp.destroy')
        ->middleware('permission:config.whatsapp.saludo.destroy');

    // Index y show son de lectura (permiso index)
    Route::get('/config-whatsapp', [ConfigWhatsappController::class, 'index'])
        ->name('api.config.whatsapp.index')
        ->middleware('permission:config.whatsapp.saludo.index');

    Route::get('/config-whatsapp/{id}', [ConfigWhatsappController::class, 'show'])
        ->name('api.config.whatsapp.show')
        ->middleware('permission:config.whatsapp.saludo.index');
});
