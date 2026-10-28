<template>
    <ul class="navbar-nav my-lg-0">
        <!-- ============================================================== -->
        <!-- Centro de Notificaciones Unificado (Citas & Helpdesk) -->
        <!-- ============================================================== -->
        <li class="nav-item dropdown" v-if="notificacionesDisponibles">
            <a
                class="nav-link dropdown-toggle waves-effect waves-dark"
                href=""
                id="unified_bell"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
                @click.prevent="onOpenNotificacionesDropdown()"
                title="Centro de Notificaciones"
            >
                <i class="icon-Bell"></i>
                <div class="notify" v-if="totalUnreadCount > 0">
                    <span class="heartbit"></span>
                    <span class="point"></span>
                </div>
            </a>
            <div
                class="dropdown-menu dropdown-menu-right mailbox animated bounceInDown"
                aria-labelledby="unified_bell"
                style="min-width: 360px; max-width: 400px; padding-bottom: 0"
            >
                <ul>
                    <li>
                        <div class="drop-title d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold">
                                Notificaciones
                                <span
                                    v-if="totalUnreadCount > 0"
                                    class="badge badge-pill badge-danger m-l-5"
                                    style="font-size: 11px"
                                >
                                    {{ totalUnreadCount }}
                                </span>
                            </span>
                            <div class="btn-group btn-group-sm" role="group">
                                <button
                                    type="button"
                                    class="btn"
                                    :class="tabActiva === 'todas' ? 'btn-info' : 'btn-secondary'"
                                    @click.prevent.stop="tabActiva = 'todas'"
                                    style="padding: 2px 8px; font-size: 11px"
                                >
                                    Todas
                                </button>
                                <button
                                    v-if="citasDisponible"
                                    type="button"
                                    class="btn"
                                    :class="tabActiva === 'citas' ? 'btn-info' : 'btn-secondary'"
                                    @click.prevent.stop="tabActiva = 'citas'"
                                    style="padding: 2px 8px; font-size: 11px"
                                >
                                    Citas
                                    <span
                                        v-if="citasUnreadCount > 0"
                                        class="badge badge-light text-dark m-l-5"
                                        style="font-size: 9px"
                                    >
                                        {{ citasUnreadCount }}
                                    </span>
                                </button>
                                <button
                                    v-if="hdDisponible"
                                    type="button"
                                    class="btn"
                                    :class="tabActiva === 'helpdesk' ? 'btn-info' : 'btn-secondary'"
                                    @click.prevent.stop="tabActiva = 'helpdesk'"
                                    style="padding: 2px 8px; font-size: 11px"
                                >
                                    Helpdesk
                                    <span
                                        v-if="hdUnreadCount > 0"
                                        class="badge badge-light text-dark m-l-5"
                                        style="font-size: 9px"
                                    >
                                        {{ hdUnreadCount }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="message-center" style="max-height: 320px; overflow-y: auto">
                            <div v-if="notifLoading" class="p-15 text-center text-muted">
                                <i class="fa fa-spinner fa-spin m-r-5"></i> Cargando
                                notificaciones...
                            </div>

                            <template v-else-if="notificacionesFiltradas.length > 0">
                                <a
                                    v-for="item in notificacionesFiltradas"
                                    :key="item.uniqueId"
                                    href="#"
                                    @click.prevent="abrirNotificacion(item)"
                                    class="d-flex align-items-center"
                                    :style="item.read ? 'opacity: 0.75;' : ''"
                                >
                                    <div
                                        class="btn btn-circle"
                                        :class="
                                            item.tipo === 'cita'
                                                ? item.read
                                                    ? 'btn-secondary'
                                                    : 'btn-success'
                                                : item.read
                                                  ? 'btn-secondary'
                                                  : 'btn-info'
                                        "
                                    >
                                        <i
                                            class="fa"
                                            :class="
                                                item.tipo === 'cita'
                                                    ? 'fa-calendar-check-o'
                                                    : item.read
                                                      ? 'fa-check'
                                                      : 'fa-ticket'
                                            "
                                        ></i>
                                    </div>
                                    <div class="mail-contnet" style="width: calc(100% - 50px)">
                                        <div
                                            class="d-flex justify-content-between align-items-center"
                                        >
                                            <h5
                                                class="m-0"
                                                style="
                                                    font-size: 12px;
                                                    max-width: 170px;
                                                    white-space: nowrap;
                                                    overflow: hidden;
                                                    text-overflow: ellipsis;
                                                "
                                            >
                                                {{ item.titulo }}
                                            </h5>
                                            <span
                                                class="badge"
                                                :class="
                                                    item.tipo === 'cita'
                                                        ? 'badge-success'
                                                        : 'badge-info'
                                                "
                                                style="font-size: 9px; padding: 2px 6px"
                                            >
                                                {{ item.tipo === "cita" ? "Cita" : "Ticket" }}
                                            </span>
                                        </div>
                                        <span
                                            class="mail-desc"
                                            style="
                                                font-size: 11px;
                                                white-space: nowrap;
                                                overflow: hidden;
                                                text-overflow: ellipsis;
                                            "
                                        >
                                            {{ item.descripcion }}
                                        </span>
                                        <span class="time" style="font-size: 10px">{{
                                            item.tiempoRelativo
                                        }}</span>
                                    </div>
                                </a>
                            </template>

                            <div v-else class="p-20 text-center text-muted" style="font-size: 12px">
                                <i
                                    class="fa fa-bell-slash-o d-block m-b-5"
                                    style="font-size: 20px"
                                ></i>
                                Sin notificaciones en esta categoría.
                            </div>
                        </div>
                    </li>
                    <li v-if="totalUnreadCount > 0">
                        <a
                            class="nav-link text-center"
                            href="#"
                            @click.prevent="marcarTodasComoLeidas()"
                        >
                            <strong>Marcar todas como leídas</strong>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- ============================================================== -->
        <!-- mega menu -->
        <!-- ============================================================== -->
        <li class="nav-item dropdown mega-dropdown d-flex align-items-center">
            <a
                class="nav-link dropdown-toggle waves-effect waves-dark d-flex align-items-center"
                href=""
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
                style="padding-top: 0; padding-bottom: 0"
                :title="
                    'Sede activa: ' +
                    nomSedeActiva +
                    (mostrarMedico ? ' | Médico: ' + nomMedicoActivo : '')
                "
            >
                <span
                    class="badge badge-info m-r-5 p-2 d-flex align-items-center"
                    style="font-size: 12px; font-weight: 500"
                >
                    <i class="fa fa-hospital-o m-r-5"></i>
                    <span>{{ nomSedeActiva }}</span>
                </span>
                <span
                    v-if="mostrarMedico"
                    class="badge badge-success m-r-5 p-2 d-flex align-items-center"
                    style="font-size: 12px; font-weight: 500"
                >
                    <i class="fa fa-user-md m-r-5"></i>
                    <span>{{ nomMedicoActivo }}</span>
                </span>
                <i class="icon-Box-Close" style="font-size: 18px"></i>
            </a>
            <div class="dropdown-menu animated bounceInDown">
                <ul class="mega-dropdown-menu row">
                    <li class="col-lg-3 m-b-30">
                        <!-- Contact -->
                        <form>
                            <div class="form-group">
                                <h4 class="m-b-20">Sedes</h4>
                                <select
                                    class="form-control custom-select"
                                    @change="estableserSede()"
                                    style="width: 100%; height: 36px"
                                    v-model="sedeSeleccionada"
                                    name="sedes"
                                >
                                    <option>Seleccione la sede</option>
                                    <option
                                        v-for="ItemSede in sedes"
                                        :key="ItemSede.id"
                                        :value="ItemSede.id"
                                    >
                                        {{ ItemSede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group" v-if="mostrarMedico">
                                <h4 class="m-b-20">Médico</h4>
                                <select
                                    class="form-control custom-select"
                                    @change="estableserMedico()"
                                    style="width: 100%; height: 36px"
                                    v-model="medicoSeleccionado"
                                    name="medicos"
                                >
                                    <option value="">Seleccione el médico</option>
                                    <option
                                        v-for="ItemMedico in medicos"
                                        :key="ItemMedico.id"
                                        :value="ItemMedico.id"
                                    >
                                        {{ ItemMedico.name }}
                                    </option>
                                </select>
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </li>

        <!-- ============================================================== -->
        <!-- Modo Oscuro / Claro Global -->
        <!-- ============================================================== -->
        <li class="nav-item d-flex align-items-center m-r-10">
            <button
                type="button"
                class="btn btn-sm btn-rounded waves-effect waves-light"
                :class="isDarkMode ? 'btn-warning' : 'btn-outline-secondary'"
                @click="toggleGlobalDarkMode()"
                :title="isDarkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                style="padding: 5px 12px; font-size: 13px; font-weight: 500"
            >
                <i :class="isDarkMode ? 'fa fa-sun-o' : 'fa fa-moon-o'"></i>
                <span class="hidden-sm-down m-l-5">{{
                    isDarkMode ? "Modo Claro" : "Modo Oscuro"
                }}</span>
            </button>
        </li>

        <!-- ============================================================== -->
        <!-- Profile -->
        <!-- ============================================================== -->
        <li class="nav-item dropdown u-pro">
            <a
                class="nav-link dropdown-toggle waves-effect waves-dark profile-pic"
                href=""
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <img :src="'/images/users/1.jpg'" alt="user" class="" />
                <span class="hidden-md-down">
                    &nbsp; {{ usuarioactual.name }}
                    <i class="fa fa-angle-down"></i>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right animated flipInY">
                <ul class="dropdown-user">
                    <li>
                        <div class="dw-user-box">
                            <div class="u-img">
                                <img :src="'/images/users/1.jpg'" alt="user" />
                            </div>
                            <div class="u-text">
                                <h4>{{ usuarioactual.name }}</h4>
                                <p class="text-muted">{{ usuarioactual.email }}</p>
                                <!-- <router-link class="btn btn-rounded btn-danger btn-sm" :to="{
                                    name: 'seguridad.usuario.perfil', params: { id: usuarioactual.id }
                                }">
                                    Ver Perfil
                                </router-link> -->
                            </div>
                        </div>
                    </li>
                    <li role="separator" class="divider"></li>
                    <li>
                        <a :href="perfilUrl || '/seguridad.perfil'">
                            <i class="ti-user"></i> Mi Perfil
                        </a>
                    </li>
                    <li role="separator" class="divider"></li>
                    <li>
                        <a href="#"> <i class="ti-settings"></i> Configuración de cuenta </a>
                    </li>
                    <li role="separator" class="divider"></li>
                    <li>
                        <a href="#" @click.prevent="Logout()">
                            <i class="fa fa-power-off"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</template>
<script setup>
import { computed, getCurrentInstance, onBeforeUnmount, onMounted, ref } from "vue";

import axios from "axios";

defineProps({
    usuarioactual: { type: Object, default: null },
    perfilUrl: { type: String, default: "" },
});

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

const sedes = ref([]);
const sedeSeleccionada = ref(sessionStorage.getItem("ST-sede"));
const medicoSeleccionado = ref(sessionStorage.getItem("ST-medico"));
const medicos = ref([]);
const nomMedicoSeleccionado = ref(sessionStorage.getItem("ST-nomMedico") || null);
const mostrarMedico = ref(false);

const nomSedeActiva = computed(() => {
    if (!sedeSeleccionada.value) return "Sin Sede";
    const encontrada = (sedes.value || []).find(
        (s) => String(s.id) === String(sedeSeleccionada.value)
    );
    return encontrada
        ? encontrada.nom_sede
        : sessionStorage.getItem("ST-nomsede") || "Sede Seleccionada";
});

const nomMedicoActivo = computed(() => {
    if (!medicoSeleccionado.value) return "Sin Médico";
    const encontrado = (medicos.value || []).find(
        (m) => String(m.id) === String(medicoSeleccionado.value)
    );
    return encontrado ? encontrado.name : nomMedicoSeleccionado.value || "Médico Seleccionado";
});

// Estado de notificaciones (Helpdesk + Citas/WhatsApp)
const tabActiva = ref("todas");
const notifLoading = ref(false);
const notifIntervalId = ref(null);

const hdDisponible = ref(true);
const hdItems = ref([]);

const citasDisponible = ref(true);
const citasItems = ref([]);
const citasCount = ref(0);

const hdUnreadCount = computed(() => {
    return (hdItems.value || []).filter((x) => !x.read_at).length;
});

const citasUnreadCount = computed(() => {
    return citasCount.value;
});

const totalUnreadCount = computed(() => {
    return hdUnreadCount.value + citasUnreadCount.value;
});

const notificacionesDisponibles = computed(() => {
    return hdDisponible.value || citasDisponible.value;
});

const notificacionesFiltradas = computed(() => {
    const lista = [];

    // Citas / WhatsApp
    if (citasDisponible.value && (tabActiva.value === "todas" || tabActiva.value === "citas")) {
        (citasItems.value || []).forEach((c) => {
            lista.push({
                uniqueId: `cita-${c.id}`,
                id: c.id,
                tipo: "cita",
                titulo: "Solicitud de Cita",
                descripcion: c.mensaje || "Nueva cita recibida por WhatsApp",
                tiempoRelativo: c.created_at || "Reciente",
                url: c.url || "/citas/revision",
                read: false,
                raw: c,
            });
        });
    }

    // Helpdesk
    if (hdDisponible.value && (tabActiva.value === "todas" || tabActiva.value === "helpdesk")) {
        (hdItems.value || []).forEach((h) => {
            lista.push({
                uniqueId: `hd-${h.id}`,
                id: h.id,
                tipo: "helpdesk",
                titulo: h.data?.folio ? `Ticket ${h.data.folio}` : "Ticket Helpdesk",
                descripcion: h.data?.mensaje || h.data?.titulo || "Actualización de ticket",
                tiempoRelativo: formatHdTime(h.created_at),
                url: h.data?.ticket_id
                    ? `/helpdesk-tickets/${h.data.ticket_id}`
                    : "/helpdesk-notificaciones",
                read: Boolean(h.read_at),
                raw: h,
            });
        });
    }

    return lista;
});

const isDarkMode = ref(localStorage.getItem("lidiag_dark_mode") === "true");

function applyGlobalTheme(dark) {
    if (dark) {
        document.body.classList.add("theme-dark");
        document.documentElement.classList.add("theme-dark");
    } else {
        document.body.classList.remove("theme-dark");
        document.documentElement.classList.remove("theme-dark");
    }
}

function toggleGlobalDarkMode() {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem("lidiag_dark_mode", isDarkMode.value ? "true" : "false");
    applyGlobalTheme(isDarkMode.value);
    emitter.emit("lidiagThemeChanged", isDarkMode.value);
}

/**
 * Determina y almacena en sessionStorage el ID del módulo actual
 * basándose en la URL del navegador (ecografías=2, estudios=1).
 *
 * @returns {string} ID del módulo detectado.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
function establecerModuloActual() {
    try {
        const pathname = String(window?.location?.pathname || "").toLowerCase();
        let moduloId = sessionStorage.getItem("ST-modulo");
        if (moduloId) return String(moduloId);

        // Heurística del proyecto (mismos IDs que se ven en llamadas existentes):
        // - ecografías: 2
        // - estudios: 1
        if (pathname.includes("ecografia")) moduloId = "2";
        else if (pathname.includes("estudios") || pathname.includes("study")) moduloId = "1";
        else moduloId = "1";

        sessionStorage.setItem("ST-modulo", moduloId);
        return String(moduloId);
    } catch (_) {
        return "1";
    }
}
/**
 * Cierra la sesión: llama al logout del backend y purga TODOS los datos de
 * contexto del navegador (claves ST-* en sessionStorage y localStorage) para
 * que el siguiente usuario no herede sede/médico/visor del anterior.
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
async function Logout() {
    try {
        // Usa el logout estándar de Laravel UI (Auth::routes)
        await axios.post("/logout");
    } finally {
        // Purga total de claves propias en ambos storages
        const esPropia = (key) => key.startsWith("ST-");
        Object.keys(window.sessionStorage)
            .filter(esPropia)
            .forEach((key) => window.sessionStorage.removeItem(key));
        Object.keys(window.localStorage)
            .filter(esPropia)
            .forEach((key) => window.localStorage.removeItem(key));

        // Asegura salida al login aunque el response sea 204 sin body
        window.location.href = "/";
    }
}
/**
 * Obtiene la lista de sedes activas desde la API y las almacena en el reactive ref sedes.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
async function listarSedes() {
    try {
        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res.data.data;
    } catch (error) {
        console.log(error);
    }
}
/**
 * Obtiene la lista de médicos disponibles desde la API y los almacena en medicos.
 * Si solo hay un médico, lo selecciona automáticamente.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
async function listarMedicos() {
    try {
        const res = await axios.get(`/api/user.listarUsuarios/Medico/2`);
        medicos.value = res.data.data;
        if (medicos.value.length === 1 && !medicoSeleccionado.value) {
            medicoSeleccionado.value = medicos.value[0].id;
            estableserMedico();
        }
    } catch (error) {
        console.log(error);
    }
}
/**
 * Establece la sede seleccionada: guarda datos en sessionStorage,
 * obtiene detalles de la sede desde la API y emite el evento sedeSeleccionada.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
async function estableserSede() {
    if (!sedeSeleccionada.value) return;
    const res = await axios.get("/api/config-sedes/" + sedeSeleccionada.value + "/buscarPorId");

    const nomSedeSeleccionada = $('select[name="sedes"] option:selected').text();

    sessionStorage.setItem("ST-sede", sedeSeleccionada.value);
    sessionStorage.setItem("ST-nomsede", nomSedeSeleccionada);
    sessionStorage.setItem("ST-urlsede", res.data.url_oviyam);
    sessionStorage.setItem("ST-servername", res.data.tap_oviyam);
    sessionStorage.setItem("ST-email_repor", res.data.email_repor || "");

    emitter.emit("sedeSeleccionada", {
        idSede: sedeSeleccionada.value,
        nomSede: nomSedeSeleccionada,
        url: res.data.url_oviyam,
        serverName: res.data.tap_oviyam,
        email_repor: res.data.email_repor,
    });
}
/**
 * Establece el médico seleccionado: guarda datos en sessionStorage y localStorage,
 * y emite el evento medicoSeleccionado para notificar a otros componentes.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
async function estableserMedico() {
    nomMedicoSeleccionado.value = $('select[name="medicos"] option:selected').text();

    sessionStorage.setItem("ST-medico", medicoSeleccionado.value);
    sessionStorage.setItem("ST-nomMedico", nomMedicoSeleccionado.value);

    localStorage.setItem("ST-ecografia-medico", medicoSeleccionado.value);
    localStorage.setItem("ST-ecografia-nomMedico", nomMedicoSeleccionado.value);

    emitter.emit("medicoSeleccionado", {
        idMedico: medicoSeleccionado.value,
        nomMedico: nomMedicoSeleccionado.value,
    });
}
/**
 * Maneja la apertura del dropdown de notificaciones unificado,
 * recargando las notificaciones de Helpdesk y Citas de forma silenciosa.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
function onOpenNotificacionesDropdown() {
    cargarTodasNotificaciones(true);
}

/**
 * Formatea una fecha/hora de notificación del helpdesk a zona horaria de Bogotá.
 *
 * @param {string} dt - Fecha en formato ISO.
 * @returns {string} Fecha formateada como 'YYYY-MM-DD HH:mm' o el valor original si falla.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
function formatHdTime(dt) {
    if (!dt) return "";
    try {
        return window.dayjs(dt).tz("America/Bogota").format("YYYY-MM-DD HH:mm");
    } catch (_) {
        return String(dt);
    }
}

/**
 * Carga las notificaciones de Helpdesk desde la API.
 * Si el endpoint retorna 403 o 404, desactiva la sección de helpdesk.
 *
 * @param {boolean} silent - Si es true, no activa spinner visual grande.
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
async function cargarHdNotificaciones(silent = false) {
    if (!hdDisponible.value) return;
    try {
        const res = await axios.get("/api/helpdesk-notificaciones", {
            params: { limit: 10 },
        });
        hdItems.value = res.data?.items || [];
    } catch (error) {
        const status = error?.response?.status;
        if (status === 403 || status === 404) {
            hdDisponible.value = false;
        } else if (!silent) {
            // noop
        }
    }
}

/**
 * Carga las notificaciones de Citas/WhatsApp pendientes desde la API.
 * Si el endpoint retorna 403 o 404, desactiva la sección de citas.
 *
 * @param {boolean} silent - Si es true, no activa spinner visual grande.
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
async function cargarCitasNotificaciones(silent = false) {
    if (!citasDisponible.value) return;
    try {
        const res = await axios.get("/api/citas/notificaciones/no-leidas");
        const data = res.data?.data || res.data || {};
        citasCount.value = Number(data.count || 0);
        citasItems.value = Array.isArray(data.notifications) ? data.notifications : [];
    } catch (error) {
        const status = error?.response?.status;
        if (status === 403 || status === 404) {
            citasDisponible.value = false;
        } else if (!silent) {
            // noop
        }
    }
}

/**
 * Orquestador para cargar simultáneamente las notificaciones de Helpdesk y Citas.
 *
 * @param {boolean} silent - Si es true, no muestra spinner bloqueante.
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
async function cargarTodasNotificaciones(silent = false) {
    if (notifLoading.value) return;
    if (!silent) notifLoading.value = true;
    try {
        await Promise.allSettled([
            cargarHdNotificaciones(silent),
            cargarCitasNotificaciones(silent),
        ]);
    } finally {
        if (!silent) notifLoading.value = false;
    }
}

/**
 * Marca una notificación individual del helpdesk como leída.
 *
 * @param {Object} n - Objeto notificación con propiedad id.
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
async function marcarHdLeida(n) {
    try {
        await axios.post(`/api/helpdesk-notificaciones/${n.id}/read`);
        n.read_at = new Date().toISOString();
    } catch {}
}

/**
 * Marca una notificación individual de citas como leída.
 *
 * @param {Object} n - Objeto notificación con propiedad id.
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
async function marcarCitaLeida(n) {
    try {
        await axios.post(`/api/citas/notificaciones/${n.id}/leida`);
        citasCount.value = Math.max(0, citasCount.value - 1);
        citasItems.value = citasItems.value.filter((item) => item.id !== n.id);
    } catch {}
}

/**
 * Abre una notificación (Cita o Helpdesk): la marca como leída y redirige a su destino.
 *
 * @param {Object} item - Notificación normalizada.
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
async function abrirNotificacion(item) {
    if (item.tipo === "cita") {
        await marcarCitaLeida(item);
        window.location.href = item.url || "/citas/revision";
    } else {
        if (!item.read) await marcarHdLeida(item.raw);
        window.location.href = item.url || "/helpdesk-notificaciones";
    }
}

/**
 * Marca todas las notificaciones como leídas en las categorías activas.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
async function marcarTodasComoLeidas() {
    const promises = [];
    if (hdDisponible.value && hdUnreadCount.value > 0) {
        promises.push(axios.post("/api/helpdesk-notificaciones/read-all"));
    }
    if (citasDisponible.value && citasUnreadCount.value > 0) {
        promises.push(axios.post("/api/citas/notificaciones/todas-leidas"));
    }
    try {
        await Promise.allSettled(promises);
        await cargarTodasNotificaciones(true);
    } catch {}
}

/**
 * Lifecycle hook que inicializa el módulo, lista sedes y médicos,
 * carga notificaciones unificadas y configura polling inteligente a 30s.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
onMounted(() => {
    establecerModuloActual();
    listarSedes();
    cargarTodasNotificaciones();

    const url = window.location.pathname;
    const rutasEcografias = [
        "/ecografias.admisiones",
        "/ecografias.admisionadas",
        "/ecografias-pendientes-lecturas",
        "/ecografias-consentimientos",
        "/ecografias-consentimientos-plantillas",
    ];
    mostrarMedico.value = rutasEcografias.includes(url);
    if (mostrarMedico.value) {
        listarMedicos();
    }

    // Polling inteligente cada 30 segundos (con backoff cuando la pestaña está oculta)
    const pollInterval = 30000;
    function schedulePoll() {
        const delay = document.hidden ? Math.min(pollInterval * 3, 120000) : pollInterval;
        notifIntervalId.value = setTimeout(() => {
            cargarTodasNotificaciones(true);
            schedulePoll();
        }, delay);
    }
    schedulePoll();
    document.addEventListener("visibilitychange", onVisibilityChange);

    applyGlobalTheme(isDarkMode.value);

    window.addEventListener("storage", (e) => {
        if (e.key === "lidiag_dark_mode") {
            isDarkMode.value = e.newValue === "true";
            applyGlobalTheme(isDarkMode.value);
        }
    });

    emitter.on("sedeSeleccionada", (data) => {
        if (data?.idSede) {
            sedeSeleccionada.value = data.idSede;
        }
    });

    emitter.on("medicoSeleccionado", (data) => {
        if (data?.idMedico) {
            medicoSeleccionado.value = data.idMedico;
            if (data?.nomMedico) {
                nomMedicoSeleccionado.value = data.nomMedico;
            }
        }
    });
});

/**
 * Listener de visibilidad: refresca las notificaciones cuando la pestaña vuelve a ser visible.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
function onVisibilityChange() {
    if (!document.hidden) {
        cargarTodasNotificaciones(true);
    }
}

/**
 * Lifecycle hook que limpia el intervalo de polling y remueve
 * el listener de visibilidad al desmontar el componente.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2024-04-04
 */
onBeforeUnmount(() => {
    try {
        if (notifIntervalId.value) clearTimeout(notifIntervalId.value);
    } catch (_) {}
    try {
        document.removeEventListener("visibilitychange", onVisibilityChange);
    } catch (_) {}
});
</script>
