<?php

use App\Http\Controllers\Agenda\AgendaDisponibilidaController;
use App\Http\Controllers\Agenda\AgendaDisponibilidaDetalleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Seguridad\UserController;
use App\Http\Controllers\Seguridad\RolController;

use App\Http\Controllers\Configuracion\ConfigAdminSaludController;
use App\Http\Controllers\Configuracion\ConfigProductoController;
use App\Http\Controllers\Configuracion\ConfigDiagnosticoController;
use App\Http\Controllers\Configuracion\ConfigModulosController;
use App\Http\Controllers\Configuracion\ConfigPlantillaController;
use App\Http\Controllers\Configuracion\ConfigPlantillaTipoController;
use App\Http\Controllers\Configuracion\ConfigPrioridadController;
use App\Http\Controllers\Configuracion\ConfigSedeController;
use App\Http\Controllers\Configuracion\ConfigTipoDocumentoController;
use App\Http\Controllers\Configuracion\ConsentimientoInformadoController;
use App\Http\Controllers\Dcm4chee\StudyController;
use App\Http\Controllers\Ecografias\EcografiaController;
use App\Http\Controllers\Ecografias\EcografiaProductoController;
use App\Http\Controllers\Ecografias\ecografiaLecturaController;
use App\Http\Controllers\Estudios\EstudioAudioController;
use App\Http\Controllers\Estudios\EstudioController;
use App\Http\Controllers\Estudios\EstudioProductoController;
use App\Http\Controllers\Estudios\EstudioDiagnosticoController;
use App\Http\Controllers\Estudios\EstudioNotificacionController;
use App\Http\Controllers\Estudios\EstudioSoporteHCController;
use App\Http\Controllers\Estudios\PlantillaEstudioController;
use App\Http\Controllers\Importar\ImportarDicom;
use App\Http\Controllers\Interface\SAHIController;
use App\Http\Controllers\Interface\SAHIEcografiasController;
use App\Http\Controllers\Interface\SAHIEcografiasEnviarController;
use App\Http\Controllers\Reportes\ReporteProduccion;

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

Route::view('dashboard', 'modulos.dashboard.dashboard')->name('dashboard')->middleware('auth');

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
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir|estudio.leerestudio|estudio.leerEstudio']);

Route::view('/estudios.leer.estudio.listar', 'modulos.estudios.leer-estudio')
    ->name('estudios.leer.estudio.listar')
    ->middleware(['auth', 'permission:estudio.leerestudio|estudio.leerEstudio']);

Route::view('/estudios.transcribir.listar', 'modulos.estudios.transcribir-estudio')
    ->name('estudios.transcribir.listar')
    ->middleware(['auth', 'permission:estudio.transcribir']);
Route::view('/estudios.tele-radiologia', 'modulos.estudios.tele-radiologia')
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

Route::view('/config-plantillas-listar', 'modulos.configuracion.config-plantillas')
    ->name('config.plantillas.listar')
    ->middleware(['auth', 'permission:config.plantillas.index']);

Route::view('/config-tipos-plantillas.listar', 'modulos.configuracion.config-tipos-plantillas')
    ->name('config.tipos.plantillas.listar')
    ->middleware(['auth', 'permission:config.plantillas.index']);

Route::view('/config-tipos-documentos-listar', 'modulos.configuracion.config-tipos-documentos')
    ->name('config.tipos.documentos.listar')
    ->middleware(['auth', 'permission:config.tipo.documento.index']);

Route::view('/config-consentimiento-informado-listar', 'modulos.configuracion.config-consentimiento-informado')
    ->name('config.consentimiento.informado.listar')
    ->middleware(['auth', 'permission:config.consentimiento_informado.index']);

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



/* Route::get('/{optional?}', function () {
    return view('layouts.app');
})->name('basepath')->where('optional', '.*');
 */