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
        ->name('api.study.listarEstudios');

    Route::resource('/estudios', App\Http\Controllers\Estudios\EstudioController::class)
        ->names('api.estudios')
        ->except(['create', 'show']);

    Route::get('/study-listar-diagnostico-por-estudio', [App\Http\Controllers\Estudios\EstudioController::class, 'listarDiagnosticoPorEstudio'])
        ->name('api.study.listar.diagnostico.por.estudio');

    Route::resource('/estudios-productos', App\Http\Controllers\Estudios\EstudioProductoController::class)
        ->names('api.estudios.productos');

    Route::resource('/estudios-diagnosticos', App\Http\Controllers\Estudios\EstudioDiagnosticoController::class)
        ->names('api.estudios.diagnosticos');

    Route::get('/listar-soportes-hc-estudio', [App\Http\Controllers\Estudios\EstudioSoporteHCController::class, 'listarSoportesHistoriaClinicaPorPorEstudio'])
        ->name('api.study.listar.soporte.hc.estudio');

    // Descarga de soportes HC desde frontend Vue (blob). (equivalente a web.php)
    Route::get('/descarga-soportes-hc/{nomArchivoEncriptadote}', [App\Http\Controllers\Estudios\EstudioSoporteHCController::class, 'descargarSoporte'])
        ->name('api.study.descarga.soporte.hc');

    Route::post('/listar.estudios.notificaciones', [App\Http\Controllers\Estudios\EstudioNotificacionController::class, 'listarEstudios'])
        ->name('api.listar.estudios.notificaciones');

    Route::post('/notificar.email', [App\Http\Controllers\Estudios\EstudioController::class, 'notificarEmail'])
        ->name('api.notificar.email');

    Route::post('/enviar-portal-pacientes', [App\Http\Controllers\Estudios\EstudioController::class, 'enviarPortalPacientes'])
        ->name('api.enviar.portal.pacientes');

    Route::post('/marcar-visto/{id}', [App\Http\Controllers\Estudios\EstudioController::class, 'marcarEstudioVisto'])
        ->name('api.estudios.marcarEstudioVisto');

    Route::post('/edit-word', [App\Http\Controllers\Estudios\PlantillaEstudioController::class, 'editWord']);

    Route::post('/estudio-leerEstudio', [App\Http\Controllers\Estudios\EstudioController::class, 'leerEstudio'])
        ->name('api.estudio.leerEstudio');

    Route::post('/upload-audio', [App\Http\Controllers\Estudios\EstudioAudioController::class, 'uploadAudio']);

    Route::get('/listar-audios-estudio/{estudio_id}', [App\Http\Controllers\Estudios\EstudioAudioController::class, 'listarAudiosPorEstudio'])
        ->name('api.listarAudiosPorEstudio');

    Route::post('/estudio-listarPendientesTrascribir', [App\Http\Controllers\Estudios\EstudioController::class, 'listarPendientesTrascribir'])
        ->name('api.estudio.listarPendientesTrascribir');

    Route::post('/estudio-listarEstudiosAsignados', [App\Http\Controllers\Estudios\EstudioController::class, 'listarEstudiosAsignados'])
        ->name('api.estudio.listarEstudiosAsignados');

    Route::post('/estudio-guardarTranscripcion', [App\Http\Controllers\Estudios\EstudioController::class, 'guardarTranscripcion'])
        ->name('api.estudio.guardarTranscripcion');

    Route::post('/estudio-anular-asignacion', [App\Http\Controllers\Estudios\EstudioController::class, 'anularAsignacion'])
        ->name('api.estudio.anularAsignacion');

    Route::post('/estudio-listarPendientesMedico', [App\Http\Controllers\Estudios\EstudioController::class, 'listarPendientesMedico'])
        ->name('api.estudio.listarPendientesMedico');

    Route::get('/estudio-listarPendientesPorLeerTranscribir', [App\Http\Controllers\Estudios\EstudioController::class, 'penditesPorLeerTranscribir'])
        ->name('api.estudio.listarPendientesPorLeerTranscribir');

    Route::get('/estudio-buscar-por-numdocu/{numDocu}', [App\Http\Controllers\Estudios\EstudioController::class, 'buscarEstudioPorNumDocu'])
        ->name('api.estudio.buscarEstudioPorNumDocu');
});

Route::get('/estudios/convertir-audio-a-texto', [\App\Http\Controllers\Estudios\EstudioAudioController::class, 'convertirAudioToText'])->name('api.estudios.convertirAudioToText');
