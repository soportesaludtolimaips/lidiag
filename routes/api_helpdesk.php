<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Helpdesk API Routes
|--------------------------------------------------------------------------
| Rutas API del módulo Helpdesk.
| - Autenticación: Sanctum stateful (cookies/sesión) como el resto del sistema.
| - Autorización: permisos Spatie con el patrón `permission:...,web`.
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Usuarios asignables (selector "Asignar a")
    Route::get('/helpdesk-usuarios-asignables', [App\Http\Controllers\HelpDesk\HelpDeskUsuariosController::class, 'asignables'])
        ->name('api.helpdesk.usuarios.asignables')
        ->middleware(['permission:helpdesk.tickets.create,web']);

    // Tickets
    Route::resource('/helpdesk-tickets', App\Http\Controllers\HelpDesk\HelpDeskTicketsController::class)
        ->names('api.helpdesk.tickets')
        ->parameters(['helpdesk-tickets' => 'ticket'])
        ->except(['create', 'edit'])
        ->middleware(['permission:helpdesk.tickets.index|helpdesk.tickets.create|helpdesk.tickets.show|helpdesk.tickets.edit|helpdesk.tickets.delete,web']);

    Route::post('/helpdesk-tickets/{ticket}/comment', [App\Http\Controllers\HelpDesk\HelpDeskTicketsController::class, 'comment'])
        ->name('api.helpdesk.tickets.comment')
        ->middleware(['permission:helpdesk.tickets.comment,web']);

    Route::post('/helpdesk-tickets/{ticket}/status', [App\Http\Controllers\HelpDesk\HelpDeskTicketsController::class, 'changeStatus'])
        ->name('api.helpdesk.tickets.status')
        ->middleware(['permission:helpdesk.tickets.change_status,web']);

    Route::post('/helpdesk-tickets/{ticket}/reassign', [App\Http\Controllers\HelpDesk\HelpDeskTicketsController::class, 'reassign'])
        ->name('api.helpdesk.tickets.reassign')
        ->middleware(['permission:helpdesk.tickets.reassign,web']);

    Route::post('/helpdesk-tickets/{ticket}/attachments', [App\Http\Controllers\HelpDesk\HelpDeskAdjuntosController::class, 'upload'])
        ->name('api.helpdesk.tickets.attachments.upload')
        ->middleware(['permission:helpdesk.tickets.attachments.upload,web']);

    // Adjuntos
    Route::get('/helpdesk-adjuntos/{adjunto}/download', [App\Http\Controllers\HelpDesk\HelpDeskAdjuntosController::class, 'download'])
        ->name('api.helpdesk.adjuntos.download')
        ->middleware(['permission:helpdesk.tickets.attachments.download,web']);

    Route::delete('/helpdesk-adjuntos/{adjunto}', [App\Http\Controllers\HelpDesk\HelpDeskAdjuntosController::class, 'destroy'])
        ->name('api.helpdesk.adjuntos.destroy')
        ->middleware(['permission:helpdesk.tickets.attachments.delete,web']);

    // Catálogos
    // - INDEX (uso operativo): accesible para pantallas de tickets/reportes
    // - CRUD (config): restringido a permisos de parametrización
    Route::get('/helpdesk-categorias', [App\Http\Controllers\HelpDesk\HelpDeskCategoriasController::class, 'index'])
        ->name('api.helpdesk.categorias.index')
        ->middleware(['permission:helpdesk.tickets.index|helpdesk.tickets.create|helpdesk.tickets.show|helpdesk.reportes.index|helpdesk.config.categorias.index,web']);

    Route::resource('/helpdesk-categorias', App\Http\Controllers\HelpDesk\HelpDeskCategoriasController::class)
        ->names('api.helpdesk.categorias')
        ->parameters(['helpdesk-categorias' => 'categoria'])
        ->except(['index', 'create', 'edit'])
        ->middleware(['permission:helpdesk.config.categorias.create|helpdesk.config.categorias.edit|helpdesk.config.categorias.destroy,web']);

    Route::get('/helpdesk-prioridades', [App\Http\Controllers\HelpDesk\HelpDeskPrioridadesController::class, 'index'])
        ->name('api.helpdesk.prioridades.index')
        ->middleware(['permission:helpdesk.tickets.index|helpdesk.tickets.create|helpdesk.tickets.show|helpdesk.reportes.index|helpdesk.config.prioridades.index,web']);

    Route::resource('/helpdesk-prioridades', App\Http\Controllers\HelpDesk\HelpDeskPrioridadesController::class)
        ->names('api.helpdesk.prioridades')
        ->parameters(['helpdesk-prioridades' => 'prioridad'])
        ->except(['index', 'create', 'edit'])
        ->middleware(['permission:helpdesk.config.prioridades.create|helpdesk.config.prioridades.edit|helpdesk.config.prioridades.destroy,web']);

    Route::get('/helpdesk-estados', [App\Http\Controllers\HelpDesk\HelpDeskEstadosController::class, 'index'])
        ->name('api.helpdesk.estados.index')
        ->middleware(['permission:helpdesk.tickets.change_status|helpdesk.tickets.create|helpdesk.tickets.show|helpdesk.reportes.index|helpdesk.config.estados.index,web']);

    Route::resource('/helpdesk-estados', App\Http\Controllers\HelpDesk\HelpDeskEstadosController::class)
        ->names('api.helpdesk.estados')
        ->parameters(['helpdesk-estados' => 'estado'])
        ->except(['index', 'create', 'edit'])
        ->middleware(['permission:helpdesk.config.estados.create|helpdesk.config.estados.edit|helpdesk.config.estados.destroy,web']);

    // Notificaciones internas (Laravel notifications)
    Route::get('/helpdesk-notificaciones', [App\Http\Controllers\HelpDesk\HelpDeskNotificacionesController::class, 'index'])
        ->name('api.helpdesk.notificaciones.index')
        ->middleware(['permission:helpdesk.notificaciones.index,web']);

    Route::post('/helpdesk-notificaciones/{id}/read', [App\Http\Controllers\HelpDesk\HelpDeskNotificacionesController::class, 'markRead'])
        ->name('api.helpdesk.notificaciones.read')
        ->middleware(['permission:helpdesk.notificaciones.read,web']);

    Route::post('/helpdesk-notificaciones/read-all', [App\Http\Controllers\HelpDesk\HelpDeskNotificacionesController::class, 'markAllRead'])
        ->name('api.helpdesk.notificaciones.read_all')
        ->middleware(['permission:helpdesk.notificaciones.read,web']);

    /**
     * Reportes
     */
    Route::get('/helpdesk-reportes/indicadores', [App\Http\Controllers\HelpDesk\HelpDeskReportesController::class, 'indicadores'])
        ->name('api.helpdesk.reportes.indicadores')
        ->middleware(['permission:helpdesk.reportes.index,web']);

    Route::get('/helpdesk-reportes/detallado', [App\Http\Controllers\HelpDesk\HelpDeskReportesController::class, 'detallado'])
        ->name('api.helpdesk.reportes.detallado')
        ->middleware(['permission:helpdesk.reportes.index,web']);

    Route::get('/helpdesk-reportes/export/excel', [App\Http\Controllers\HelpDesk\HelpDeskReportesController::class, 'exportExcel'])
        ->name('api.helpdesk.reportes.export.excel')
        ->middleware(['permission:helpdesk.reportes.export,web']);

    Route::get('/helpdesk-reportes/export/pdf', [App\Http\Controllers\HelpDesk\HelpDeskReportesController::class, 'exportPdf'])
        ->name('api.helpdesk.reportes.export.pdf')
        ->middleware(['permission:helpdesk.reportes.export,web']);
});
