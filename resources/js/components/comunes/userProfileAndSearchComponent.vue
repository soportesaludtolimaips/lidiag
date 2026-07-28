<template>
    <ul class="navbar-nav my-lg-0">
        <!-- ============================================================== -->
        <!-- Notificaciones (Helpdesk) -->
        <!-- ============================================================== -->
        <li class="nav-item dropdown" v-if="hdDisponible">
            <a
                class="nav-link dropdown-toggle waves-effect waves-dark"
                href=""
                id="hd_bell"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
                @click.prevent="onOpenHdDropdown()"
            >
                <i class="icon-Bell"></i>
                <div class="notify" v-if="hdUnreadCount > 0">
                    <span class="heartbit"></span>
                    <span class="point"></span>
                </div>
            </a>
            <div
                class="dropdown-menu dropdown-menu-right mailbox animated bounceInDown"
                aria-labelledby="hd_bell"
            >
                <ul>
                    <li>
                        <div class="drop-title d-flex justify-content-between align-items-center">
                            <span>Helpdesk</span>
                            <a
                                href="/helpdesk-notificaciones"
                                class="text-info"
                                @click.prevent="goHdNotificaciones()"
                            >
                                Ver todas
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="message-center">
                            <div v-if="hdLoading" class="p-10 text-muted">
                                <i class="fa fa-spinner fa-spin"></i> Cargando...
                            </div>

                            <a
                                v-for="n in hdItems"
                                :key="n.id"
                                href="#"
                                @click.prevent="abrirHdNotificacion(n)"
                            >
                                <div
                                    class="btn btn-circle"
                                    :class="n.read_at ? 'btn-secondary' : 'btn-info'"
                                >
                                    <i class="fa" :class="n.read_at ? 'fa-check' : 'fa-bell'"></i>
                                </div>
                                <div class="mail-contnet">
                                    <h5 class="m-0">{{ n.data?.folio || "Ticket" }}</h5>
                                    <span class="mail-desc">{{ n.data?.mensaje || "" }}</span>
                                    <span class="time">{{ formatHdTime(n.created_at) }}</span>
                                </div>
                            </a>

                            <div v-if="!hdLoading && hdItems.length === 0" class="p-10 text-muted">
                                Sin notificaciones.
                            </div>
                        </div>
                    </li>
                    <li v-if="hdUnreadCount > 0">
                        <a class="nav-link text-center" href="#" @click.prevent="marcarHdTodas()">
                            <strong>Marcar todas como leídas</strong>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- ============================================================== -->
        <!-- mega menu -->
        <!-- ============================================================== -->
        <li class="nav-item dropdown mega-dropdown">
            <a
                class="nav-link dropdown-toggle waves-effect waves-dark"
                href=""
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <i class="icon-Box-Close"></i
            ></a>
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
                                        :key="ItemSede"
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
                                    <option>Seleccione el médico</option>
                                    <option
                                        v-for="ItemMedico in medicos"
                                        :key="ItemMedico"
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
                <img src="@/admin-wrap/assets/images/users/1.jpg" alt="user" class="" />
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
                                <img src="@/admin-wrap/assets/images/users/1.jpg" alt="user" />
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

const props = defineProps({
    usuarioactual: { type: Object, default: null },
    perfilUrl: { type: String, default: "" },
});

const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;

const sedes = ref([]);
const sedeSeleccionada = ref(sessionStorage.getItem("ST-sede"));
const medicoSeleccionado = ref(sessionStorage.getItem("ST-medico"));
const medicos = ref([]);
const nomMedicoSeleccionado = ref(null);
const mostrarMedico = ref(true);
const hdDisponible = ref(true);
const hdLoading = ref(false);
const hdItems = ref([]);
const hdIntervalId = ref(null);

const hdUnreadCount = computed(() => {
    return (hdItems.value || []).filter((x) => !x.read_at).length;
});
const medicoOptions = computed(() => {
    // Opciones para el Select2 local de medicos.
    return (medicos.value || []).map((p) => ({ id: String(p.id), name: p.name }));
});

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
async function Logout() {
    try {
        // Usa el logout estándar de Laravel UI (Auth::routes)
        await axios.post("/logout");
    } finally {
        // Asegura salida al login aunque el response sea 204 sin body
        window.location.href = "/";
    }
}
async function listarSedes() {
    try {
        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res.data.data;
    } catch (error) {
        console.log(error);
    }
}
async function listarMedicos() {
    try {
        const moduloId = sessionStorage.getItem("ST-modulo") || establecerModuloActual();
        const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
        medicos.value = res.data;
    } catch (error) {
        console.log(error);
    }
}
async function estableserSede() {
    if (!sedeSeleccionada.value) return;
    const res = await axios.get("/api/config-sedes/" + sedeSeleccionada.value + "/buscarPorId");

    const nomSedeSeleccionada = $('select[name="sedes"] option:selected').text();

    sessionStorage.setItem("ST-sede", sedeSeleccionada.value);
    sessionStorage.setItem("ST-nomsede", nomSedeSeleccionada);
    sessionStorage.setItem("ST-urlsede", res.data.url_oviyam);
    sessionStorage.setItem("ST-servername", res.data.tap_oviyam);
    sessionStorage.setItem("ST-email_repor", res.data.email_repor);

    emitter.emit("sedeSeleccionada", {
        idSede: sedeSeleccionada.value,
        nomSede: nomSedeSeleccionada,
        url: res.data.url_oviyam,
        serverName: res.data.tap_oviyam,
        email_repor: res.data.email_repor,
    });
}
async function estableserMedico() {
    nomMedicoSeleccionado.value = $('select[name="medicos"] option:selected').text();

    sessionStorage.setItem("ST-medico", medicoSeleccionado.value);
    sessionStorage.setItem("ST-nomMedico", nomMedicoSeleccionado.value);

    emitter.emit("medicoSeleccionado", {
        idMedico: medicoSeleccionado.value,
        nomMedico: nomMedicoSeleccionado.value,
    });
}
function onOpenHdDropdown() {
    // Recarga rápida al abrir
    cargarHdNotificaciones(true);
}
function formatHdTime(dt) {
    if (!dt) return "";
    try {
        return window.dayjs(dt).tz("America/Bogota").format("YYYY-MM-DD HH:mm");
    } catch (_) {
        return String(dt);
    }
}
async function cargarHdNotificaciones(silent = false) {
    if (!hdDisponible.value) return;
    if (hdLoading.value) return;
    hdLoading.value = true;
    try {
        const res = await axios.get("/api/helpdesk-notificaciones", {
            params: { limit: 10 },
        });
        hdItems.value = res.data?.items || [];
    } catch (error) {
        const status = error?.response?.status;
        // Si no tiene permisos o no existe el endpoint, ocultar el ícono.
        if (status === 403 || status === 404) {
            hdDisponible.value = false;
        } else if (!silent) {
        }
    } finally {
        hdLoading.value = false;
    }
}
async function marcarHdLeida(n) {
    try {
        await axios.post(`/api/helpdesk-notificaciones/${n.id}/read`);
        n.read_at = new Date().toISOString();
    } catch (error) {}
}
async function marcarHdTodas() {
    try {
        await axios.post("/api/helpdesk-notificaciones/read-all");
        await cargarHdNotificaciones(true);
    } catch (error) {}
}
async function abrirHdNotificacion(n) {
    if (!n.read_at) await marcarHdLeida(n);
    if (n.data?.ticket_id) window.location.href = `/helpdesk-tickets/${n.data.ticket_id}`;
}
function goHdNotificaciones() {
    window.location.href = "/helpdesk-notificaciones";
}

onMounted(() => {
    establecerModuloActual();
    listarSedes();
    listarMedicos();
    cargarHdNotificaciones();

    // Polling inteligente: solo si la pestaña está visible, con backoff progresivo
    let pollInterval = 30000;
    function schedulePoll() {
        const delay = document.hidden ? Math.min(pollInterval * 3, 120000) : pollInterval;
        hdIntervalId.value = setTimeout(() => {
            cargarHdNotificaciones(true);
            schedulePoll();
        }, delay);
    }
    schedulePoll();
    function onVisibilityChange() {
        if (!document.hidden) {
            cargarHdNotificaciones(true);
        }
    }
    document.addEventListener("visibilitychange", onVisibilityChange);

    const medicoSeleccionado = sessionStorage.getItem("ST-medico");
    const sedeSeleccionada = sessionStorage.getItem("ST-sede");

    const url = window.location.pathname;
    if (
        url === "/ecografias.admisiones" ||
        url === "/ecografias-pendientes-lecturas" ||
        url === "/estudios.leer.estudio.listar"
    ) {
        mostrarMedico.value = true;
    }
});

onBeforeUnmount(() => {
    try {
        if (hdIntervalId.value) clearTimeout(hdIntervalId.value);
    } catch (_) {}
    try {
        document.removeEventListener("visibilitychange", onVisibilityChange);
    } catch (_) {}
});
</script>
