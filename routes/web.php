<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Ecografias\EcografiaConsentimientosController;
use App\Http\Controllers\Estudios\EstudioAudioController;
use App\Http\Controllers\Estudios\EstudioController;
use App\Http\Controllers\Estudios\EstudioSoporteHCController;
use App\Http\Controllers\Importar\ImportarDicom;
use App\Http\Controllers\Reportes\ReporteProduccion;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth/login');
});

/**
 * Autenticación (MPA/Blade como fuente de verdad)
 * - Definimos rutas explícitas para evitar exponer endpoints no usados (register/reset).
 * - `POST /login` tiene throttle dedicado (`throttle:login`).
 * - Si la petición es AJAX/JSON (Axios), el controller responde JSON.
 */
Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login')
    ->middleware(['guest']);
Route::post('/login', [LoginController::class, 'login'])
    ->middleware(['guest', 'throttle:login']);
Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware(['auth']);

// Password Reset
use App\Http\Controllers\Agenda\AgendaCitaController;
use App\Http\Controllers\Api\DosimetriaController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Configuracion\ConfigSedeController;
use App\Http\Controllers\Liquidacion\LiquidacionController;
use App\Http\Controllers\Publico\VerificacionInformeController;

Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset')
    ->middleware(['guest']);
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])
    ->name('password.update')
    ->middleware(['guest', 'throttle:5,1']);

// Password Change (authenticated — must change password before accessing dashboard)
Route::get('/password/cambiar', [PasswordChangeController::class, 'showForm'])
    ->name('password.cambiar')
    ->middleware(['auth']);
Route::post('/password/cambiar', [PasswordChangeController::class, 'update'])
    ->name('password.cambiar.update')
    ->middleware(['auth']);

Route::get('dashboard', fn () => view('layouts.app'))
    ->name('dashboard')
    ->middleware(['auth']);

/**
 * Helpdesk
 */
Route::get('/helpdesk-tickets', fn () => view('layouts.app'))
    ->name('helpdesk.tickets.listar')
    ->middleware(['auth', 'permission:helpdesk.tickets.index']);

Route::get('/helpdesk-tickets/crear', fn () => view('layouts.app'))
    ->name('helpdesk.tickets.crear')
    ->middleware(['auth', 'permission:helpdesk.tickets.create']);

Route::get('/helpdesk-tickets/{ticket_id}', fn () => view('layouts.app'))
    ->name('helpdesk.tickets.detalle')
    ->middleware(['auth', 'permission:helpdesk.tickets.show']);

Route::get('/helpdesk-reportes', fn () => view('layouts.app'))
    ->name('helpdesk.reportes')
    ->middleware(['auth', 'permission:helpdesk.reportes.index']);

Route::get('/helpdesk-notificaciones', fn () => view('layouts.app'))
    ->name('helpdesk.notificaciones')
    ->middleware(['auth', 'permission:helpdesk.notificaciones.index']);

/**
 * Gestión de Pacientes y Admisión
 */
Route::get('/pacientes/admision', fn () => view('layouts.app'))
    ->name('gestion.pacientes.admision')
    ->middleware(['auth', 'permission:gestion.pacientes.index']);

/**
 * Módulo Dosis y Control de Radiación (Resolución 482)
 */
Route::get('/dosis/dashboard', fn () => view('layouts.app'))
    ->name('dosis.dashboard')
    ->middleware(['auth', 'permission:dosimetria.dashboard']);

Route::get('/dosimetria/dashboard', fn () => view('layouts.app'))
    ->name('dosimetria.dashboard')
    ->middleware(['auth', 'permission:dosimetria.dashboard']);

/**
 * Estudios radiologicos
 */
Route::get('/estudios.asignar.listar', fn () => view('layouts.app'))
    ->name('estudios.asignar.listar')
    ->middleware(['auth', 'permission:estudio.asignar']);
Route::get('/estudios.asignados.listar', fn () => view('layouts.app'))
    ->name('estudios.asignados.listar')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir']);
Route::get('/estudios.notificar.index', fn () => view('layouts.app'))
    ->name('estudios.notificar.index')
    ->middleware(['auth', 'permission:estudios.notificar.index']);

Route::get('/descarga-soportes-hc/{nomArchivoEncriptadote}', [EstudioSoporteHCController::class, 'descargarSoporte'])
    ->name('study.descarga.soporte.hc')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir|estudio.leerEstudio']);
Route::get('/descargar.pdf.lectura/{nomArchivo}', [EstudioController::class, 'descargarPdfLectura'])
    ->name('descargar.pdf.lectura')
    ->middleware('auth');

// Audios (fallback sin depender de symlink `public/audios`)
Route::get('/audios/{filename}', [EstudioAudioController::class, 'stream'])
    ->name('audios.stream')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir|estudio.leerEstudio|tele.ragiologia.transcribir'])
    ->where('filename', '(audio|cargado)_[A-Z0-9]{10,}\.mp3');

Route::get('/estudios.leer.estudio.listar', fn () => view('layouts.app'))
    ->name('estudios.leer.estudio.listar')
    ->middleware(['auth', 'permission:estudio.leerEstudio']);

Route::get('/estudios.transcribir.listar', fn () => view('layouts.app'))
    ->name('estudios.transcribir.listar')
    ->middleware(['auth', 'permission:estudio.transcribir']);
Route::get('/estudios.tele-radiologia', fn () => view('layouts.app'))
    ->name('estudios.teleradiologis.listar')
    ->middleware(['auth', 'permission:tele.ragiologia.index|tele.ragiologia.enviar|tele.ragiologia.transcribir|estudio.asignar|estudio.transcribir']);

/**
 * Tele Radiologia
 */
Route::get('/tele-radiologia', fn () => view('layouts.app'))
    ->name('teleradiologia.index')
    ->middleware(['auth', 'permission:tele.ragiologia.index|tele.ragiologia.enviar|estudio.asignar']);
Route::redirect('/teleradiologia.index', '/tele-radiologia');
Route::get('/tele-radiologia-transcribir', fn () => view('layouts.app'))
    ->name('teleradiologia.transcribir')
    ->middleware(['auth', 'permission:tele.ragiologia.transcribir|estudio.transcribir']);
Route::redirect('/teleradiologia.transcribir', '/tele-radiologia-transcribir');

/*
 * Ecografias
 */
Route::get('/ecografias.admisiones', fn () => view('layouts.app'))
    ->name('ecografias.admisiones')
    ->middleware(['auth', 'permission:ecografias.admision']);
Route::get('/ecografias.admisionadas', fn () => view('layouts.app'))
    ->name('ecografias.adminisionadas')
    ->middleware(['auth', 'permission:ecografias.index|ecografias.admision']);
Route::get('/ecografias-pendientes-lecturas', fn () => view('layouts.app'))
    ->name('ecografias.pendientes.lecturas')
    ->middleware(['auth', 'permission:ecografias.leer']);

Route::get('/ecografias-consentimientos', fn () => view('layouts.app'))
    ->name('ecografias.consentimientos')
    ->middleware(['auth', 'permission:ecografias.consentimientos.index']);

Route::get('/ecografias-consentimientos-plantillas', fn () => view('layouts.app'))
    ->name('ecografias.consentimientos.plantillas')
    ->middleware(['auth', 'permission:ecografias.consentimientos.plantillas.index']);

// Alias en Configuración (menú Parametrización)
Route::get('/config-consentimientos', fn () => view('layouts.app'))
    ->name('config.consentimientos.index')
    ->middleware(['auth', 'permission:ecografias.consentimientos.index']);

Route::get('/config-consentimientos-plantillas', fn () => view('layouts.app'))
    ->name('config.consentimientos.plantillas.index')
    ->middleware(['auth', 'permission:ecografias.consentimientos.plantillas.index']);

/**
 * Firma de consentimiento (paciente/acompañante) - acceso por token (sin login).
 */
Route::view('/firma/consentimiento-ecografia/{token}', 'modulos.ecografias.firma-consentimiento')
    ->name('ecografias.consentimientos.firma.paciente.view');
Route::get('/firma/consentimiento-ecografia/{token}/info', [EcografiaConsentimientosController::class, 'infoPorToken'])
    ->name('ecografias.consentimientos.firma.paciente.info')
    ->middleware(['throttle:60,1']);
Route::post('/firma/consentimiento-ecografia/{token}/firmar', [EcografiaConsentimientosController::class, 'firmarPacientePorToken'])
    ->name('ecografias.consentimientos.firma.paciente.post')
    ->middleware(['throttle:20,1']);

/**
 * Reportes
 */
Route::get('reportes-produccion', fn () => view('layouts.app'))
    ->name('reportes.produccion')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);
Route::get('descarga-reportes-produccion', [ReporteProduccion::class, 'reporteProduccionDetallado'])
    ->name('descarga.reportes.produccion')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);

Route::get('reportes-estudios-pac', fn () => view('layouts.app'))
    ->name('reportes.estudios.pac')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);
Route::get('descarga-reportes-estudios-pac', [ReporteProduccion::class, 'reporteEstudiosPAC'])
    ->name('descarga.reportes.estudios.pac')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);
Route::get('descarga-reportes-dosimetria', [DosimetriaController::class, 'exportar'])
    ->name('descarga.reportes.dosimetria')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores|dosimetria.dashboard|dosimetria.registros']);
Route::get('descarga-reportes-teleradiologia', [ReporteProduccion::class, 'reporteTeleRadiologia'])
    ->name('descarga.reportes.teleradiologia')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);
Route::get('descarga-reportes-ausentismo-citas', [ReporteProduccion::class, 'reporteAusentismoCitas'])
    ->name('descarga.reportes.ausentismo.citas')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);
Route::get('descarga-reportes-liquidacion', [LiquidacionController::class, 'exportar'])
    ->name('descarga.reportes.liquidacion')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores|liquidacion.exportar']);
Route::get('descarga-reportes-productividad', [LiquidacionController::class, 'exportarProductividad'])
    ->name('descarga.reportes.productividad')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores|liquidacion.exportar']);
Route::get('descarga-reportes-tat', [ReporteProduccion::class, 'reporteTAT'])
    ->name('descarga.reportes.tat')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);
Route::get('descarga-reportes-agentes', [ReporteProduccion::class, 'reporteAgentes'])
    ->name('descarga.reportes.agentes')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores|seguridad.backups.index']);
Route::get('descarga-reportes-consolidado-red', [ReporteProduccion::class, 'reporteConsolidadoRed'])
    ->name('descarga.reportes.consolidado.red')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);
Route::get('descarga-reportes-represados', [ReporteProduccion::class, 'reporteRepresados'])
    ->name('descarga.reportes.represados')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);
Route::get('descarga-reportes-citas-whatsapp', [ReporteProduccion::class, 'reporteCitasWhatsApp'])
    ->name('descarga.reportes.citas.whatsapp')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);
Route::get('descarga-reportes-almacenamiento', [ReporteProduccion::class, 'reporteAlmacenamiento'])
    ->name('descarga.reportes.almacenamiento')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores|seguridad.backups.index']);

/**
 * Configuración
 */
Route::get('/config-adminsalud-listar', fn () => view('layouts.app'))
    ->name('config.adminsalud.listar')
    ->middleware(['auth', 'permission:config.admin.salud.index']);

Route::get('/config-diagnosticos-listar', fn () => view('layouts.app'))
    ->name('config.diagnosticos.listar')
    ->middleware(['auth', 'permission:config.diagnosticos.index']);

Route::get('/config-prioridades-listar', fn () => view('layouts.app'))
    ->name('config.prioridades.listar')
    ->middleware(['auth', 'permission:config.prioridades.index']);

Route::get('/config-productos-listar', fn () => view('layouts.app'))
    ->name('config.productos.listar')
    ->middleware(['auth', 'permission:config.productos.index']);

Route::get('/config-sedes-listar', fn () => view('layouts.app'))
    ->name('config.sedes.listar')
    ->middleware(['auth', 'permission:config.sedes.index']);

Route::post('/config/sedes/updateWitchPlantillas', [ConfigSedeController::class, 'update'])
    ->name('config.sedes.update')
    ->middleware(['auth', 'permission:config.sedes.edit']);

Route::get('/config-plantillas-listar', fn () => view('layouts.app'))
    ->name('config.plantillas.listar')
    ->middleware(['auth', 'permission:config.plantillas.index']);

Route::get('/config-tipos-plantillas.listar', fn () => view('layouts.app'))
    ->name('config.tipos.plantillas.listar')
    ->middleware(['auth', 'permission:config.plantillas.index']);

Route::get('/config-servicio-producto', fn () => view('layouts.app'))
    ->name('config.servicio.producto')
    ->middleware(['auth', 'permission:config.productos.index']);

Route::get('/config-servicio-citas-listar', fn () => view('layouts.app'))
    ->name('config.servicio.citas.listar')
    ->middleware(['auth', 'permission:config.servicio.citas.index']);

Route::get('/config-tipos-documentos-listar', fn () => view('layouts.app'))
    ->name('config.tipos.documentos.listar')
    ->middleware(['auth', 'permission:config.tipo.documento.index']);

Route::get('/config-consentimiento-informado-listar', fn () => view('layouts.app'))
    ->name('config.consentimiento.informado.listar')
    ->middleware(['auth', 'permission:config.consentimiento_informado.index']);

Route::get('/config-whatsapp-listar', fn () => view('layouts.app'))
    ->name('config.whatsapp.listar')
    ->middleware(['auth', 'permission:config.whatsapp.saludo.index']);

Route::get('/config-cita-rechazo-motivos', fn () => view('layouts.app'))
    ->name('config.cita.rechazo.motivos')
    ->middleware(['auth', 'permission:citas.index']);

/**
 * Configuración Helpdesk
 */
Route::get('/config-helpdesk-categorias-listar', fn () => view('layouts.app'))
    ->name('config.helpdesk.categorias.listar')
    ->middleware(['auth', 'permission:helpdesk.config.categorias.index']);

Route::get('/config-helpdesk-prioridades-listar', fn () => view('layouts.app'))
    ->name('config.helpdesk.prioridades.listar')
    ->middleware(['auth', 'permission:helpdesk.config.prioridades.index']);

Route::get('/config-helpdesk-estados-listar', fn () => view('layouts.app'))
    ->name('config.helpdesk.estados.listar')
    ->middleware(['auth', 'permission:helpdesk.config.estados.index']);

/**
 * Seguridad
 */
Route::get('/seguridad.usuarios.listar', fn () => view('layouts.app'))
    ->name('seguridad.usuarios.listar')
    ->middleware(['auth', 'permission:seguridad.usuarios.index']);

Route::get('/seguridad.roles.listar', fn () => view('layouts.app'))
    ->name('seguridad.roles.listar')
    ->middleware(['auth', 'permission:seguridad.roles.index']);

Route::get('/seguridad.perfil', fn () => view('layouts.app'))
    ->name('seguridad.perfil')
    ->middleware(['auth']);

Route::get('/seguridad-auditoria', fn () => view('layouts.app'))
    ->name('seguridad.auditoria')
    ->middleware(['auth', 'permission:seguridad.auditoria.index']);

Route::get('/seguridad-backups', fn () => view('layouts.app'))
    ->name('seguridad.backups')
    ->middleware(['auth', 'permission:seguridad.backups.index']);

Route::get('/seguridad-tokens', fn () => view('layouts.app'))
    ->name('seguridad.tokens')
    ->middleware(['auth', 'permission:seguridad.tokens.index']);

/**
 * Interface SAHI
 */

/**
 * Interface SAHI
 */
Route::get('/importar-dicom', [ImportarDicom::class, 'importarDicom'])->middleware(['auth']);

/**
 * Agendamiento
 */
Route::get('/agenda', fn () => view('layouts.app'))
    ->name('agendamiento.index')
    ->middleware(['auth', 'permission:agendamiento.agendamiento.index|agenda.index']);
Route::get('/agenda-disponibilidad', fn () => view('layouts.app'))
    ->name('agenda.disponibilidad.index')
    ->middleware(['auth', 'permission:agendamiento.disponibilidad.index|agenda.index']);

/**
 * Citas
 */
Route::get('/citas', fn () => view('layouts.app'))
    ->name('citas.index')
    ->middleware(['auth', 'permission:citas.index']);

Route::get('/citas/gestion', fn () => view('layouts.app'))
    ->name('citas.gestion')
    ->middleware(['auth', 'permission:citas.index']);

Route::get('/citas/revision', fn () => view('layouts.app'))
    ->name('citas.revision')
    ->middleware(['auth', 'permission:citas.index']);

Route::get('/citas/recomendaciones', fn () => view('layouts.app'))
    ->name('citas.recomendaciones')
    ->middleware(['auth', 'permission:citas.index']);

Route::get('/citas/media/{filename}', [AgendaCitaController::class, 'descargarMedia'])
    ->name('citas.media')
    ->middleware(['auth', 'permission:citas.index']);

/**
 * Contratación
 */
Route::get('/contratos', fn () => view('layouts.app'))
    ->name('contratos.listar')
    ->middleware(['auth', 'permission:contratos.index']);

/**
 * Liquidación
 */
Route::get('/liquidacion', fn () => view('layouts.app'))
    ->name('liquidacion.ver')
    ->middleware(['auth', 'permission:liquidacion.index']);

/*
 * Productividad médica (corte de mes)
 */
Route::get('/productividad', fn () => view('layouts.app'))
    ->name('productividad.ver')
    ->middleware(['auth', 'permission:liquidacion.index']);

/**
 * Verificación Pública de Firma Digital de Informes Médicos (QR / Token)
 */
Route::get('/validar-informe/{token}', [VerificacionInformeController::class, 'validar'])
    ->name('informe.validar');

require __DIR__.'/files.php';

/**
 * Entrada a la SPA (Vue Router) y Fallback global
 */
Route::get('/spa/{any?}', function () {
    return view('layouts.app');
})->middleware(['auth', 'force-password-change'])->where('any', '.*')->name('spa.entrypoint');

Route::fallback(function () {
    if (auth()->check()) {
        return view('layouts.app');
    }

    return redirect()->route('login');
})->middleware(['auth', 'force-password-change']);
