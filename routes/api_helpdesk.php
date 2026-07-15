<?php

use App\Http\Controllers\HelpDesk\HelpDeskAdjuntosController;
use App\Http\Controllers\HelpDesk\HelpDeskCategoriasController;
use App\Http\Controllers\HelpDesk\HelpDeskEstadosController;
use App\Http\Controllers\HelpDesk\HelpDeskNotificacionesController;
use App\Http\Controllers\HelpDesk\HelpDeskPrioridadesController;
use App\Http\Controllers\HelpDesk\HelpDeskReportesController;
use App\Http\Controllers\HelpDesk\HelpDeskTicketsController;
use App\Http\Controllers\HelpDesk\HelpDeskUsuariosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Helpdesk API Routes
|--------------------------------------------------------------------------
| Rutas API del módulo Helpdesk.
| - Autenticación: Sanctum stateful (cookies/sesión) como el resto del sistema.
| - Autorización: permisos Spatie con el patrón `permission:...,web`.
*/

Route::middleware(['auth'])->group(function () {
    // Usuarios asignables (selector "Asignar a")
    Route::get('/helpdesk-usuarios-asignables', [HelpDeskUsuariosController::class, 'asignables'])
        ->name('api.helpdesk.usuarios.asignables')
        ->middleware(['permission:helpdesk.tickets.create,web']);

    // Tickets
    Route::resource('/helpdesk-tickets', HelpDeskTicketsController::class)
        ->names('api.helpdesk.tickets')
        ->parameters(['helpdesk-tickets' => 'ticket'])
        ->except(['create', 'edit'])
        ->middleware(['permission:helpdesk.tickets.index|helpdesk.tickets.create|helpdesk.tickets.show|helpdesk.tickets.edit|helpdesk.tickets.delete,web']);

    Route::post('/helpdesk-tickets/{ticket}/comment', [HelpDeskTicketsController::class, 'comment'])
        ->name('api.helpdesk.tickets.comment')
        ->middleware(['permission:helpdesk.tickets.comment,web']);

    Route::post('/helpdesk-tickets/{ticket}/status', [HelpDeskTicketsController::class, 'changeStatus'])
        ->name('api.helpdesk.tickets.status')
        ->middleware(['permission:helpdesk.tickets.change_status,web']);

    Route::post('/helpdesk-tickets/{ticket}/reassign', [HelpDeskTicketsController::class, 'reassign'])
        ->name('api.helpdesk.tickets.reassign')
        ->middleware(['permission:helpdesk.tickets.reassign,web']);

    Route::post('/helpdesk-tickets/{ticket}/attachments', [HelpDeskAdjuntosController::class, 'upload'])
        ->name('api.helpdesk.tickets.attachments.upload')
        ->middleware(['permission:helpdesk.tickets.attachments.upload,web']);

    // Adjuntos
    Route::get('/helpdesk-adjuntos/{adjunto}/download', [HelpDeskAdjuntosController::class, 'download'])
        ->name('api.helpdesk.adjuntos.download')
        ->middleware(['permission:helpdesk.tickets.attachments.download,web']);

    Route::delete('/helpdesk-adjuntos/{adjunto}', [HelpDeskAdjuntosController::class, 'destroy'])
        ->name('api.helpdesk.adjuntos.destroy')
        ->middleware(['permission:helpdesk.tickets.attachments.delete,web']);

    // Catálogos
    // - INDEX (uso operativo): accesible para pantallas de tickets/reportes
    // - CRUD (config): restringido a permisos de parametrización
    Route::get('/helpdesk-categorias', [HelpDeskCategoriasController::class, 'index'])
        ->name('api.helpdesk.categorias.index')
        ->middleware(['permission:helpdesk.tickets.index|helpdesk.tickets.create|helpdesk.tickets.show|helpdesk.reportes.index|helpdesk.config.categorias.index,web']);

    Route::resource('/helpdesk-categorias', HelpDeskCategoriasController::class)
        ->names('api.helpdesk.categorias')
        ->parameters(['helpdesk-categorias' => 'categoria'])
        ->except(['index', 'create', 'edit'])
        ->middleware(['permission:helpdesk.config.categorias.create|helpdesk.config.categorias.edit|helpdesk.config.categorias.destroy,web']);

    Route::get('/helpdesk-prioridades', [HelpDeskPrioridadesController::class, 'index'])
        ->name('api.helpdesk.prioridades.index')
        ->middleware(['permission:helpdesk.tickets.index|helpdesk.tickets.create|helpdesk.tickets.show|helpdesk.reportes.index|helpdesk.config.prioridades.index,web']);

    Route::resource('/helpdesk-prioridades', HelpDeskPrioridadesController::class)
        ->names('api.helpdesk.prioridades')
        ->parameters(['helpdesk-prioridades' => 'prioridad'])
        ->except(['index', 'create', 'edit'])
        ->middleware(['permission:helpdesk.config.prioridades.create|helpdesk.config.prioridades.edit|helpdesk.config.prioridades.destroy,web']);

    Route::get('/helpdesk-estados', [HelpDeskEstadosController::class, 'index'])
        ->name('api.helpdesk.estados.index')
        ->middleware(['permission:helpdesk.tickets.change_status|helpdesk.tickets.create|helpdesk.tickets.show|helpdesk.reportes.index|helpdesk.config.estados.index,web']);

    // Notificaciones internas (Laravel notifications)
    // Usan el middleware auth del grupo padre (stateful, no Sanctum).
    // No se aplica middleware web adicional para evitar doble StartSession + CSRF en API.
    Route::get('/helpdesk-notificaciones', [HelpDeskNotificacionesController::class, 'index'])
        ->name('api.helpdesk.notificaciones.index')
        ->middleware(['permission:helpdesk.notificaciones.index,web']);

    Route::post('/helpdesk-notificaciones/{id}/read', [HelpDeskNotificacionesController::class, 'markRead'])
        ->name('api.helpdesk.notificaciones.read')
        ->middleware(['permission:helpdesk.notificaciones.read,web']);

    Route::post('/helpdesk-notificaciones/read-all', [HelpDeskNotificacionesController::class, 'markAllRead'])
        ->name('api.helpdesk.notificaciones.read_all')
        ->middleware(['permission:helpdesk.notificaciones.read,web']);

    /**
     * Reportes
     */
    Route::get('/helpdesk-reportes/indicadores', [HelpDeskReportesController::class, 'indicadores'])
        ->name('api.helpdesk.reportes.indicadores')
        ->middleware(['permission:helpdesk.reportes.index,web']);

    Route::get('/helpdesk-reportes/detallado', [HelpDeskReportesController::class, 'detallado'])
        ->name('api.helpdesk.reportes.detallado')
        ->middleware(['permission:helpdesk.reportes.index,web']);

    Route::get('/helpdesk-reportes/export/excel', [HelpDeskReportesController::class, 'exportExcel'])
        ->name('api.helpdesk.reportes.export.excel')
        ->middleware(['permission:helpdesk.reportes.export,web']);

    Route::get('/helpdesk-reportes/export/pdf', [HelpDeskReportesController::class, 'exportPdf'])
        ->name('api.helpdesk.reportes.export.pdf')
        ->middleware(['permission:helpdesk.reportes.export,web']);
});
