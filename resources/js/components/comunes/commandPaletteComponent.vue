<template>
    <div>
        <!-- Disparador en Header (Lupa + Atajo Ctrl+K) -->
        <button
            type="button"
            class="btn btn-sm btn-outline-secondary d-none d-md-flex align-items-center command-palette-trigger"
            @click="abrirPalette()"
            title="Buscar módulo o pantalla (Ctrl + K)"
        >
            <i class="fa fa-search mr-2"></i>
            <span class="mr-2">Buscar módulo...</span>
            <kbd class="palette-kbd">Ctrl K</kbd>
        </button>

        <!-- Botón Móvil (solo icono) -->
        <button
            type="button"
            class="btn btn-sm btn-link d-md-none text-muted p-2"
            @click="abrirPalette()"
            aria-label="Abrir buscador"
        >
            <i class="fa fa-search fa-lg"></i>
        </button>

        <!-- Modal Teleport / Backdrop de la Command Palette -->
        <teleport to="body">
            <transition name="fade">
                <div
                    v-if="modalVisible"
                    class="command-palette-backdrop"
                    @click.self="cerrarPalette()"
                    @keydown.esc="cerrarPalette()"
                    tabindex="-1"
                >
                    <div class="command-palette-modal">
                        <div class="command-palette-header">
                            <i class="fa fa-search command-palette-search-icon"></i>
                            <input
                                ref="inputBusqueda"
                                type="text"
                                class="command-palette-input"
                                placeholder="Escribe para buscar cualquier módulo, pantalla o reporte..."
                                v-model="termino"
                                @keydown.down.prevent="moverFoco(1)"
                                @keydown.up.prevent="moverFoco(-1)"
                                @keydown.enter.prevent="ejecutarSeleccionado()"
                                @keydown.esc.prevent="cerrarPalette()"
                            />
                            <button
                                type="button"
                                class="command-palette-close-btn"
                                @click="cerrarPalette()"
                                title="Cerrar (Esc)"
                            >
                                <kbd>ESC</kbd>
                            </button>
                        </div>

                        <!-- Lista de Resultados -->
                        <div class="command-palette-body">
                            <div
                                v-if="resultadosFiltrados.length === 0"
                                class="command-palette-empty"
                            >
                                <i class="fa fa-folder-open-o fa-2x mb-2 text-muted"></i>
                                <p class="mb-0">
                                    No se encontraron módulos coincidentes con "{{ termino }}".
                                </p>
                            </div>

                            <div v-else class="command-palette-list">
                                <div
                                    v-for="(item, index) in resultadosFiltrados"
                                    :key="item.url"
                                    class="command-palette-item"
                                    :class="{ 'is-selected': indiceSeleccionado === index }"
                                    @mouseenter="indiceSeleccionado = index"
                                    @click="navegarA(item)"
                                >
                                    <div class="item-icon-box">
                                        <i :class="item.icono || 'fa fa-link'"></i>
                                    </div>
                                    <div class="item-text-box">
                                        <div class="item-title">{{ item.titulo }}</div>
                                        <div class="item-category text-muted">
                                            {{ item.categoria }}
                                        </div>
                                    </div>
                                    <div class="item-action-hint">
                                        <i class="fa fa-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer con tips de navegación -->
                        <div class="command-palette-footer">
                            <span><kbd>↑</kbd> <kbd>↓</kbd> Navegar</span>
                            <span class="ml-3"><kbd>↵</kbd> Seleccionar</span>
                            <span class="ml-3"><kbd>ESC</kbd> Cerrar</span>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

defineProps({
    permisos: { type: Array, default: () => [] },
});

const modalVisible = ref(false);
const termino = ref("");
const indiceSeleccionado = ref(0);
const inputBusqueda = ref(null);

/**
 * Catálogo completo de módulos y pantallas accesibles en LIDiag
 */
const todosLosModulos = [
    // Dashboard
    {
        titulo: "Dashboard Principal",
        categoria: "Personal",
        url: "/dashboard",
        icono: "fa fa-tachometer",
        permiso: null,
    },

    // Pacientes
    {
        titulo: "Admisionar Paciente",
        categoria: "Pacientes",
        url: "/pacientes/admisionar",
        icono: "fa fa-user-plus",
        permiso: "gestion.pacientes.create",
    },
    {
        titulo: "Listar Pacientes",
        categoria: "Pacientes",
        url: "/pacientes/listar",
        icono: "fa fa-users",
        permiso: "gestion.pacientes.index",
    },

    // Estudios
    {
        titulo: "Asignar Estudios (RX, TAC, RM)",
        categoria: "Estudios",
        url: "/estudios.asignar.listar",
        icono: "fa fa-upload",
        permiso: "estudio.asignar",
    },
    {
        titulo: "Estudios Asignados",
        categoria: "Estudios",
        url: "/estudios.asignados.listar",
        icono: "fa fa-check",
        permiso: "estudio.asignar",
    },
    {
        titulo: "Pendientes por Transcribir",
        categoria: "Estudios",
        url: "/estudios.transcribir.listar",
        icono: "fa fa-keyboard-o",
        permiso: "estudio.transcribir",
    },
    {
        titulo: "Estación de Lectura de Estudios",
        categoria: "Estudios",
        url: "/estudios.leer.estudio.listar",
        icono: "fa fa-stethoscope",
        permiso: "estudio.leerEstudio",
    },
    {
        titulo: "Notificar Estudio (WhatsApp / Email)",
        categoria: "Estudios",
        url: "/estudios.notificar.index",
        icono: "fa fa-envelope-o",
        permiso: "estudios.notificar.index",
    },

    // Tele-Radiología
    {
        titulo: "Enviar Estudios a Tele-Radiología",
        categoria: "Tele-Radiología",
        url: "/teleradiologia.index",
        icono: "fa fa-cloud-upload",
        permiso: "tele.ragiologia.index",
    },
    {
        titulo: "Transcribir Tele-Radiología",
        categoria: "Tele-Radiología",
        url: "/teleradiologia.transcribir",
        icono: "fa fa-file-text-o",
        permiso: "tele.ragiologia.transcribir",
    },

    // Ecografías
    {
        titulo: "Admisionar Ecografías y Procedimientos",
        categoria: "Ecografías",
        url: "/ecografias.admisiones",
        icono: "fa fa-address-book-o",
        permiso: "ecografias.admision",
    },
    {
        titulo: "Ecografías Admisionadas",
        categoria: "Ecografías",
        url: "/ecografias.adminisionadas",
        icono: "fa fa-list-alt",
        permiso: "ecografias.index",
    },
    {
        titulo: "Estación de Lectura de Ecografías",
        categoria: "Ecografías",
        url: "/ecografias-pendientes-lecturas",
        icono: "fa fa-heartbeat",
        permiso: "ecografias.leer",
    },
    {
        titulo: "Consentimientos Informados (Firmas/PDF)",
        categoria: "Ecografías",
        url: "/config-consentimientos-informados",
        icono: "fa fa-pencil-square-o",
        permiso: "ecografias.consentimientos.index",
    },

    // Citas y Agenda
    {
        titulo: "Solicitudes de Citas Pendientes",
        categoria: "Citas",
        url: "/citas/revision",
        icono: "fa fa-inbox",
        permiso: "citas.index",
    },
    {
        titulo: "Listado General de Citas",
        categoria: "Citas",
        url: "/citas",
        icono: "fa fa-calendar",
        permiso: "citas.index",
    },
    {
        titulo: "Tablero de Gestión de Citas",
        categoria: "Citas",
        url: "/citas/gestion",
        icono: "fa fa-th-large",
        permiso: "citas.index",
    },
    {
        titulo: "Recomendaciones de Citas",
        categoria: "Citas",
        url: "/citas/recomendaciones",
        icono: "fa fa-commenting-o",
        permiso: "citas.index",
    },
    {
        titulo: "Disponibilidad de Agenda",
        categoria: "Citas",
        url: "/agenda/disponibilidad",
        icono: "fa fa-clock-o",
        permiso: "agendamiento.disponibilidad.index",
    },

    // Reportes
    {
        titulo: "Reportes de Producción Médica",
        categoria: "Reportes",
        url: "/reportes.produccion",
        icono: "fa fa-bar-chart",
        permiso: "reportes.indicadores",
    },

    // Soporte / Helpdesk
    {
        titulo: "Mis Tickets de Helpdesk",
        categoria: "Helpdesk",
        url: "/helpdesk-tickets",
        icono: "fa fa-ticket",
        permiso: "helpdesk.tickets.index",
    },
    {
        titulo: "Crear Ticket de Soporte",
        categoria: "Helpdesk",
        url: "/helpdesk-tickets-crear",
        icono: "fa fa-plus-circle",
        permiso: "helpdesk.tickets.create",
    },
    {
        titulo: "Reportes de Helpdesk",
        categoria: "Helpdesk",
        url: "/helpdesk-reportes",
        icono: "fa fa-pie-chart",
        permiso: "helpdesk.reportes.index",
    },
    {
        titulo: "Notificaciones Helpdesk",
        categoria: "Helpdesk",
        url: "/helpdesk-notificaciones",
        icono: "fa fa-bell-o",
        permiso: "helpdesk.notificaciones.index",
    },

    // Contratación y Liquidación
    {
        titulo: "Contratos de Médicos y Tarifas",
        categoria: "Contratación",
        url: "/contratos",
        icono: "fa fa-handshake-o",
        permiso: "contratos.index",
    },
    {
        titulo: "Liquidación Médica",
        categoria: "Liquidación",
        url: "/liquidacion",
        icono: "fa fa-calculator",
        permiso: "liquidacion.index",
    },
    {
        titulo: "Productividad y Honorarios",
        categoria: "Liquidación",
        url: "/productividad",
        icono: "fa fa-line-chart",
        permiso: "liquidacion.index",
    },

    // Parametrización
    {
        titulo: "Administradoras de Salud (EPS)",
        categoria: "Configuración",
        url: "/config-adminsalud",
        icono: "fa fa-shield",
        permiso: "config.admin.salud.index",
    },
    {
        titulo: "Catálogo de Diagnósticos (CIE-10)",
        categoria: "Configuración",
        url: "/config-diagnosticos",
        icono: "fa fa-book",
        permiso: "config.diagnosticos.index",
    },
    {
        titulo: "Prioridades de Atención",
        categoria: "Configuración",
        url: "/config-prioridades",
        icono: "fa fa-flag",
        permiso: "config.prioridades.index",
    },
    {
        titulo: "Catálogo de Productos / Exámenes",
        categoria: "Configuración",
        url: "/config-productos",
        icono: "fa fa-cubes",
        permiso: "config.productos.index",
    },
    {
        titulo: "Sedes de Atención",
        categoria: "Configuración",
        url: "/config-sedes",
        icono: "fa fa-hospital-o",
        permiso: "config.sedes.index",
    },
    {
        titulo: "Servicios de Citas",
        categoria: "Configuración",
        url: "/config-servicio-citas",
        icono: "fa fa-tags",
        permiso: "config.servicio.citas.index",
    },
    {
        titulo: "Productos por Servicio",
        categoria: "Configuración",
        url: "/config-servicio-producto",
        icono: "fa fa-link",
        permiso: "config.servicio.citas.index",
    },
    {
        titulo: "Plantillas de Informes",
        categoria: "Configuración",
        url: "/config-plantillas",
        icono: "fa fa-file-text-o",
        permiso: "config.plantillas.index",
    },
    {
        titulo: "Tipos de Plantillas",
        categoria: "Configuración",
        url: "/config-tipos-plantillas",
        icono: "fa fa-folder-o",
        permiso: "config.plantillas.index",
    },
    {
        titulo: "Tipos de Documentos",
        categoria: "Configuración",
        url: "/config-tipos-documentos",
        icono: "fa fa-id-card-o",
        permiso: "config.tipo.documento.index",
    },
    {
        titulo: "Configuración WhatsApp Bot",
        categoria: "Configuración",
        url: "/config-whatsapp",
        icono: "fa fa-whatsapp",
        permiso: "config.whatsapp.saludo.index",
    },

    // Seguridad
    {
        titulo: "Gestión de Usuarios",
        categoria: "Seguridad",
        url: "/seguridad.usuarios",
        icono: "fa fa-users",
        permiso: "seguridad.usuarios.index",
    },
    {
        titulo: "Roles y Permisos",
        categoria: "Seguridad",
        url: "/seguridad.roles",
        icono: "fa fa-key",
        permiso: "seguridad.roles.index",
    },
    {
        titulo: "Auditoría del Sistema",
        categoria: "Seguridad",
        url: "/seguridad.auditoria",
        icono: "fa fa-history",
        permiso: "seguridad.auditoria.index",
    },
    {
        titulo: "Mi Perfil de Usuario",
        categoria: "Seguridad",
        url: "/seguridad.perfil",
        icono: "fa fa-user",
        permiso: null,
    },
];

/**
 * Filtra módulos accesibles según el término de búsqueda
 */
const resultadosFiltrados = computed(() => {
    const q = (termino.value || "").trim().toLowerCase();
    if (!q) return todosLosModulos.slice(0, 10);

    return todosLosModulos
        .filter((item) => {
            return (
                item.titulo.toLowerCase().includes(q) || item.categoria.toLowerCase().includes(q)
            );
        })
        .slice(0, 15);
});

function abrirPalette() {
    modalVisible.value = true;
    termino.value = "";
    indiceSeleccionado.value = 0;
    nextTick(() => {
        if (inputBusqueda.value) {
            inputBusqueda.value.focus();
        }
    });
}

function cerrarPalette() {
    modalVisible.value = false;
    termino.value = "";
}

function moverFoco(delta) {
    const total = resultadosFiltrados.value.length;
    if (total === 0) return;
    indiceSeleccionado.value = (indiceSeleccionado.value + delta + total) % total;
}

function ejecutarSeleccionado() {
    const seleccionado = resultadosFiltrados.value[indiceSeleccionado.value];
    if (seleccionado) {
        navegarA(seleccionado);
    }
}

function navegarA(item) {
    cerrarPalette();
    window.location.href = item.url;
}

function onGlobalKeydown(e) {
    // Detectar Ctrl+K o Cmd+K
    if ((e.ctrlKey || e.metaKey) && (e.key === "k" || e.key === "K")) {
        e.preventDefault();
        if (modalVisible.value) {
            cerrarPalette();
        } else {
            abrirPalette();
        }
    }
}

onMounted(() => {
    window.addEventListener("keydown", onGlobalKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener("keydown", onGlobalKeydown);
});
</script>

<style scoped>
.command-palette-trigger {
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 13px;
    background: rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0, 0, 0, 0.12);
    cursor: pointer;
    transition: all 0.2s ease;
}

.command-palette-trigger:hover {
    background: rgba(0, 0, 0, 0.08);
}

.palette-kbd {
    background: #e2e8f0;
    color: #475569;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-family: inherit;
    border: 1px solid #cbd5e1;
}

/* Modal Overlay */
.command-palette-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 10vh;
}

.command-palette-modal {
    width: 100%;
    max-width: 640px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

/* Header */
.command-palette-header {
    display: flex;
    align-items: center;
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
}

.command-palette-search-icon {
    font-size: 18px;
    color: #94a3b8;
    margin-right: 12px;
}

.command-palette-input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 16px;
    color: #1e293b;
    background: transparent;
}

.command-palette-close-btn {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
}

.command-palette-close-btn kbd {
    background: #f1f5f9;
    color: #64748b;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 11px;
}

/* Body / Results */
.command-palette-body {
    max-height: 380px;
    overflow-y: auto;
    padding: 8px;
}

.command-palette-empty {
    text-align: center;
    padding: 30px 15px;
    color: #64748b;
}

.command-palette-item {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.command-palette-item.is-selected {
    background-color: #f0f9ff;
}

.item-icon-box {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    margin-right: 12px;
}

.command-palette-item.is-selected .item-icon-box {
    background: #0284c7;
    color: #ffffff;
}

.item-text-box {
    flex: 1;
}

.item-title {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
}

.item-category {
    font-size: 12px;
}

.item-action-hint {
    color: #94a3b8;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.command-palette-item.is-selected .item-action-hint {
    opacity: 1;
    color: #0284c7;
}

/* Footer */
.command-palette-footer {
    display: flex;
    align-items: center;
    padding: 10px 18px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    font-size: 12px;
    color: #64748b;
}

.command-palette-footer kbd {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    padding: 1px 4px;
    border-radius: 3px;
    font-size: 10px;
}

/* Dark mode support */
:global(body.theme-dark) .command-palette-modal {
    background: #181a20;
    border-color: #2d3343;
    color: #e2e8f0;
}

:global(body.theme-dark) .command-palette-header {
    border-bottom-color: #2d3343;
}

:global(body.theme-dark) .command-palette-input {
    color: #ffffff;
}

:global(body.theme-dark) .command-palette-close-btn kbd {
    background: #222632;
    color: #94a3b8;
}

:global(body.theme-dark) .command-palette-item.is-selected {
    background-color: #222632;
}

:global(body.theme-dark) .item-title {
    color: #f8fafc;
}

:global(body.theme-dark) .item-icon-box {
    background: #222632;
    color: #38bdf8;
}

:global(body.theme-dark) .command-palette-item.is-selected .item-icon-box {
    background: #0284c7;
    color: #ffffff;
}

:global(body.theme-dark) .command-palette-footer {
    background: #14171f;
    border-top-color: #2d3343;
    color: #94a3b8;
}

:global(body.theme-dark) .command-palette-footer kbd {
    background: #222632;
    border-color: #3b4254;
    color: #cbd5e1;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
