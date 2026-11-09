/**
 * Definición centralizada de rutas de Vue Router para LIDiag SPA.
 * Mapea las vistas del frontend con importaciones dinámicas asíncronas
 * y metadatos de permisos Spatie para control de acceso.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-09
 */

export const routes = [
    {
        path: "/",
        redirect: "/dashboard",
    },
    {
        path: "/dashboard",
        name: "dashboard",
        component: () => import("../components/dashboard/dashboardComponent.vue"),
        meta: { title: "Dashboard Principal", auth: true },
    },

    // Gestión Clínica — Pacientes
    {
        path: "/pacientes/admision",
        name: "pacientes.admision",
        component: () => import("../components/modulos/pacientes/admisionComponent.vue"),
        meta: {
            title: "Admisión de Pacientes",
            permission: "gestion.pacientes.index",
            auth: true,
        },
    },

    // Gestión Clínica — Estudios Radiológicos
    {
        path: "/estudios.asignar.listar",
        name: "estudios.asignar",
        component: () => import("../components/modulos/estudios/AsignarEstudioComponent.vue"),
        meta: { title: "Asignar Estudios", permission: "estudio.asignar", auth: true },
    },
    {
        path: "/estudios.asignados.listar",
        name: "estudios.asignados",
        component: () => import("../components/modulos/estudios/estudioAsignadosComponent.vue"),
        meta: { title: "Estudios Asignados", permission: "estudio.asignar", auth: true },
    },
    {
        path: "/estudios.transcribir.listar",
        name: "estudios.transcribir",
        component: () => import("../components/modulos/estudios/transcribirEstudioComponent.vue"),
        meta: {
            title: "Pendientes por Transcribir",
            permission: "estudio.transcribir",
            auth: true,
        },
    },
    {
        path: "/estudios.leer.estudio.listar",
        name: "estudios.leer",
        component: () => import("../components/modulos/estudios/leerEstudioComponent.vue"),
        meta: { title: "Pendientes por Leer", permission: "estudio.leerEstudio", auth: true },
    },
    {
        path: "/estudios.notificar.index",
        name: "estudios.notificar",
        component: () => import("../components/modulos/estudios/notificarEstudioComponent.vue"),
        meta: {
            title: "Notificar Estudio",
            permission: "estudios.notificar.index",
            auth: true,
        },
    },

    // Tele-Radiología
    {
        path: "/tele-radiologia",
        alias: "/estudios.tele-radiologia",
        name: "teleradiologia.enviar",
        component: () =>
            import("../components/modulos/tele-radiologia/teleRadiologiaEnviarEstudioComponent.vue"),
        meta: { title: "Tele-Radiología", permission: "tele.ragiologia.index", auth: true },
    },
    {
        path: "/tele-radiologia-transcribir",
        name: "teleradiologia.transcribir",
        component: () =>
            import("../components/modulos/tele-radiologia/teleRadiologiaTranscibirComponent.vue"),
        meta: {
            title: "Transcribir Tele-Radiología",
            permission: "tele.ragiologia.transcribir",
            auth: true,
        },
    },

    // Ecografías
    {
        path: "/ecografias.admisiones",
        name: "ecografias.admisiones",
        component: () => import("../components/modulos/ecografias/admisionarComponent.vue"),
        meta: { title: "Admisión Ecografías", permission: "ecografias.admision", auth: true },
    },
    {
        path: "/ecografias.admisionadas",
        name: "ecografias.admisionadas",
        component: () =>
            import("../components/modulos/ecografias/ecografiasAdmisionadasComponent.vue"),
        meta: { title: "Ecografías Admisionadas", permission: "ecografias.leer", auth: true },
    },
    {
        path: "/ecografias-pendientes-lecturas",
        name: "ecografias.lectura",
        component: () => import("../components/modulos/ecografias/lecturaComponent.vue"),
        meta: { title: "Lectura de Ecografías", permission: "ecografias.leer", auth: true },
    },
    {
        path: "/ecografias-consentimientos",
        alias: "/config-consentimientos",
        name: "ecografias.consentimientos",
        component: () =>
            import("../components/modulos/ecografias/ecografiasConsentimientosComponent.vue"),
        meta: {
            title: "Consentimientos Informados",
            permission: "ecografias.reporte",
            auth: true,
        },
    },
    {
        path: "/ecografias-consentimientos-plantillas",
        alias: "/config-consentimientos-plantillas",
        name: "ecografias.consentimientos.plantillas",
        component: () =>
            import("../components/modulos/ecografias/ecografiasConsentimientosPlantillasComponent.vue"),
        meta: {
            title: "Plantillas Consentimientos",
            permission: "ecografias.reporte",
            auth: true,
        },
    },

    // Control de Radiación / Dosimetría (Res. 482)
    {
        path: "/dosimetria/dashboard",
        alias: "/dosis/dashboard",
        name: "dosimetria.dashboard",
        component: () => import("../components/modulos/dosimetria/DashboardDosimetria.vue"),
        meta: {
            title: "Dashboard Dosimetría Radiológica",
            permission: "dosimetria.dashboard",
            auth: true,
        },
    },

    // Citas y Agenda
    {
        path: "/citas",
        name: "citas.index",
        component: () => import("../components/modulos/citas/citasComponent.vue"),
        meta: { title: "Agendamiento de Citas", permission: "citas.index", auth: true },
    },
    {
        path: "/citas/gestion",
        name: "citas.gestion",
        component: () => import("../components/modulos/citas/gestionCitasComponent.vue"),
        meta: { title: "Gestión de Citas", permission: "citas.index", auth: true },
    },
    {
        path: "/citas/revision",
        name: "citas.revision",
        component: () => import("../components/modulos/citas/citasRevisionComponent.vue"),
        meta: { title: "Revisión de Citas WhatsApp", permission: "citas.index", auth: true },
    },
    {
        path: "/citas/recomendaciones",
        name: "citas.recomendaciones",
        component: () => import("../components/modulos/citas/recomendacionProductoComponent.vue"),
        meta: { title: "Recomendaciones de Exámenes", permission: "citas.index", auth: true },
    },
    {
        path: "/agenda",
        name: "agenda.index",
        component: () => import("../components/modulos/agenda/agendaDisponibilidadComponent.vue"),
        meta: { title: "Agenda Médica", permission: "agenda.index", auth: true },
    },
    {
        path: "/agenda-disponibilidad",
        name: "agenda.disponibilidad",
        component: () => import("../components/modulos/agenda/agendaDisponibilidadComponent.vue"),
        meta: {
            title: "Disponibilidad de Agenda",
            permission: "agenda.disponibilidad",
            auth: true,
        },
    },

    // Helpdesk
    {
        path: "/helpdesk-tickets",
        name: "helpdesk.tickets",
        component: () => import("../components/modulos/help-desk/helpdeskTicketsComponent.vue"),
        meta: { title: "Tickets de Soporte", permission: "helpdesk.tickets.index", auth: true },
    },
    {
        path: "/helpdesk-tickets/crear",
        name: "helpdesk.tickets.crear",
        component: () => import("../components/modulos/help-desk/helpdeskCrearTicketComponent.vue"),
        meta: { title: "Crear Ticket", permission: "helpdesk.tickets.create", auth: true },
    },
    {
        path: "/helpdesk-tickets/:ticket_id",
        name: "helpdesk.tickets.detalle",
        component: () =>
            import("../components/modulos/help-desk/helpdeskDetalleTicketComponent.vue"),
        props: true,
        meta: { title: "Detalle de Ticket", permission: "helpdesk.tickets.show", auth: true },
    },
    {
        path: "/helpdesk-reportes",
        name: "helpdesk.reportes",
        component: () => import("../components/modulos/help-desk/helpdeskReportesComponent.vue"),
        meta: { title: "Reportes Helpdesk", permission: "helpdesk.reportes.index", auth: true },
    },
    {
        path: "/helpdesk-notificaciones",
        name: "helpdesk.notificaciones",
        component: () =>
            import("../components/modulos/help-desk/helpdeskNotificacionesComponent.vue"),
        meta: {
            title: "Notificaciones Helpdesk",
            permission: "helpdesk.notificaciones.index",
            auth: true,
        },
    },

    // Seguridad
    {
        path: "/seguridad.usuarios.listar",
        name: "seguridad.usuarios",
        component: () => import("../components/modulos/seguridad/usuarioComponent.vue"),
        meta: {
            title: "Gestión de Usuarios",
            permission: "seguridad.usuarios.index",
            auth: true,
        },
    },
    {
        path: "/seguridad.roles.listar",
        name: "seguridad.roles",
        component: () => import("../components/modulos/seguridad/roleComponente.vue"),
        meta: { title: "Roles y Permisos", permission: "seguridad.roles.index", auth: true },
    },
    {
        path: "/seguridad.perfil",
        name: "seguridad.perfil",
        component: () => import("../components/modulos/seguridad/usuarioPerfil.vue"),
        meta: { title: "Perfil de Usuario", auth: true },
    },
    {
        path: "/seguridad-auditoria",
        name: "seguridad.auditoria",
        component: () => import("../components/modulos/seguridad/auditLogComponent.vue"),
        meta: {
            title: "Bitácora de Auditoría",
            permission: "seguridad.auditoria.index",
            auth: true,
        },
    },
    {
        path: "/seguridad-backups",
        name: "seguridad.backups",
        component: () => import("../components/modulos/seguridad/backupMonitorComponent.vue"),
        meta: {
            title: "Monitor Central de Backups",
            permission: "seguridad.backups.index",
            auth: true,
        },
    },
    {
        path: "/seguridad-tokens",
        name: "seguridad.tokens",
        component: () => import("../components/modulos/seguridad/seguridadTokensComponent.vue"),
        meta: {
            title: "Tokens de API y Sedes",
            permission: "seguridad.tokens.index",
            auth: true,
        },
    },

    // Contratación y Liquidación
    {
        path: "/contratos",
        name: "contratos",
        component: () => import("../components/modulos/contratos/contratoComponent.vue"),
        meta: { title: "Contratos de Clientes", permission: "contratos.index", auth: true },
    },
    {
        path: "/liquidacion",
        name: "liquidacion",
        component: () => import("../components/modulos/liquidacion/liquidacionComponent.vue"),
        meta: { title: "Liquidación Médica", permission: "liquidacion.index", auth: true },
    },
    {
        path: "/productividad",
        name: "productividad",
        component: () => import("../components/modulos/liquidacion/productividadComponent.vue"),
        meta: {
            title: "Productividad y Rendimiento",
            permission: "productividad.index",
            auth: true,
        },
    },

    // Reportes
    {
        path: "/reportes-produccion",
        name: "reportes.produccion",
        component: () => import("../components/modulos/reportes/reporteProduccionComponent.vue"),
        meta: {
            title: "Reportes de Producción",
            permission: "reportes.produccion.index",
            auth: true,
        },
    },
    {
        path: "/reportes-estudios-pac",
        name: "reportes.pac",
        component: () => import("../components/modulos/reportes/reporteEstudiosPACComponent.vue"),
        meta: { title: "Reportes PACS", permission: "reportes.pac.index", auth: true },
    },

    // Configuración General
    {
        path: "/config-adminsalud-listar",
        name: "config.adminsalud",
        component: () =>
            import("../components/modulos/configuracion/configAdminSaludComponent.vue"),
        meta: {
            title: "Configuración Administradoras",
            permission: "configuracion.adminsalud.index",
            auth: true,
        },
    },
    {
        path: "/config-diagnosticos-listar",
        name: "config.diagnosticos",
        component: () =>
            import("../components/modulos/configuracion/configDiagnosticoComponent.vue"),
        meta: {
            title: "Configuración Diagnósticos CIE-10",
            permission: "configuracion.diagnosticos.index",
            auth: true,
        },
    },
    {
        path: "/config-prioridades-listar",
        name: "config.prioridades",
        component: () => import("../components/modulos/configuracion/configPrioridadComponent.vue"),
        meta: {
            title: "Configuración Prioridades",
            permission: "configuracion.prioridades.index",
            auth: true,
        },
    },
    {
        path: "/config-productos-listar",
        name: "config.productos",
        component: () => import("../components/modulos/configuracion/configProductoComponent.vue"),
        meta: {
            title: "Configuración Productos CUPS",
            permission: "configuracion.productos.index",
            auth: true,
        },
    },
    {
        path: "/config-sedes-listar",
        name: "config.sedes",
        component: () => import("../components/modulos/configuracion/configSedeComponent.vue"),
        meta: {
            title: "Configuración Sedes",
            permission: "configuracion.sedes.index",
            auth: true,
        },
    },
    {
        path: "/config-plantillas-listar",
        name: "config.plantillas",
        component: () => import("../components/modulos/configuracion/configPlantilla.vue"),
        meta: {
            title: "Configuración Plantillas",
            permission: "configuracion.plantillas.index",
            auth: true,
        },
    },
    {
        path: "/config-tipos-plantillas.listar",
        name: "config.tiposplantillas",
        component: () => import("../components/modulos/configuracion/configTipoPlantilla.vue"),
        meta: {
            title: "Tipos de Plantillas",
            permission: "configuracion.tiposplantillas.index",
            auth: true,
        },
    },
    {
        path: "/config-servicio-producto",
        name: "config.servicioproducto",
        component: () =>
            import("../components/modulos/configuracion/configServicioProductoComponent.vue"),
        meta: {
            title: "Productos por Servicio",
            permission: "configuracion.servicioproducto.index",
            auth: true,
        },
    },
    {
        path: "/config-servicio-citas-listar",
        name: "config.serviciocitas",
        component: () =>
            import("../components/modulos/configuracion/configServicioCitaComponent.vue"),
        meta: {
            title: "Servicios de Citas",
            permission: "configuracion.serviciocitas.index",
            auth: true,
        },
    },
    {
        path: "/config-tipos-documentos-listar",
        name: "config.tiposdocumentos",
        component: () =>
            import("../components/modulos/configuracion/configTipoDocumentoComponent.vue"),
        meta: {
            title: "Tipos de Documento",
            permission: "configuracion.tiposdocumentos.index",
            auth: true,
        },
    },
    {
        path: "/config-consentimiento-informado-listar",
        name: "config.consentimientos",
        component: () =>
            import("../components/modulos/configuracion/configConsentimientoInformadoComponent.vue"),
        meta: {
            title: "Configuración Consentimientos",
            permission: "configuracion.consentimientos.index",
            auth: true,
        },
    },
    {
        path: "/config-whatsapp-listar",
        name: "config.whatsapp",
        component: () => import("../components/modulos/configuracion/configWhatsappComponent.vue"),
        meta: {
            title: "Configuración WhatsApp Bot",
            permission: "configuracion.whatsapp.index",
            auth: true,
        },
    },
    {
        path: "/config-cita-rechazo-motivos",
        name: "config.citarechazomotivos",
        component: () =>
            import("../components/modulos/configuracion/configCitaRechazoMotivoComponent.vue"),
        meta: {
            title: "Motivos de Rechazo de Citas",
            permission: "configuracion.citarechazomotivos.index",
            auth: true,
        },
    },
    {
        path: "/config-helpdesk-categorias-listar",
        name: "config.helpdeskcategorias",
        component: () =>
            import("../components/modulos/configuracion/helpdeskConfigCategoriasComponent.vue"),
        meta: {
            title: "Categorías Helpdesk",
            permission: "configuracion.helpdeskcategorias.index",
            auth: true,
        },
    },
    {
        path: "/config-helpdesk-prioridades-listar",
        name: "config.helpdeskprioridades",
        component: () =>
            import("../components/modulos/configuracion/helpdeskConfigPrioridadesComponent.vue"),
        meta: {
            title: "Prioridades Helpdesk",
            permission: "configuracion.helpdeskprioridades.index",
            auth: true,
        },
    },
    {
        path: "/config-helpdesk-estados-listar",
        name: "config.helpdeskestados",
        component: () =>
            import("../components/modulos/configuracion/helpdeskConfigEstadosComponent.vue"),
        meta: {
            title: "Estados Helpdesk",
            permission: "configuracion.helpdeskestados.index",
            auth: true,
        },
    },

    // Páginas de Estado
    {
        path: "/403",
        name: "forbidden",
        component: () => import("../components/comunes/ForbiddenComponent.vue"),
        meta: { title: "403 Acceso Denegado" },
    },
    {
        path: "/:pathMatch(.*)*",
        name: "not-found",
        component: () => import("../components/comunes/NotFoundComponent.vue"),
        meta: { title: "404 Página No Encontrada" },
    },
];
