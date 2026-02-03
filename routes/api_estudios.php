<?php

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
    Route::post('/study.listarEstudios', [App\Http\Controllers\Dcm4chee\StudyController::class, 'listarEstudios'])
        ->name('api.study.listarEstudios')
        ->middleware(['permission:estudio.asignar,web']);

    Route::resource('/estudios', App\Http\Controllers\Estudios\EstudioController::class)
        ->names('api.estudios')
        ->except(['create', 'show'])
        ->middleware(['permission:estudio.asignar|estudio.transcribir|estudio.leerestudio|estudio.leerEstudio,web']);

    Route::get('/study-listar-diagnostico-por-estudio', [App\Http\Controllers\Estudios\EstudioController::class, 'listarDiagnosticoPorEstudio'])
        ->name('api.study.listar.diagnostico.por.estudio')
        ->middleware(['permission:estudio.asignar|estudio.transcribir|estudio.leerestudio|estudio.leerEstudio,web']);

    Route::resource('/estudios-productos', App\Http\Controllers\Estudios\EstudioProductoController::class)
        ->names('api.estudios.productos');

    Route::resource('/estudios-diagnosticos', App\Http\Controllers\Estudios\EstudioDiagnosticoController::class)
        ->names('api.estudios.diagnosticos');

    Route::get('/listar-soportes-hc-estudio', [App\Http\Controllers\Estudios\EstudioSoporteHCController::class, 'listarSoportesHistoriaClinicaPorPorEstudio'])
        ->name('api.study.listar.soporte.hc.estudio')
        ->middleware(['permission:estudio.asignar|estudio.transcribir|estudio.leerestudio|estudio.leerEstudio,web']);

    // Descarga de soportes HC desde frontend Vue (blob). (equivalente a web.php)
    Route::get('/descarga-soportes-hc/{nomArchivoEncriptadote}', [App\Http\Controllers\Estudios\EstudioSoporteHCController::class, 'descargarSoporte'])
        ->name('api.study.descarga.soporte.hc')
        ->middleware(['permission:estudio.asignar|estudio.transcribir|estudio.leerestudio|estudio.leerEstudio,web']);

    Route::post('/listar.estudios.notificaciones', [App\Http\Controllers\Estudios\EstudioNotificacionController::class, 'listarEstudios'])
        ->name('api.listar.estudios.notificaciones')
        ->middleware(['permission:estudios.notificar.index,web']);

    Route::post('/notificar.email', [App\Http\Controllers\Estudios\EstudioController::class, 'notificarEmail'])
        ->name('api.notificar.email')
        ->middleware(['permission:estudios.notificar.index|estudio.transcribir,web']);

    Route::post('/enviar-portal-pacientes', [App\Http\Controllers\Estudios\EstudioController::class, 'enviarPortalPacientes'])
        ->name('api.enviar.portal.pacientes')
        ->middleware(['permission:estudios.notificar.index|estudio.transcribir,web']);

    Route::post('/marcar-visto/{id}', [App\Http\Controllers\Estudios\EstudioController::class, 'marcarEstudioVisto'])
        ->name('api.estudios.marcarEstudioVisto')
        ->middleware(['permission:estudio.leerestudio|estudio.leerEstudio|estudio.transcribir,web']);

    Route::post('/edit-word', [App\Http\Controllers\Estudios\PlantillaEstudioController::class, 'editWord'])
        ->middleware(['permission:estudio.transcribir|estudio.leerestudio|estudio.leerEstudio,web']);

    Route::post('/estudio-leerEstudio', [App\Http\Controllers\Estudios\EstudioController::class, 'leerEstudio'])
        ->name('api.estudio.leerEstudio')
        ->middleware(['permission:estudio.leerestudio|estudio.leerEstudio,web']);

    Route::post('/upload-audio', [App\Http\Controllers\Estudios\EstudioAudioController::class, 'uploadAudio'])
        ->middleware(['permission:estudio.transcribir|estudio.leerestudio|estudio.leerEstudio,web']);

    Route::get('/listar-audios-estudio/{estudio_id}', [App\Http\Controllers\Estudios\EstudioAudioController::class, 'listarAudiosPorEstudio'])
        ->name('api.listarAudiosPorEstudio')
        ->middleware(['permission:estudio.transcribir|estudio.leerestudio|estudio.leerEstudio,web']);

    Route::post('/estudio-listarPendientesTrascribir', [App\Http\Controllers\Estudios\EstudioController::class, 'listarPendientesTrascribir'])
        ->name('api.estudio.listarPendientesTrascribir')
        ->middleware(['permission:estudio.transcribir,web']);

    Route::post('/estudio-listarEstudiosAsignados', [App\Http\Controllers\Estudios\EstudioController::class, 'listarEstudiosAsignados'])
        ->name('api.estudio.listarEstudiosAsignados')
        ->middleware(['permission:estudio.asignar|estudio.transcribir,web']);

    Route::post('/estudio-guardarTranscripcion', [App\Http\Controllers\Estudios\EstudioController::class, 'guardarTranscripcion'])
        ->name('api.estudio.guardarTranscripcion')
        ->middleware(['permission:estudio.transcribir,web']);

    Route::post('/estudio-anular-asignacion', [App\Http\Controllers\Estudios\EstudioController::class, 'anularAsignacion'])
        ->name('api.estudio.anularAsignacion')
        ->middleware(['permission:estudio.asignar,web']);

    Route::post('/estudio-listarPendientesMedico', [App\Http\Controllers\Estudios\EstudioController::class, 'listarPendientesMedico'])
        ->name('api.estudio.listarPendientesMedico')
        ->middleware(['permission:estudio.leerestudio|estudio.leerEstudio,web']);

    Route::get('/estudio-listarPendientesPorLeerTranscribir', [App\Http\Controllers\Estudios\EstudioController::class, 'penditesPorLeerTranscribir'])
        ->name('api.estudio.listarPendientesPorLeerTranscribir')
        ->middleware(['permission:estudio.transcribir,web']);

    Route::get('/estudio-buscar-por-numdocu/{numDocu}', [App\Http\Controllers\Estudios\EstudioController::class, 'buscarEstudioPorNumDocu'])
        ->name('api.estudio.buscarEstudioPorNumDocu')
        ->middleware(['permission:estudio.asignar|estudio.transcribir|estudio.leerestudio|estudio.leerEstudio,web']);
});
