/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import "./bootstrap";
import "../css/app.css";
import { createApp } from "vue";

import mitt from "mitt";

const emitter = mitt();

/**
 * Next, we will create a fresh Vue application instance. You may then begin
 * registering components with the application instance so they are ready
 * to use in your application's views. An example is included for you.
 */

const app = createApp({});
app.config.globalProperties.emitter = emitter;

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";

dayjs.extend(utc);
dayjs.extend(timezone);

// Opcional: Si deseas hacer dayjs globalmente disponible
window.dayjs = dayjs;

/**
 * Principales
 */

import dashboardComponent from "./components/dashboard/dashboardComponent.vue";
app.component("DashboardComponent", dashboardComponent);
import commandPaletteComponent from "./components/comunes/commandPaletteComponent.vue";
app.component("CommandPaletteComponent", commandPaletteComponent);
import emptyStateComponent from "./components/comunes/EmptyStateComponent.vue";
app.component("EmptyStateComponent", emptyStateComponent);
import tableSkeletonLoader from "./components/comunes/TableSkeletonLoader.vue";
app.component("TableSkeletonLoader", tableSkeletonLoader);

/**
 * Pacientes y Admisión
 */
import admisionComponent from "./components/modulos/pacientes/admisionComponent.vue";
app.component("AdmisionPacienteComponent", admisionComponent);
app.component("AdmisionPacienteComponent", admisionComponent);

/**
 * Cofiguración
 */
import configAdminSaludComponent from "./components/modulos/configuracion/configAdminSaludComponent.vue";
app.component("ConfigAdminSaludComponent", configAdminSaludComponent);
import configDiagnosticoComponent from "./components/modulos/configuracion/configDiagnosticoComponent.vue";
app.component("ConfigDiagnosticoComponent", configDiagnosticoComponent);
import configPrioridadComponent from "./components/modulos/configuracion/configPrioridadComponent.vue";
app.component("ConfigPrioridadComponent", configPrioridadComponent);
import configProductoComponent from "./components/modulos/configuracion/configProductoComponent.vue";
app.component("ConfigProductoComponent", configProductoComponent);
import configSedeComponent from "./components/modulos/configuracion/configSedeComponent.vue";
app.component("ConfigSedeComponent", configSedeComponent);
import configWhatsappComponent from "./components/modulos/configuracion/configWhatsappComponent.vue";
app.component("ConfigWhatsappComponent", configWhatsappComponent);
import configCitaRechazoMotivoComponent from "./components/modulos/configuracion/configCitaRechazoMotivoComponent.vue";
app.component("ConfigCitaRechazoMotivoComponent", configCitaRechazoMotivoComponent);
import configServicioCitaComponent from "./components/modulos/configuracion/configServicioCitaComponent.vue";
app.component("ConfigServicioCitaComponent", configServicioCitaComponent);
import configServicioProductoComponent from "./components/modulos/configuracion/configServicioProductoComponent.vue";
app.component("ConfigServicioProductoComponent", configServicioProductoComponent);
import configTipoPlantilla from "./components/modulos/configuracion/configTipoPlantilla.vue";
app.component("ConfigTipoPlantillaComponent", configTipoPlantilla);
import configPlantilla from "./components/modulos/configuracion/configPlantilla.vue";
app.component("ConfigPlantillaComponent", configPlantilla);
import configTipoDocumento from "./components/modulos/configuracion/configTipoDocumentoComponent.vue";
app.component("ConfigTipoDocumentoComponent", configTipoDocumento);
import configConsentimientoInformadoComponent from "./components/modulos/configuracion/configConsentimientoInformadoComponent.vue";
app.component("ConfigConsentimientoInformadosComponent", configConsentimientoInformadoComponent);
// Alias en kebab-case (Vue resuelve tags en minúscula en DOM)

/**
 * Estudios
 */
import AsignarEstudioComponent from "./components/modulos/estudios/AsignarEstudioComponent.vue";
app.component("AsignarEstudioComponent", AsignarEstudioComponent);
import leerEstudioComponent from "./components/modulos/estudios/leerEstudioComponent.vue";
app.component("LeerEstudioComponent", leerEstudioComponent);

import transcribirEstudioComponent from "./components/modulos/estudios/transcribirEstudioComponent.vue";
app.component("TranscribirEstudioComponent", transcribirEstudioComponent);
import notificarEstudioComponent from "./components/modulos/estudios/notificarEstudioComponent.vue";
app.component("NotificarEstudioComponent", notificarEstudioComponent);
import estudioAsignadosComponent from "./components/modulos/estudios/estudioAsignadosComponent.vue";
app.component("EstudioAsignadosComponent", estudioAsignadosComponent);

/**
 * Tele Radiologia
 */
import teleRadiologiaEnviarEstudioComponent from "./components/modulos/tele-radiologia/teleRadiologiaEnviarEstudioComponent.vue";
app.component("TeleRadiologiaEnviarEstudioComponent", teleRadiologiaEnviarEstudioComponent);
import teleRadiologiaTranscibirComponent from "./components/modulos/tele-radiologia/teleRadiologiaTranscibirComponent.vue";
app.component("TeleRadiologiaTranscribirComponent", teleRadiologiaTranscibirComponent);

/**
 * Ecografias
 */
import ecogradifasAdmisionesComponent from "./components/modulos/ecografias/admisionarComponent.vue";
app.component("EcografiasAdmisionesComponent", ecogradifasAdmisionesComponent);
import lecturaComponent from "./components/modulos/ecografias/lecturaComponent.vue";
app.component("EcografiasLecturaComponent", lecturaComponent);
import ecografiasAdmisionadasComponent from "./components/modulos/ecografias/ecografiasAdmisionadasComponent.vue";
app.component("EcografiasAdmisionadasComponent", ecografiasAdmisionadasComponent);
import ecografiaFirmaConsentimientoComponent from "./components/modulos/ecografias/ecografiaFirmaConsentimientoComponent.vue";
app.component("EcografiaFirmaConsentimientoComponent", ecografiaFirmaConsentimientoComponent);
import ecografiasConsentimientosComponent from "./components/modulos/ecografias/ecografiasConsentimientosComponent.vue";
app.component("EcografiasConsentimientosComponent", ecografiasConsentimientosComponent);
import ecografiasConsentimientosPlantillasComponent from "./components/modulos/ecografias/ecografiasConsentimientosPlantillasComponent.vue";
app.component(
    "EcografiasConsentimientosPlantillasComponent",
    ecografiasConsentimientosPlantillasComponent
);

/**
 * Citas
 */
import citasComponent from "./components/modulos/citas/citasComponent.vue";
app.component("CitasComponent", citasComponent);
import gestionCitasComponent from "./components/modulos/citas/gestionCitasComponent.vue";
app.component("GestionCitasComponent", gestionCitasComponent);
import citasRevisionComponent from "./components/modulos/citas/citasRevisionComponent.vue";
app.component("CitasRevisionComponent", citasRevisionComponent);
import citasNotificationBadgeComponent from "./components/modulos/citas/citasNotificationBadgeComponent.vue";
app.component("CitasNotificationBadgeComponent", citasNotificationBadgeComponent);
import recomendacionProductoComponent from "./components/modulos/citas/recomendacionProductoComponent.vue";
app.component("RecomendacionProductoComponent", recomendacionProductoComponent);

/**
 * Agenda
 */
import agendaDisponibilidadComponent from "./components/modulos/agenda/agendaDisponibilidadComponent.vue";
app.component("AgendaDisponibilidadComponent", agendaDisponibilidadComponent);
// Alias para compatibilidad con Blade antiguo (`resources/views/modulos/agenda/index.blade.php`)
app.component("AgendaComponent", agendaDisponibilidadComponent);

/**
 * Seguridad
 */
import usuarioComponent from "./components/modulos/seguridad/usuarioComponent.vue";
app.component("UsuarioComponent", usuarioComponent);
import usuarioPerfil from "./components/modulos/seguridad/usuarioPerfil.vue";
app.component("UsuarioPerfilComponent", usuarioPerfil);
// Alias para compatibilidad con Blade (`resources/views/modulos/seguridad/perfil.blade.php`)
app.component("SeguridadPerfil", usuarioPerfil);
import roleComponente from "./components/modulos/seguridad/roleComponente.vue";
app.component("RoleComponente", roleComponente);
import auditLogComponent from "./components/modulos/seguridad/auditLogComponent.vue";
app.component("AuditLogComponent", auditLogComponent);
import backupMonitorComponent from "./components/modulos/seguridad/backupMonitorComponent.vue";
app.component("BackupMonitorComponent", backupMonitorComponent);

/**
 * Reportes
 */
import reporteProduccionComponent from "./components/modulos/reportes/reporteProduccionComponent.vue";
app.component("ReportesProduccionComponent", reporteProduccionComponent);

/**
 * Helpdesk
 */
import helpdeskTicketsComponent from "./components/modulos/help-desk/helpdeskTicketsComponent.vue";
app.component("HelpdeskTicketsComponent", helpdeskTicketsComponent);
import helpdeskCrearTicketComponent from "./components/modulos/help-desk/helpdeskCrearTicketComponent.vue";
app.component("HelpdeskCrearTicketComponent", helpdeskCrearTicketComponent);
import helpdeskDetalleTicketComponent from "./components/modulos/help-desk/helpdeskDetalleTicketComponent.vue";
app.component("HelpdeskDetalleTicketComponent", helpdeskDetalleTicketComponent);
import helpdeskNotificacionesComponent from "./components/modulos/help-desk/helpdeskNotificacionesComponent.vue";
app.component("HelpdeskNotificacionesComponent", helpdeskNotificacionesComponent);
import helpdeskReportesComponent from "./components/modulos/help-desk/helpdeskReportesComponent.vue";
app.component("HelpdeskReportesComponent", helpdeskReportesComponent);

/**
 * Contratación
 */
import contratoComponent from "./components/modulos/contratos/contratoComponent.vue";
app.component("ContratoComponent", contratoComponent);

import liquidacionComponent from "./components/modulos/liquidacion/liquidacionComponent.vue";
app.component("LiquidacionComponent", liquidacionComponent);
import productividadComponent from "./components/modulos/liquidacion/productividadComponent.vue";
app.component("ProductividadComponent", productividadComponent);

/**
 * Configuración Helpdesk
 */
import helpdeskConfigCategoriasComponent from "./components/modulos/configuracion/helpdeskConfigCategoriasComponent.vue";
app.component("HelpdeskConfigCategoriasComponent", helpdeskConfigCategoriasComponent);
import helpdeskConfigPrioridadesComponent from "./components/modulos/configuracion/helpdeskConfigPrioridadesComponent.vue";
app.component("HelpdeskConfigPrioridadesComponent", helpdeskConfigPrioridadesComponent);
import helpdeskConfigEstadosComponent from "./components/modulos/configuracion/helpdeskConfigEstadosComponent.vue";
app.component("HelpdeskConfigEstadosComponent", helpdeskConfigEstadosComponent);

/**
 * Comunes
 */
import userProfileAndSearchComponent from "./components/comunes/userProfileAndSearchComponent.vue";
app.component("UserProfileAndSearchComponent", userProfileAndSearchComponent);

import footerComponent from "./components/comunes/footerComponent.vue";
app.component("FooterComponent", footerComponent);

// Registro global de Select2 (v-select2-component)
import Select2 from "./components/shared/Select2.vue";
app.component("Select2", Select2);

/**
 * Dosimetría y Control de Radiación (Resolución 482)
 */
import dashboardDosimetria from "./components/modulos/dosimetria/DashboardDosimetria.vue";
app.component("DashboardDosimetria", dashboardDosimetria);

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// Object.entries(import.meta.glob('./**/*.vue', { eager: true })).forEach(([path, definition]) => {
//     app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
// });

/**
 * Finally, we will attach the application instance to a HTML element with
 * an "id" attribute of "app". This element is included with the "auth"
 * scaffolding. Otherwise, you will need to add an element yourself.
 */

/* import route from './routes'

app.use(route); */

// Importar archivo CSS antes de crear la aplicación Vue
app.mount("#app");
/* import "./admin-wrap/assets/node_modules/jquery/jquery.min.js";
import "./admin-wrap/assets/node_modules/calendar/jquery-ui.min.js";
import "./admin-wrap/assets/node_modules/calendar/dist/fullcalendar.css";
import "./admin-wrap/assets/node_modules/moment/moment.js";
import "./admin-wrap/assets/node_modules/calendar/dist/fullcalendar.min.js";
import "./admin-wrap/assets/node_modules/calendar/dist/cal-init.js"; */
