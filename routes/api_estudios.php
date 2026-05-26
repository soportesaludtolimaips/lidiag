<?php

use App\Http\Controllers\Dcm4chee\StudyController;
use App\Http\Controllers\Estudios\EstudioAudioController;
use App\Http\Controllers\Estudios\EstudioController;
use App\Http\Controllers\Estudios\EstudioDiagnosticoController;
use App\Http\Controllers\Estudios\EstudioNotificacionController;
use App\Http\Controllers\Estudios\EstudioProductoController;
use App\Http\Controllers\Estudios\EstudioSoporteHCController;
use App\Http\Controllers\Estudios\PlantillaEstudioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Estudios API Routes
|--------------------------------------------------------------------------
|
| Aquí se definen todas las rutas API relacionadas con los estudios
| radiológicos (CRUD, asignación, transcripción, lectura, etc.)
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    /*
     * Estudios API Endpoints
     */
    Route::post('/study.listarEstudios', [StudyController::class, 'listarEstudios'])
        ->name('api.study.listarEstudios');

    Route::resource('/estudios', EstudioController::class)
        ->names('api.estudios')
        ->except(['create', 'show']);

    Route::get('/study-listar-diagnostico-por-estudio', [EstudioController::class, 'listarDiagnosticoPorEstudio'])
        ->name('api.study.listar.diagnostico.por.estudio');

    Route::resource('/estudios-productos', EstudioProductoController::class)
        ->names('api.estudios.productos');

    Route::resource('/estudios-diagnosticos', EstudioDiagnosticoController::class)
        ->names('api.estudios.diagnosticos');

    Route::get('/listar-soportes-hc-estudio', [EstudioSoporteHCController::class, 'listarSoportesHistoriaClinicaPorPorEstudio'])
        ->name('api.study.listar.soporte.hc.estudio');

    // Descarga de soportes HC desde frontend Vue (blob). (equivalente a web.php)
    Route::get('/descarga-soportes-hc/{nomArchivoEncriptadote}', [EstudioSoporteHCController::class, 'descargarSoporte'])
        ->name('api.study.descarga.soporte.hc');

    Route::post('/listar.estudios.notificaciones', [EstudioNotificacionController::class, 'listarEstudios'])
        ->name('api.listar.estudios.notificaciones');

    Route::post('/notificar.email', [EstudioController::class, 'notificarEmail'])
        ->name('api.notificar.email');

    Route::post('/enviar-portal-pacientes', [EstudioController::class, 'enviarPortalPacientes'])
        ->name('api.enviar.portal.pacientes');

    Route::post('/marcar-visto/{id}', [EstudioController::class, 'marcarEstudioVisto'])
        ->name('api.estudios.marcarEstudioVisto');

    Route::post('/edit-word', [PlantillaEstudioController::class, 'editWord']);

    Route::post('/estudio-leerEstudio', [EstudioController::class, 'leerEstudio'])
        ->name('api.estudio.leerEstudio');

    Route::post('/upload-audio', [EstudioAudioController::class, 'uploadAudio']);

    Route::get('/listar-audios-estudio/{estudio_producto_id}', [EstudioAudioController::class, 'listarAudiosPorEstudio'])
        ->name('api.listarAudiosPorEstudio');

    Route::post('/cargar-audio-transcribir', [EstudioAudioController::class, 'cargarAudioTranscribir'])
        ->name('api.cargarAudioTranscribir');

    Route::delete('/eliminar-audio-cargado/{id}', [EstudioAudioController::class, 'eliminarAudioCargado'])
        ->name('api.eliminarAudioCargado');

    Route::get('/stream-audio/{filename}', [EstudioAudioController::class, 'stream'])
        ->name('api.streamAudio');

    Route::post('/estudio-listarPendientesTrascribir', [EstudioController::class, 'listarPendientesTrascribir'])
        ->name('api.estudio.listarPendientesTrascribir');

    Route::post('/estudio-listarEstudiosAsignados', [EstudioController::class, 'listarEstudiosAsignados'])
        ->name('api.estudio.listarEstudiosAsignados');

    Route::post('/estudio-guardarTranscripcion', [EstudioController::class, 'guardarTranscripcion'])
        ->name('api.estudio.guardarTranscripcion');

    Route::post('/estudio-anular-asignacion', [EstudioController::class, 'anularAsignacion'])
        ->name('api.estudio.anularAsignacion');

    Route::post('/estudio-listarPendientesMedico', [EstudioController::class, 'listarPendientesMedico'])
        ->name('api.estudio.listarPendientesMedico');

    Route::get('/estudio-listarPendientesPorLeerTranscribir', [EstudioController::class, 'penditesPorLeerTranscribir'])
        ->name('api.estudio.listarPendientesPorLeerTranscribir');

    Route::get('/estudio-buscar-por-numdocu/{numDocu}', [EstudioController::class, 'buscarEstudioPorNumDocu'])
        ->name('api.estudio.buscarEstudioPorNumDocu');
});
