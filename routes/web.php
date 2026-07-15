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
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Configuracion\ConfigSedeController;

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

Route::view('dashboard', 'modulos.dashboard.dashboard')->name('dashboard')->middleware('auth');

/**
 * Helpdesk
 */
Route::view('/helpdesk-tickets', 'modulos.help-desk.tickets')
    ->name('helpdesk.tickets.listar')
    ->middleware(['auth', 'permission:helpdesk.tickets.index']);

Route::view('/helpdesk-tickets/crear', 'modulos.help-desk.crear-ticket')
    ->name('helpdesk.tickets.crear')
    ->middleware(['auth', 'permission:helpdesk.tickets.create']);

Route::get('/helpdesk-tickets/{ticket_id}', function (string $ticket_id) {
    return view('modulos.help-desk.detalle-ticket');
})
    ->name('helpdesk.tickets.detalle')
    ->middleware(['auth', 'permission:helpdesk.tickets.show']);

Route::view('/helpdesk-reportes', 'modulos.help-desk.reportes')
    ->name('helpdesk.reportes')
    ->middleware(['auth', 'permission:helpdesk.reportes.index']);

Route::view('/helpdesk-notificaciones', 'modulos.help-desk.notificaciones')
    ->name('helpdesk.notificaciones')
    ->middleware(['auth', 'permission:helpdesk.notificaciones.index']);

/**
 * Estudios radiologicos
 */
Route::view('/estudios.asignar.listar', 'modulos.estudios.asignar-estudio')
    ->name('estudios.asignar.listar')
    ->middleware(['auth', 'permission:estudio.asignar']);
Route::view('/estudios.asignados.listar', 'modulos.estudios.estudio-asignados')
    ->name('estudios.asignados.listar')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir']);
Route::view('/estudios.notificar.index', 'modulos.estudios.notificar-estudio')
    ->name('estudios.notificar.index')
    ->middleware(['auth', 'permission:estudios.notificar.index']);

Route::get('/descarga-soportes-hc/{nomArchivoEncriptadote}', [EstudioSoporteHCController::class, 'descargarSoporte'])
    ->name('study.descarga.soporte.hc')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir|estudio.leerestudio|estudio.leerEstudio']);
Route::get('/descargar.pdf.lectura/{nomArchivo}', [EstudioController::class, 'descargarPdfLectura'])
    ->name('descargar.pdf.lectura')
    ->middleware('auth:sanctum');

// Audios (fallback sin depender de symlink `public/audios`)
Route::get('/audios/{filename}', [EstudioAudioController::class, 'stream'])
    ->name('audios.stream')
    ->middleware(['auth', 'permission:estudio.transcribir|estudio.leerestudio|estudio.leerEstudio'])
    ->where('filename', 'audio_[A-Z0-9]{10,}\.mp3');

Route::view('/estudios.leer.estudio.listar', 'modulos.estudios.leer-estudio')
    ->name('estudios.leer.estudio.listar')
    ->middleware(['auth', 'permission:estudio.leerestudio|estudio.leerEstudio']);

Route::view('/estudios.transcribir.listar', 'modulos.estudios.transcribir-estudio')
    ->name('estudios.transcribir.listar')
    ->middleware(['auth', 'permission:estudio.transcribir']);
Route::view('/estudios.tele-radiologia', 'modulos.tele-radiologia.tele-radiologia')
    ->name('estudios.teleradiologis.listar')
    ->middleware(['auth', 'permission:tele.ragiologia.index|tele.ragiologia.enviar|tele.ragiologia.transcribir|estudio.asignar|estudio.transcribir']);

/**
 * Otras Plataformas
 */
Route::view('/otras-plataformas.listar-estudio-enviar', 'modulos.otras-plataformas.listar-estudio-enviar')
    ->name('otras.plataformas.listar.estudio.enviar')
    ->middleware(['auth', 'permission:otrasPlataformas.index|otrasPlataformas.enviar|otrasPlataformas.trasbcribir']);

/**
 * Tele Radiologia
 */
Route::view('/tele-radiologia', 'modulos.tele-radiologia.tele-radiologia')
    ->name('teleradiologia.index')
    ->middleware(['auth', 'permission:tele.ragiologia.index|tele.ragiologia.enviar|estudio.asignar']);
Route::view('/tele-radiologia-transcribir', 'modulos.tele-radiologia.tele-radiologia-trasncribir')
    ->name('teleradiologia.transcribir')
    ->middleware(['auth', 'permission:tele.ragiologia.transcribir|estudio.transcribir']);

/*
 * Ecografias
 */
Route::view('/ecografias.admisiones', 'modulos.ecografias.admisiones')
    ->name('ecografias.admisiones')
    ->middleware(['auth', 'permission:ecografias.admision']);
Route::view('/ecografias.admisionadas', 'modulos.ecografias.adminisionadas')
    ->name('ecografias.adminisionadas')
    ->middleware(['auth', 'permission:ecografias.index|ecografias.admision']);
Route::view('/ecografias-pendientes-lecturas', 'modulos.ecografias.lectura')
    ->name('ecografias.pendientes.lecturas')
    ->middleware(['auth', 'permission:ecografias.leer']);

Route::view('/ecografias-consentimientos', 'modulos.ecografias.consentimientos')
    ->name('ecografias.consentimientos')
    ->middleware(['auth', 'permission:ecografias.consentimientos.index']);

Route::view('/ecografias-consentimientos-plantillas', 'modulos.ecografias.consentimientos-plantillas')
    ->name('ecografias.consentimientos.plantillas')
    ->middleware(['auth', 'permission:ecografias.consentimientos.plantillas.index']);

// Alias en Configuración (menú Parametrización)
Route::view('/config-consentimientos', 'modulos.ecografias.consentimientos')
    ->name('config.consentimientos.index')
    ->middleware(['auth', 'permission:ecografias.consentimientos.index']);

Route::view('/config-consentimientos-plantillas', 'modulos.ecografias.consentimientos-plantillas')
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
Route::view('reportes-produccion', 'modulos.reportes.reportes-produccion')
    ->name('reportes.produccion')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);
Route::get('descarga-reportes-produccion', [ReporteProduccion::class, 'reporteProduccionDetallado'])
    ->name('descarga.reportes.produccion')
    ->middleware(['auth', 'permission:reportes.detallados|reportes.indicadores']);

/**
 * Configuración
 */
Route::view('/config-adminsalud-listar', 'modulos.configuracion.config-admin-salud')
    ->name('config.adminsalud.listar')
    ->middleware(['auth', 'permission:config.admin.salud.index']);

Route::view('/config-diagnosticos-listar', 'modulos.configuracion.config-diagnosticos')
    ->name('config.diagnosticos.listar')
    ->middleware(['auth', 'permission:config.diagnosticos.index']);

Route::view('/config-prioridades-listar', 'modulos.configuracion.config-prioridades')
    ->name('config.prioridades.listar')
    ->middleware(['auth', 'permission:config.prioridades.index']);

Route::view('/config-productos-listar', 'modulos.configuracion.config-productos')
    ->name('config.productos.listar')
    ->middleware(['auth', 'permission:config.productos.index']);

Route::view('/config-sedes-listar', 'modulos.configuracion.config-sedes')
    ->name('config.sedes.listar')
    ->middleware(['auth', 'permission:config.sedes.index']);

Route::post('/config/sedes/updateWitchPlantillas', [ConfigSedeController::class, 'update'])
    ->name('config.sedes.update')
    ->middleware(['auth', 'permission:config.sedes.edit']);

Route::view('/config-plantillas-listar', 'modulos.configuracion.config-plantillas')
    ->name('config.plantillas.listar')
    ->middleware(['auth', 'permission:config.plantillas.index']);

Route::view('/config-tipos-plantillas.listar', 'modulos.configuracion.config-tipos-plantillas')
    ->name('config.tipos.plantillas.listar')
    ->middleware(['auth', 'permission:config.plantillas.index']);

Route::view('/config-servicio-producto', 'modulos.configuracion.config-servicio-producto')
    ->name('config.servicio.producto')
    ->middleware(['auth', 'permission:config.productos.index']);

Route::view('/config-servicio-citas-listar', 'modulos.configuracion.config-servicio-citas')
    ->name('config.servicio.citas.listar')
    ->middleware(['auth', 'permission:config.servicio.citas.index']);

Route::view('/config-tipos-documentos-listar', 'modulos.configuracion.config-tipos-documentos')
    ->name('config.tipos.documentos.listar')
    ->middleware(['auth', 'permission:config.tipo.documento.index']);

Route::view('/config-consentimiento-informado-listar', 'modulos.configuracion.config-consentimiento-informado')
    ->name('config.consentimiento.informado.listar')
    ->middleware(['auth', 'permission:config.consentimiento_informado.index']);

Route::view('/config-whatsapp-listar', 'modulos.configuracion.config-whatsapp')
    ->name('config.whatsapp.listar')
    ->middleware(['auth', 'permission:config.whatsapp.saludo.index']);

Route::view('/config-cita-rechazo-motivos', 'modulos.configuracion.config-cita-rechazo-motivo')
    ->name('config.cita.rechazo.motivos')
    ->middleware(['auth', 'permission:citas.index']);

/**
 * Configuración Helpdesk
 */
Route::view('/config-helpdesk-categorias-listar', 'modulos.configuracion.config-helpdesk-categorias')
    ->name('config.helpdesk.categorias.listar')
    ->middleware(['auth', 'permission:helpdesk.config.categorias.index']);

Route::view('/config-helpdesk-prioridades-listar', 'modulos.configuracion.config-helpdesk-prioridades')
    ->name('config.helpdesk.prioridades.listar')
    ->middleware(['auth', 'permission:helpdesk.config.prioridades.index']);

Route::view('/config-helpdesk-estados-listar', 'modulos.configuracion.config-helpdesk-estados')
    ->name('config.helpdesk.estados.listar')
    ->middleware(['auth', 'permission:helpdesk.config.estados.index']);

/**
 * Seguridad
 */
Route::view('/seguridad.usuarios.listar', 'modulos.seguridad.usuarios')
    ->name('seguridad.usuarios.listar')
    ->middleware(['auth', 'permission:seguridad.usuarios.index']);

Route::view('/seguridad.roles.listar', 'modulos.seguridad.roles')
    ->name('seguridad.roles.listar')
    ->middleware(['auth', 'permission:seguridad.roles.index']);

Route::view('/seguridad.perfil', 'modulos.seguridad.perfil')
    ->name('seguridad.perfil')
    ->middleware(['auth']);

Route::view('/seguridad-auditoria', 'modulos.seguridad.auditoria')
    ->name('seguridad.auditoria')
    ->middleware(['auth', 'permission:seguridad.auditoria.index']);

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
Route::view('/agenda', 'modulos.agenda.index')
    ->name('agendamiento.index')
    ->middleware(['auth', 'permission:agendamiento.agendamiento.index|agenda.index']);
Route::view('/agenda-disponibilidad', 'modulos.agenda.disponibilidad')
    ->name('agenda.disponibilidad.index')
    ->middleware(['auth', 'permission:agendamiento.disponibilidad.index|agenda.index']);

/**
 * Citas
 */
Route::view('/citas', 'modulos.citas.index')
    ->name('citas.index')
    ->middleware(['auth', 'permission:citas.index']);

Route::view('/citas/gestion', 'modulos.citas.gestion')
    ->name('citas.gestion')
    ->middleware(['auth', 'permission:citas.index']);

Route::view('/citas/revision', 'modulos.citas.revision')
    ->name('citas.revision')
    ->middleware(['auth', 'permission:citas.index']);

Route::view('/citas/recomendaciones', 'modulos.citas.recomendaciones')
    ->name('citas.recomendaciones')
    ->middleware(['auth', 'permission:citas.index']);

Route::get('/citas/media/{filename}', [AgendaCitaController::class, 'descargarMedia'])
    ->name('citas.media')
    ->middleware(['auth', 'permission:citas.index']);

require __DIR__.'/files.php';

/* Route::get('/{optional?}', function () {
    return view('layouts.app');
})->name('basepath')->where('optional', '.*');
 */
