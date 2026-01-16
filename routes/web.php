<?php

use App\Http\Controllers\Agenda\AgendaController;
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
use App\Http\Controllers\Dashboard\DashBoardController;
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
use App\Http\Controllers\Interface\TeleRadiologiaController;
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

Auth::routes();

Route::post('/autenticacion/login', [LoginController::class, 'login']);
Route::post('/autenticacion/logout', [LoginController::class, 'logout']);
Route::view('dashboard', 'modulos.dashboard.dashboard')->name('dashboard')->middleware('auth');
Route::get('reportes.dashboard', [DashBoardController::class, 'Dashboard'])->name('reportes.dashboard')->middleware('auth');

/**
 * Estudios radiologicos
 */
Route::view('/estudios.asignar.listar', 'modulos.estudios.asignar-estudio')->name('estudios.asignar.listar');
Route::view('/estudios.asignados.listar', 'modulos.estudios.estudio-asignados')->name('estudios.asignados.listar');
Route::post('/study.listarEstudios', [StudyController::class, 'listarEstudios'])->name('study.listarEstudios');
Route::view('/estudios.notificar.index', 'modulos.estudios.notificar-estudio')->name('estudios.notificar.index');

Route::resource('/estudios', EstudioController::class)->names('estudios')->except(['create', 'show']);
Route::get('/study-listar-diagnostico-por-estudio', [EstudioController::class, 'listarDiagnosticoPorEstudio'])->name('study.listar.diagnostico.por.estudio');
Route::resource('/estudios-productos', EstudioProductoController::class)->names('estudios.productos');
Route::resource('/estudios-diagnosticos', EstudioDiagnosticoController::class)->names('estudios.diagnosticos');
Route::get('/listar-soportes-hc-estudio', [EstudioSoporteHCController::class, 'listarSoportesHistoriaClinicaPorPorEstudio'])->name('study.listar.soporte.hc.estudio');
Route::get('/descarga-soportes-hc/{nomArchivoEncriptadote}', [EstudioSoporteHCController::class, 'descargarSoporte'])->name('study.descarga.soporte.hc');
Route::post('/listar.estudios.notificaciones', [EstudioNotificacionController::class, 'listarEstudios'])->name('listar.estudios.notificaciones');
Route::get('/descargar.pdf.lectura/{nomArchivo}', [EstudioController::class, 'descargarPdfLectura'])->name('descargar.pdf.lectura');
Route::post('/notificar.email', [EstudioController::class, 'notificarEmail'])->name('notificar.email');
Route::post('/marcar-visto/{id}', [EstudioController::class, 'marcarEstudioVisto'])->name('estudios.marcarEstudioVisto');

Route::post('/edit-word', [PlantillaEstudioController::class, 'editWord']);

Route::view('/estudios.leer.estudio.listar', 'modulos.estudios.leer-estudio')->name('estudios.leer.estudio.listar');
Route::post('/estudio-leerEstudio', [EstudioController::class, 'leerEstudio'])->name('estudio.leerEstudio');

Route::post('/upload-audio', [EstudioAudioController::class, 'uploadAudio']);
Route::get('/listar-audios-estudio/{estudio_id}', [EstudioAudioController::class, 'listarAudiosPorEstudio'])->name('listarAudiosPorEstudio');

Route::view('/estudios.transcribir.listar', 'modulos.estudios.transcribir-estudio')->name('estudios.transcribir.listar');
Route::view('/estudios.otras-plataformas', 'modulos.estudios.otras-plataformas')->name('estudios.otrasPlataformas.listar');
Route::view('/estudios.tele-radiologia', 'modulos.estudios.tele-radiologia')->name('estudios.teleradiologis.listar');

Route::post('/estudio-listarPendientesTrascribir', [EstudioController::class, 'listarPendientesTrascribir'])->name('estudio.listarPendientesTrascribir');
Route::post('/estudio-listarEstudiosAsignados', [EstudioController::class, 'listarEstudiosAsignados'])->name('estudio.listarEstudiosAsignados');
Route::post('/estudio-guardarTranscripcion', [EstudioController::class, 'guardarTranscripcion'])->name('estudio.guardarTranscripcion');
Route::post('/estudio-anular-asignacion', [EstudioController::class, 'anularAsignacion'])->name('estudio.anularAsignacion');
Route::post('/estudio-listarPendientesMedico', [EstudioController::class, 'listarPendientesMedico'])->name('estudio.listarPendientesMedico');
Route::get('/estudio-listarPendientesPorLeerTranscribir', [EstudioController::class, 'penditesPorLeerTranscribir'])->name('estudio.listarPendientesPorLeerTranscribir');
Route::get('/estudio-buscar-por-numdocu/{numDocu}', [EstudioController::class, 'buscarEstudioPorNumDocu'])->name('estudio.buscarEstudioPorNumDocu');

/**
 * Ecografias
 */
Route::view('/ecografias.admisiones', 'modulos.ecografias.admisiones')->name('ecografias.admisiones');
Route::view('/ecografias.admisionadas', 'modulos.ecografias.adminisionadas')->name('ecografias.adminisionadas');
Route::view('/ecografias-pendientes-lecturas', 'modulos.ecografias.lectura')->name('ecografias.pendientes.lecturas');

Route::resource('ecografias', EcografiaController::class)->names('ecografias')->except(['create', 'edit']);
Route::post('ecografias-filtar', [EcografiaController::class, 'filtarEcografias'])->name('ecografias.filtar');
Route::post('ecografia-anular/{id}', [EcografiaController::class, 'anularEcografia'])->name('ecografias.anular');

Route::put('ecografia-lectura/{ecografia_id}', [ecografiaLecturaController::class, 'leer'])->name('ecografias.lectura');
Route::post('ecografia-generar-reporte/{ecografia_id}', [EcografiaController::class, 'generarReporte'])->name('ecografias.generar.reporte');

Route::post('/ecografia-listarPendientesPorLectura/{idMedico}', [ecografiaLecturaController::class, 'listarPendientesPorLectura'])->name('ecografia.listarPendientesPorLectura');

Route::resource('ecografias-productos', EcografiaProductoController::class)->names('ecografias.productos')->except(['create', 'edit']);
Route::post('/ecografia-guardarLectura', [ecografiaLecturaController::class, 'guardarLectura'])->name('ecografia.guardarLectura');

Route::post('/interface-sahi-ecografias/listar/productos/atencion', [SAHIEcografiasController::class, 'listarProductosAtencion'])->name('sahi.listar.productos.atencion');

/**
 * citas
 */
Route::view('/agenda', 'modulos.agenda.agenda-gestionar')->name('agenda');
Route::resource('/agenda-gestionar', AgendaController::class)->names('agenda.gestionar');

/**
 * Reportes
 */
Route::view('reportes-produccion', 'modulos.reportes.reportes-produccion')->name('reportes.produccion')->middleware('auth');
Route::get('descarga-reportes-produccion', [ReporteProduccion::class, 'reporteProduccionDetallado'])->name('descarga.reportes.produccion')->middleware('auth');

/**
 * Configuración
 */
Route::view('/config-adminsalud-listar', 'modulos.configuracion.config-admin-salud')->name('config.adminsalud.listar');
Route::resource('/config-admin-salud', ConfigAdminSaludController::class)->names('config.admin.salud')->except(['create', 'show']);

Route::view('/config-diagnosticos-listar', 'modulos.configuracion.config-diagnosticos')->name('config.diagnosticos.listar');
Route::resource('/config-diagnosticos', ConfigDiagnosticoController::class)->names('config.diagnosticos')->except(['create', 'show']);

Route::view('/config-prioridades-listar', 'modulos.configuracion.config-prioridades')->name('config.prioridades.listar');
Route::resource('/config-prioridades', ConfigPrioridadController::class)->names('config.prioridades')->except(['create', 'show']);

Route::view('/config-productos-listar', 'modulos.configuracion.config-productos')->name('config.productos.listar');
Route::resource('/config-productos', ConfigProductoController::class)->names('config.productos')->except(['create']);

Route::view('/config-sedes-listar', 'modulos.configuracion.config-sedes')->name('config.sedes.listar');
Route::post('/config/sedes/updateWitchPlantillas', [ConfigSedeController::class, 'update'])->name('config.sedes.update');
Route::resource('/config-sedes', ConfigSedeController::class)->names('config.sedes')->except(['create', 'show', 'update']);
Route::get('/config-sedes/{configsede}/buscarPorId', [ConfigSedeController::class, 'buscarPorId'])->name('config.sedes.buscarPorId');
Route::get('/config-sedes/listar/sedes/activas', [ConfigSedeController::class, 'listarSedesActivas'])->name('config.sedes.listarSedesActivas');

Route::view('/config-plantillas-listar', 'modulos.configuracion.config-plantillas')->name('config.plantillas.listar');
Route::resource('/config-plantillas', ConfigPlantillaController::class)->names('config.plantillas')->except(['create', 'edit']);
Route::get('/config-plantillas/listar/activos/{tipo_id}', [ConfigPlantillaController::class, 'listarActivos'])->name('config.plantillas.listar.activos');

Route::view('/config-tipos-plantillas.listar', 'modulos.configuracion.config-tipos-plantillas')->name('config.tipos.plantillas.listar');
Route::resource('/config-tipos-plantillas', ConfigPlantillaTipoController::class)->names('config.tipos.plantillas')->except(['create', 'edit']);
Route::get('/config-tipos-plantillas/listar/activos', [ConfigPlantillaTipoController::class, 'listarActivos'])->name('config.tipos.plantillas.listar.activos');

Route::get('confing-modulos-listar', [ConfigModulosController::class, 'listarModulos'])->name('confing.modulos.listar');

Route::view('/config-tipos-documentos-listar', 'modulos.configuracion.config-tipos-documentos')->name('config.tipos.documentos.listar');
Route::resource('/config-tipos-documentos', ConfigTipoDocumentoController::class)->names('config.tipos.documentos')->except(['create', 'edit']);
Route::get('/config-tipos-documentos/listar/activos', [ConfigTipoDocumentoController::class, 'listarActivos'])->name('config.tipos.documentos.listar.activos');

Route::view('/config-consentimiento-informado-listar', 'modulos.configuracion.config-consentimiento-informado')->name('config.consentimiento.informado.listar');

/**
 * Seguridad
 */
Route::view('/seguridad.usuarios.listar', 'modulos.seguridad.usuarios')->name('seguridad.usuarios.listar');
Route::resource('/seguridad-usuarios', UserController::class)->names('seguridad.usuarios')->except('update');
Route::post('/seguridad/usuarios/update', [UserController::class, 'update'])->name('seguridad.update');
Route::put('/seguridad-actualizar-perfil/{idUsua}', [UserController::class, 'actualizarPerfil'])->name('seguridad.actualizar.perfil');
Route::put('/seguridad-usuarios/estado/{idUsua}', [UserController::class, 'estadoUsuario'])->name('seguridad.usuario.estado');
Route::get('/user.listarUsuarios/{tipo_user}/{modulo}', [UserController::class, 'listarUsuarios'])->name('user.listarUsuarios');

Route::view('/seguridad.roles.listar', 'modulos.seguridad.roles')->name('seguridad.roles.listar');
Route::resource('/seguridad-roles', RolController::class)->names('seguridad.roles');

Route::get('/usuario-por-modulo/{modulo_id}', [UserController::class, 'usuariosPorModulo'])->name('usuarios.por.modulo');

Route::view('/seguridad.perfil', 'modulos.seguridad.perfil')->name('seguridad.perfil');
Route::get('/seguridad-listarPermisosUsuario/{id}', [UserController::class, 'listarPermisosUsuario'])->name('seguridad.listarPermisosUsuario');

/**
 * Interface SAHI
 */
Route::get('/interface-sahi-listar-atenciones/{numDocu}', [SAHIController::class, 'listarAtenciones'])->name('sahi.listar.atenciones');
Route::get('/interface-sahi-listar-listarProductosDiagnosticos/{idAtencion}', [SAHIController::class, 'listarProductosDiagnosticos'])->name('sahi.listar.listarProductosDiagnosticos');
Route::post('/interface-sahi/listar/productos/atencion', [SAHIController::class, 'listarProductosAtencion'])->name('sahi.listar.productos.atencion');

/**
 * Interface SAHI
 */
Route::get('/importar-dicom', [ImportarDicom::class, 'importarDicom']);

/**
 * Interface Tele Radiologia
 */


/* Route::get('/{optional?}', function () {
    return view('layouts.app');
})->name('basepath')->where('optional', '.*');
 */