/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import "./bootstrap";
import "../css/app.css";
import { createApp, defineAsyncComponent } from "vue";
import mitt from "mitt";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import router from "./router";

const emitter = mitt();

/**
 * Next, we will create a fresh Vue application instance. You may then begin
 * registering components with the application instance so they are ready
 * to use in your application's views.
 */
const app = createApp({});
app.config.globalProperties.emitter = emitter;

dayjs.extend(utc);
dayjs.extend(timezone);
window.dayjs = dayjs;

/**
 * Componentes Comunes y Shell (Carga Síncrona Inmediata)
 */
import commandPaletteComponent from "./components/comunes/commandPaletteComponent.vue";
app.component("CommandPaletteComponent", commandPaletteComponent);

import emptyStateComponent from "./components/comunes/EmptyStateComponent.vue";
app.component("EmptyStateComponent", emptyStateComponent);

import tableSkeletonLoader from "./components/comunes/TableSkeletonLoader.vue";
app.component("TableSkeletonLoader", tableSkeletonLoader);

import userProfileAndSearchComponent from "./components/comunes/userProfileAndSearchComponent.vue";
app.component("UserProfileAndSearchComponent", userProfileAndSearchComponent);

import footerComponent from "./components/comunes/footerComponent.vue";
app.component("FooterComponent", footerComponent);

import Select2 from "./components/shared/Select2.vue";
app.component("Select2", Select2);

import AppLayout from "./layouts/AppLayout.vue";
app.component("AppLayout", AppLayout);

/**
 * Dashboard (Asíncrono)
 */
app.component(
    "DashboardComponent",
    defineAsyncComponent(() => import("./components/dashboard/dashboardComponent.vue"))
);

/**
 * Pacientes y Admisión (Asíncrono)
 */
const AdmisionComponent = defineAsyncComponent(
    () => import("./components/modulos/pacientes/admisionComponent.vue")
);
app.component("AdmisionPacienteComponent", AdmisionComponent);

/**
 * Configuración (Asíncrono)
 */
app.component(
    "ConfigAdminSaludComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/configAdminSaludComponent.vue")
    )
);
app.component(
    "ConfigDiagnosticoComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/configDiagnosticoComponent.vue")
    )
);
app.component(
    "ConfigPrioridadComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/configPrioridadComponent.vue")
    )
);
app.component(
    "ConfigProductoComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/configProductoComponent.vue")
    )
);
app.component(
    "ConfigSedeComponent",
    defineAsyncComponent(() => import("./components/modulos/configuracion/configSedeComponent.vue"))
);
app.component(
    "ConfigWhatsappComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/configWhatsappComponent.vue")
    )
);
app.component(
    "ConfigCitaRechazoMotivoComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/configCitaRechazoMotivoComponent.vue")
    )
);
app.component(
    "ConfigServicioCitaComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/configServicioCitaComponent.vue")
    )
);
app.component(
    "ConfigServicioProductoComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/configServicioProductoComponent.vue")
    )
);
app.component(
    "ConfigTipoPlantillaComponent",
    defineAsyncComponent(() => import("./components/modulos/configuracion/configTipoPlantilla.vue"))
);
app.component(
    "ConfigPlantillaComponent",
    defineAsyncComponent(() => import("./components/modulos/configuracion/configPlantilla.vue"))
);
app.component(
    "ConfigTipoDocumentoComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/configTipoDocumentoComponent.vue")
    )
);
app.component(
    "ConfigConsentimientoInformadosComponent",
    defineAsyncComponent(
        () =>
            import("./components/modulos/configuracion/configConsentimientoInformadoComponent.vue")
    )
);

/**
 * Estudios (Asíncrono)
 */
app.component(
    "AsignarEstudioComponent",
    defineAsyncComponent(() => import("./components/modulos/estudios/AsignarEstudioComponent.vue"))
);
app.component(
    "LeerEstudioComponent",
    defineAsyncComponent(() => import("./components/modulos/estudios/leerEstudioComponent.vue"))
);
app.component(
    "TranscribirEstudioComponent",
    defineAsyncComponent(
        () => import("./components/modulos/estudios/transcribirEstudioComponent.vue")
    )
);
app.component(
    "NotificarEstudioComponent",
    defineAsyncComponent(
        () => import("./components/modulos/estudios/notificarEstudioComponent.vue")
    )
);
app.component(
    "EstudioAsignadosComponent",
    defineAsyncComponent(
        () => import("./components/modulos/estudios/estudioAsignadosComponent.vue")
    )
);

/**
 * Tele Radiología (Asíncrono)
 */
app.component(
    "TeleRadiologiaEnviarEstudioComponent",
    defineAsyncComponent(
        () =>
            import("./components/modulos/tele-radiologia/teleRadiologiaEnviarEstudioComponent.vue")
    )
);
app.component(
    "TeleRadiologiaTranscribirComponent",
    defineAsyncComponent(
        () => import("./components/modulos/tele-radiologia/teleRadiologiaTranscibirComponent.vue")
    )
);

/**
 * Ecografías (Asíncrono)
 */
app.component(
    "EcografiasAdmisionesComponent",
    defineAsyncComponent(() => import("./components/modulos/ecografias/admisionarComponent.vue"))
);
app.component(
    "EcografiasLecturaComponent",
    defineAsyncComponent(() => import("./components/modulos/ecografias/lecturaComponent.vue"))
);
app.component(
    "EcografiasAdmisionadasComponent",
    defineAsyncComponent(
        () => import("./components/modulos/ecografias/ecografiasAdmisionadasComponent.vue")
    )
);
app.component(
    "EcografiaFirmaConsentimientoComponent",
    defineAsyncComponent(
        () => import("./components/modulos/ecografias/ecografiaFirmaConsentimientoComponent.vue")
    )
);
app.component(
    "EcografiasConsentimientosComponent",
    defineAsyncComponent(
        () => import("./components/modulos/ecografias/ecografiasConsentimientosComponent.vue")
    )
);
app.component(
    "EcografiasConsentimientosPlantillasComponent",
    defineAsyncComponent(
        () =>
            import("./components/modulos/ecografias/ecografiasConsentimientosPlantillasComponent.vue")
    )
);

/**
 * Citas (Asíncrono)
 */
app.component(
    "CitasComponent",
    defineAsyncComponent(() => import("./components/modulos/citas/citasComponent.vue"))
);
app.component(
    "GestionCitasComponent",
    defineAsyncComponent(() => import("./components/modulos/citas/gestionCitasComponent.vue"))
);
app.component(
    "CitasRevisionComponent",
    defineAsyncComponent(() => import("./components/modulos/citas/citasRevisionComponent.vue"))
);
app.component(
    "CitasNotificationBadgeComponent",
    defineAsyncComponent(
        () => import("./components/modulos/citas/citasNotificationBadgeComponent.vue")
    )
);
app.component(
    "RecomendacionProductoComponent",
    defineAsyncComponent(
        () => import("./components/modulos/citas/recomendacionProductoComponent.vue")
    )
);

/**
 * Agenda (Asíncrono)
 */
const AgendaComponent = defineAsyncComponent(
    () => import("./components/modulos/agenda/agendaDisponibilidadComponent.vue")
);
app.component("AgendaDisponibilidadComponent", AgendaComponent);
app.component("AgendaComponent", AgendaComponent);

/**
 * Seguridad (Asíncrono)
 */
app.component(
    "UsuarioComponent",
    defineAsyncComponent(() => import("./components/modulos/seguridad/usuarioComponent.vue"))
);
const UsuarioPerfilComponent = defineAsyncComponent(
    () => import("./components/modulos/seguridad/usuarioPerfil.vue")
);
app.component("UsuarioPerfilComponent", UsuarioPerfilComponent);
app.component("SeguridadPerfil", UsuarioPerfilComponent);

app.component(
    "RoleComponente",
    defineAsyncComponent(() => import("./components/modulos/seguridad/roleComponente.vue"))
);
app.component(
    "AuditLogComponent",
    defineAsyncComponent(() => import("./components/modulos/seguridad/auditLogComponent.vue"))
);
app.component(
    "BackupMonitorComponent",
    defineAsyncComponent(() => import("./components/modulos/seguridad/backupMonitorComponent.vue"))
);
app.component(
    "SeguridadTokensComponent",
    defineAsyncComponent(
        () => import("./components/modulos/seguridad/seguridadTokensComponent.vue")
    )
);

/**
 * Reportes (Asíncrono)
 */
app.component(
    "ReportesProduccionComponent",
    defineAsyncComponent(
        () => import("./components/modulos/reportes/reporteProduccionComponent.vue")
    )
);
app.component(
    "ReportesEstudiosPACComponent",
    defineAsyncComponent(
        () => import("./components/modulos/reportes/reporteEstudiosPACComponent.vue")
    )
);
app.component(
    "ReportesEstudiosPacComponent",
    defineAsyncComponent(
        () => import("./components/modulos/reportes/reporteEstudiosPACComponent.vue")
    )
);

/**
 * Helpdesk (Asíncrono)
 */
app.component(
    "HelpdeskTicketsComponent",
    defineAsyncComponent(
        () => import("./components/modulos/help-desk/helpdeskTicketsComponent.vue")
    )
);
app.component(
    "HelpdeskCrearTicketComponent",
    defineAsyncComponent(
        () => import("./components/modulos/help-desk/helpdeskCrearTicketComponent.vue")
    )
);
app.component(
    "HelpdeskDetalleTicketComponent",
    defineAsyncComponent(
        () => import("./components/modulos/help-desk/helpdeskDetalleTicketComponent.vue")
    )
);
app.component(
    "HelpdeskNotificacionesComponent",
    defineAsyncComponent(
        () => import("./components/modulos/help-desk/helpdeskNotificacionesComponent.vue")
    )
);
app.component(
    "HelpdeskReportesComponent",
    defineAsyncComponent(
        () => import("./components/modulos/help-desk/helpdeskReportesComponent.vue")
    )
);

/**
 * Contratación y Liquidación (Asíncrono)
 */
app.component(
    "ContratoComponent",
    defineAsyncComponent(() => import("./components/modulos/contratos/contratoComponent.vue"))
);
app.component(
    "LiquidacionComponent",
    defineAsyncComponent(() => import("./components/modulos/liquidacion/liquidacionComponent.vue"))
);
app.component(
    "ProductividadComponent",
    defineAsyncComponent(
        () => import("./components/modulos/liquidacion/productividadComponent.vue")
    )
);

/**
 * Configuración Helpdesk (Asíncrono)
 */
app.component(
    "HelpdeskConfigCategoriasComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/helpdeskConfigCategoriasComponent.vue")
    )
);
app.component(
    "HelpdeskConfigPrioridadesComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/helpdeskConfigPrioridadesComponent.vue")
    )
);
app.component(
    "HelpdeskConfigEstadosComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/helpdeskConfigEstadosComponent.vue")
    )
);

/**
 * Dosimetría y Control de Radiación (Resolución 482) (Asíncrono)
 */
app.component(
    "DashboardDosimetria",
    defineAsyncComponent(() => import("./components/modulos/dosimetria/DashboardDosimetria.vue"))
);

/**
 * Componentes propios de LIDiag: Consentimientos (módulo genérico), logo/membrete por sede y otras plataformas (Asíncrono)
 */
app.component(
    "ConfigSedeLogoComponent",
    defineAsyncComponent(
        () => import("./components/modulos/configuracion/configSedeLogoComponent.vue")
    )
);
app.component(
    "ConsentimientoFirmaComponent",
    defineAsyncComponent(
        () => import("./components/modulos/consentimientos/consentimientoFirmaComponent.vue")
    )
);
app.component(
    "ConsentimientosGenerarComponent",
    defineAsyncComponent(
        () => import("./components/modulos/consentimientos/consentimientosGenerarComponent.vue")
    )
);
app.component(
    "ConsentimientoMarcasCanvas",
    defineAsyncComponent(
        () => import("./components/modulos/consentimientos/consentimientoMarcasCanvas.vue")
    )
);
app.component(
    "ConsentimientosMedicoComponent",
    defineAsyncComponent(
        () => import("./components/modulos/consentimientos/consentimientosMedicoComponent.vue")
    )
);
app.component(
    "ConsentimientosPlantillasComponent",
    defineAsyncComponent(
        () => import("./components/modulos/consentimientos/consentimientosPlantillasComponent.vue")
    )
);
app.component(
    "ConsentimientosDashboardComponent",
    defineAsyncComponent(
        () => import("./components/modulos/consentimientos/consentimientosDashboardComponent.vue")
    )
);
app.component(
    "OtrasPlataformasAsignarEstudioComponent",
    defineAsyncComponent(
        () =>
            import("./components/modulos/otras-plataformas/OtrasPlataformasAsignarEstudioComponent.vue")
    )
);

app.use(router);

app.mount("#app");
