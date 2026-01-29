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
use App\Http\Controllers\Interface\SAHIEcografiasEnviarController;
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

Route::post('/autenticacion/login', [LoginController::class, 'login'])->middleware(['guest', 'throttle:login']);
Route::post('/autenticacion/logout', [LoginController::class, 'logout'])->middleware(['auth']);
Route::view('dashboard', 'modulos.dashboard.dashboard')->name('dashboard')->middleware('auth');
Route::get('reportes.dashboard', [DashBoardController::class, 'Dashboard'])->name('reportes.dashboard')->middleware('auth');

/**
 * Estudios radiologicos
 */
Route::view('/estudios.asignar.listar', 'modulos.estudios.asignar-estudio')
    ->name('estudios.asignar.listar')
    ->middleware(['auth', 'permission:estudio.asignar']);
Route::view('/estudios.asignados.listar', 'modulos.estudios.estudio-asignados')
    ->name('estudios.asignados.listar')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir']);
Route::post('/study.listarEstudios', [StudyController::class, 'listarEstudios'])
    ->name('study.listarEstudios')
    ->middleware(['auth', 'permission:estudio.asignar']);
Route::view('/estudios.notificar.index', 'modulos.estudios.notificar-estudio')
    ->name('estudios.notificar.index')
    ->middleware(['auth', 'permission:estudios.notificar.index']);

Route::resource('/estudios', EstudioController::class)
    ->names('estudios')
    ->except(['create', 'show'])
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir|estudio.leerEstudio']);
Route::get('/study-listar-diagnostico-por-estudio', [EstudioController::class, 'listarDiagnosticoPorEstudio'])
    ->name('study.listar.diagnostico.por.estudio')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir|estudio.leerEstudio']);
Route::resource('/estudios-productos', EstudioProductoController::class)->names('estudios.productos');
Route::resource('/estudios-diagnosticos', EstudioDiagnosticoController::class)->names('estudios.diagnosticos');
Route::get('/listar-soportes-hc-estudio', [EstudioSoporteHCController::class, 'listarSoportesHistoriaClinicaPorPorEstudio'])
    ->name('study.listar.soporte.hc.estudio')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir|estudio.leerEstudio']);
Route::get('/descarga-soportes-hc/{nomArchivoEncriptadote}', [EstudioSoporteHCController::class, 'descargarSoporte'])
    ->name('study.descarga.soporte.hc')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir|estudio.leerEstudio']);
Route::post('/listar.estudios.notificaciones', [EstudioNotificacionController::class, 'listarEstudios'])
    ->name('listar.estudios.notificaciones')
    ->middleware(['auth', 'permission:estudios.notificar.index']);
Route::get('/descargar.pdf.lectura/{nomArchivo}', [EstudioController::class, 'descargarPdfLectura'])
    ->name('descargar.pdf.lectura')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir|estudio.leerEstudio']);

Route::post('/notificar.email', [EstudioController::class, 'notificarEmail'])
    ->name('notificar.email')
    ->middleware(['auth', 'permission:estudios.notificar.index|estudio.transcribir']);
Route::post('/enviar-portal-pacientes', [EstudioController::class, 'enviarPortalPacientes'])
    ->name('enviar.portal.pacientes')
    ->middleware(['auth', 'permission:estudios.notificar.index|estudio.transcribir']);
Route::post('/marcar-visto/{id}', [EstudioController::class, 'marcarEstudioVisto'])
    ->name('estudios.marcarEstudioVisto')
    ->middleware(['auth', 'permission:estudio.leerEstudio|estudio.transcribir']);

Route::post('/edit-word', [PlantillaEstudioController::class, 'editWord'])
    ->middleware(['auth', 'permission:estudio.transcribir|estudio.leerEstudio']);

Route::view('/estudios.leer.estudio.listar', 'modulos.estudios.leer-estudio')
    ->name('estudios.leer.estudio.listar')
    ->middleware(['auth', 'permission:estudio.leerEstudio']);
Route::post('/estudio-leerEstudio', [EstudioController::class, 'leerEstudio'])
    ->name('estudio.leerEstudio')
    ->middleware(['auth', 'permission:estudio.leerEstudio']);

Route::post('/upload-audio', [EstudioAudioController::class, 'uploadAudio'])
    ->middleware(['auth', 'permission:estudio.transcribir|estudio.leerEstudio']);
Route::get('/listar-audios-estudio/{estudio_id}', [EstudioAudioController::class, 'listarAudiosPorEstudio'])
    ->name('listarAudiosPorEstudio')
    ->middleware(['auth', 'permission:estudio.transcribir|estudio.leerEstudio']);

Route::view('/estudios.transcribir.listar', 'modulos.estudios.transcribir-estudio')
    ->name('estudios.transcribir.listar')
    ->middleware(['auth', 'permission:estudio.transcribir']);
Route::view('/estudios.tele-radiologia', 'modulos.estudios.tele-radiologia')
    ->name('estudios.teleradiologis.listar')
    ->middleware(['auth']);

Route::post('/estudio-listarPendientesTrascribir', [EstudioController::class, 'listarPendientesTrascribir'])
    ->name('estudio.listarPendientesTrascribir')
    ->middleware(['auth', 'permission:estudio.transcribir']);
Route::post('/estudio-listarEstudiosAsignados', [EstudioController::class, 'listarEstudiosAsignados'])
    ->name('estudio.listarEstudiosAsignados')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir']);
Route::post('/estudio-guardarTranscripcion', [EstudioController::class, 'guardarTranscripcion'])
    ->name('estudio.guardarTranscripcion')
    ->middleware(['auth', 'permission:estudio.transcribir']);
Route::post('/estudio-anular-asignacion', [EstudioController::class, 'anularAsignacion'])
    ->name('estudio.anularAsignacion')
    ->middleware(['auth', 'permission:estudio.asignar']);
Route::post('/estudio-listarPendientesMedico', [EstudioController::class, 'listarPendientesMedico'])
    ->name('estudio.listarPendientesMedico')
    ->middleware(['auth', 'permission:estudio.leerEstudio']);
Route::get('/estudio-listarPendientesPorLeerTranscribir', [EstudioController::class, 'penditesPorLeerTranscribir'])
    ->name('estudio.listarPendientesPorLeerTranscribir')
    ->middleware(['auth', 'permission:estudio.transcribir']);
Route::get('/estudio-buscar-por-numdocu/{numDocu}', [EstudioController::class, 'buscarEstudioPorNumDocu'])
    ->name('estudio.buscarEstudioPorNumDocu')
    ->middleware(['auth', 'permission:estudio.asignar|estudio.transcribir|estudio.leerEstudio']);

/**
 * Otras Plataformas
 */
Route::view('/otras-plataformas.listar-estudio-enviar', 'modulos.otras-plataformas.listar-estudio-enviar')
    ->name('otras.plataformas.listar.estudio.enviar')
    ->middleware(['auth']);

/**
 * Tele Radiologia
 */
Route::view('/tele-radiologia', 'modulos.tele-radiologia.tele-radiologia')
    ->name('teleradiologia.index')
    ->middleware(['auth', 'permission:estudio.asignar']);
Route::view('/tele-radiologia-transcribir', 'modulos.tele-radiologia.tele-radiologia-trasncribir')
    ->name('teleradiologia.transcribir')
    ->middleware(['auth', 'permission:estudio.transcribir']);
Route::post('/tele-radiologia-enviar-estudio', [TeleRadiologiaController::class, 'enviarEstudio'])
    ->name('tele.radiologia.enviar.estudio')
    ->middleware(['auth', 'permission:estudio.asignar']);
Route::post('/tele-radiologia-listar-estudios', [TeleRadiologiaController::class, 'listarEstudios'])
    ->name('tele.radiologia.listar.estudios')
    ->middleware(['auth', 'permission:estudio.asignar']);
Route::post('/tele-radiologia-enviar-orden', [TeleRadiologiaController::class, 'enviarOrdenTeleRadiologia'])
    ->name('tele.radiologia.enviar.orden')
    ->middleware(['auth', 'permission:estudio.asignar']);

/*
 * Ecografias
 */
Route::view('/ecografias.admisiones', 'modulos.ecografias.admisiones')->name('ecografias.admisiones')->middleware(['auth']);
Route::view('/ecografias.admisionadas', 'modulos.ecografias.adminisionadas')->name('ecografias.adminisionadas')->middleware(['auth']);
Route::view('/ecografias-pendientes-lecturas', 'modulos.ecografias.lectura')->name('ecografias.pendientes.lecturas')->middleware(['auth']);

Route::resource('ecografias', EcografiaController::class)->names('ecografias')->except(['create', 'edit'])->middleware(['auth']);
Route::post('ecografias-filtar', [EcografiaController::class, 'filtarEcografias'])->name('ecografias.filtar')->middleware(['auth']);
Route::post('ecografia-anular/{id}', [EcografiaController::class, 'anularEcografia'])->name('ecografias.anular')->middleware(['auth']);

Route::put('ecografia-lectura/{ecografia_id}', [ecografiaLecturaController::class, 'leer'])->name('ecografias.lectura')->middleware(['auth']);
Route::post('ecografia-generar-reporte/{ecografia_id}', [EcografiaController::class, 'generarReporte'])->name('ecografias.generar.reporte')->middleware(['auth']);

Route::post('/ecografia-listarPendientesPorLectura/{idMedico}', [ecografiaLecturaController::class, 'listarPendientesPorLectura'])->name('ecografia.listarPendientesPorLectura')->middleware(['auth']);

Route::resource('ecografias-productos', EcografiaProductoController::class)->names('ecografias.productos')->except(['create', 'edit'])->middleware(['auth']);
Route::post('/ecografia-guardarLectura', [ecografiaLecturaController::class, 'guardarLectura'])->name('ecografia.guardarLectura')->middleware(['auth']);

Route::post('/interface-sahi-ecografias/listar/productos/atencion', [SAHIEcografiasController::class, 'listarProductosAtencion'])->name('sahi.listar.productos.atencion')->middleware(['auth']);
Route::post('/interface-sahi-ecografias/enviar/ecografia-producto', [SAHIEcografiasEnviarController::class, 'enviarPorEcografiaProducto'])->name('sahi.ecografias.enviar.ecografiaProducto')->middleware(['auth']);
Route::get('/interface-sahi-ecografias/estado/ecografia-producto/{ecografia_producto_id}', [SAHIEcografiasEnviarController::class, 'estadoPorEcografiaProducto'])->name('sahi.ecografias.estado.ecografiaProducto')->middleware(['auth']);

/**
 * Reportes
 */
Route::view('reportes-produccion', 'modulos.reportes.reportes-produccion')->name('reportes.produccion')->middleware('auth');
Route::get('descarga-reportes-produccion', [ReporteProduccion::class, 'reporteProduccionDetallado'])->name('descarga.reportes.produccion')->middleware('auth');

/**
 * Configuración
 */
Route::view('/config-adminsalud-listar', 'modulos.configuracion.config-admin-salud')->name('config.adminsalud.listar')->middleware(['auth']);
Route::resource('/config-admin-salud', ConfigAdminSaludController::class)->names('config.admin.salud')->except(['create', 'show']);

Route::view('/config-diagnosticos-listar', 'modulos.configuracion.config-diagnosticos')->name('config.diagnosticos.listar')->middleware(['auth']);
Route::resource('/config-diagnosticos', ConfigDiagnosticoController::class)->names('config.diagnosticos')->except(['create', 'show']);

Route::view('/config-prioridades-listar', 'modulos.configuracion.config-prioridades')->name('config.prioridades.listar')->middleware(['auth']);
Route::resource('/config-prioridades', ConfigPrioridadController::class)->names('config.prioridades')->except(['create', 'show']);

Route::view('/config-productos-listar', 'modulos.configuracion.config-productos')->name('config.productos.listar')->middleware(['auth']);
Route::resource('/config-productos', ConfigProductoController::class)->names('config.productos')->except(['create']);

Route::view('/config-sedes-listar', 'modulos.configuracion.config-sedes')->name('config.sedes.listar')->middleware(['auth']);
Route::post('/config/sedes/updateWitchPlantillas', [ConfigSedeController::class, 'update'])->name('config.sedes.update');
Route::resource('/config-sedes', ConfigSedeController::class)->names('config.sedes')->except(['create', 'show', 'update']);
Route::get('/config-sedes/{configsede}/buscarPorId', [ConfigSedeController::class, 'buscarPorId'])->name('config.sedes.buscarPorId');
Route::get('/config-sedes/listar/sedes/activas', [ConfigSedeController::class, 'listarSedesActivas'])->name('config.sedes.listarSedesActivas');

Route::view('/config-plantillas-listar', 'modulos.configuracion.config-plantillas')->name('config.plantillas.listar')->middleware(['auth']);
Route::resource('/config-plantillas', ConfigPlantillaController::class)->names('config.plantillas')->except(['create', 'edit']);
Route::get('/config-plantillas/listar/activos/{tipo_id}', [ConfigPlantillaController::class, 'listarActivos'])->name('config.plantillas.listar.activos');

Route::view('/config-tipos-plantillas.listar', 'modulos.configuracion.config-tipos-plantillas')->name('config.tipos.plantillas.listar')->middleware(['auth']);
Route::resource('/config-tipos-plantillas', ConfigPlantillaTipoController::class)->names('config.tipos.plantillas')->except(['create', 'edit']);
Route::get('/config-tipos-plantillas/listar/activos', [ConfigPlantillaTipoController::class, 'listarActivos'])->name('config.tipos.plantillas.listar.activos');

Route::get('confing-modulos-listar', [ConfigModulosController::class, 'listarModulos'])->name('confing.modulos.listar');

Route::view('/config-tipos-documentos-listar', 'modulos.configuracion.config-tipos-documentos')->name('config.tipos.documentos.listar')->middleware(['auth']);
Route::resource('/config-tipos-documentos', ConfigTipoDocumentoController::class)->names('config.tipos.documentos')->except(['create', 'edit']);
Route::get('/config-tipos-documentos/listar/activos', [ConfigTipoDocumentoController::class, 'listarActivos'])->name('config.tipos.documentos.listar.activos');

Route::view('/config-consentimiento-informado-listar', 'modulos.configuracion.config-consentimiento-informado')->name('config.consentimiento.informado.listar')->middleware(['auth']);

/**
 * Seguridad
 */
Route::view('/seguridad.usuarios.listar', 'modulos.seguridad.usuarios')->name('seguridad.usuarios.listar')->middleware(['auth']);
Route::resource('/seguridad-usuarios', UserController::class)->names('seguridad.usuarios')->except('update');
Route::post('/seguridad/usuarios/update', [UserController::class, 'update'])->name('seguridad.update');
Route::put('/seguridad-actualizar-perfil/{idUsua}', [UserController::class, 'actualizarPerfil'])->name('seguridad.actualizar.perfil');
Route::put('/seguridad-usuarios/estado/{idUsua}', [UserController::class, 'estadoUsuario'])->name('seguridad.usuario.estado');
Route::get('/user.listarUsuarios/{tipo_user}/{modulo}', [UserController::class, 'listarUsuarios'])
    ->name('user.listarUsuarios')
    ->middleware(['auth']);

Route::view('/seguridad.roles.listar', 'modulos.seguridad.roles')->name('seguridad.roles.listar')->middleware(['auth']);
Route::resource('/seguridad-roles', RolController::class)->names('seguridad.roles');

Route::get('/usuario-por-modulo/{modulo_id}', [UserController::class, 'usuariosPorModulo'])->name('usuarios.por.modulo');

Route::view('/seguridad.perfil', 'modulos.seguridad.perfil')->name('seguridad.perfil')->middleware(['auth']);
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
Route::get('/importar-dicom', [ImportarDicom::class, 'importarDicom'])->middleware(['auth']);

/**
 * Agendamiento
 */
Route::view('/agenda', 'modulos.agenda.index')->name('agendamiento.index')->middleware(['auth']);
Route::view('/agenda-disponibilidad', 'modulos.agenda.disponibilidad')->name('agenda.disponibilidad.index')->middleware(['auth']);
Route::resource('agenda-disponibilidad', AgendaDisponibilidaController::class)->names('agenda.disponibilidad')->except(['create', 'edit', ''])->middleware(['auth']);
Route::resource('agenda-detalle', AgendaDisponibilidaDetalleController::class)->names('agenda.detalle')->except(['create', 'edit', 'update'])->middleware(['auth']);



/* Route::get('/{optional?}', function () {
    return view('layouts.app');
})->name('basepath')->where('optional', '.*');
 */