<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Citas</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Citas
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Disponibilidad de citas</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary m-r-5"
                    @click="buscarStudy()"
                    :disabled="loadingBuscar"
                    title="Actualizar listado"
                >
                    <i class="fa" :class="loadingBuscar ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="
                        editando = false;
                        mostrarRegistro();
                    "
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros de búsqueda
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="bus_fech_ini" class="control-label"
                                                >Fecha Inicio</label
                                            >
                                            <input
                                                type="date"
                                                id="bus_fech_ini"
                                                name="bus_fech_ini"
                                                v-model="busqueda.bus_fech_ini"
                                                class="form-control"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="bus_fecha_fin" class="control-label"
                                                >Fecha Fin</label
                                            >
                                            <input
                                                type="date"
                                                id="bus_fecha_fin"
                                                name="bus_fecha_fin"
                                                v-model="busqueda.bus_fecha_fin"
                                                class="form-control"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-success m-r-5"
                                        @click="buscarStudy()"
                                        :disabled="loadingBuscar"
                                    >
                                        <i
                                            class="fa"
                                            :class="
                                                loadingBuscar ? 'fa-spinner fa-spin' : 'fa-search'
                                            "
                                        ></i>
                                        {{ loadingBuscar ? "Buscando..." : "Buscar" }}
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-inverse"
                                        @click="btnLimpiar()"
                                    >
                                        <i class="fa fa-refresh"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de disponibilidades</h4>
                        <h6 class="card-subtitle text-info">
                            Gestione las disponibilidades de citas para estudios.
                        </h6>

                        <table-skeleton-loader v-if="loadingBuscar" :rows="5" :columns="5" />

                        <div v-else-if="!registros.length" class="m-t-20">
                            <empty-state-component
                                title="No hay disponibilidades registradas"
                                description="No se encontraron disponibilidades para la sede y fechas seleccionadas. Presione 'Nuevo registro' para crear una."
                                icon="fa-calendar"
                            />
                        </div>

                        <div class="table-responsive m-t-20" v-else>
                            <table
                                id="tablaAgendaDisponibilidad"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th width="5%">Fecha Inicio</th>
                                        <th width="5%">Fecha Fin</th>
                                        <th width="30%">Sede</th>
                                        <th>Servicio de Cita</th>
                                        <th class="sr-only">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ formatFecha(item.fecha_hora_ini) }}</td>
                                        <td>{{ formatFecha(item.fecha_hora_fin) }}</td>
                                        <td>{{ item.sede?.nom_sede || item.sede_id }}</td>
                                        <td>{{ item.servicio_cita?.nombre || "-" }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                title="Editar disponibilidad"
                                                aria-label="Editar"
                                                @click="editarRegistro(item)"
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                title="Eliminar disponibilidad"
                                                aria-label="Eliminar"
                                                @click="elimnarRegistro(item.id)"
                                                :disabled="loadingEliminarId === item.id"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        loadingEliminarId === item.id
                                                            ? 'fa-spinner fa-spin'
                                                            : 'fa-trash'
                                                    "
                                                ></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backdrop overlay para cerrar al hacer clic afuera -->
        <div v-if="showSidebar" class="sidebar-backdrop" @click="cerrarModal()"></div>

        <!-- Right sidebar -->
        <div
            class="right-sidebar"
            id="right-sidebar"
            :class="{ 'shw-rside': showSidebar }"
            style="overflow-x: hidden; overflow-y: auto; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="close right-side-toggle"
                        id="btnCerrarModalForm"
                        @click="cerrarModal()"
                        aria-label="Cerrar panel"
                        title="Cerrar (Esc)"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos de la disponibilidad
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="total_citas" class="control-label">
                                                    Total de citas
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="number"
                                                    id="total_citas"
                                                    name="total_citas"
                                                    v-model="registro.total_citas"
                                                    class="form-control"
                                                />
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.total_citas"
                                                >
                                                    {{ errores.total_citas[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label for="dura_cita" class="control-label">
                                                    Minutos Por Cita
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="number"
                                                    id="dura_cita"
                                                    name="dura_cita"
                                                    v-model="registro.dura_cita"
                                                    class="form-control"
                                                    placeholder="Minutos por cita"
                                                />
                                                <span class="text-danger" v-if="errores.dura_cita">
                                                    {{ errores.dura_cita[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label for="servicio_id" class="control-label">
                                                    Servicio de Cita
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select
                                                    v-model="registro.servicio_id"
                                                    name="servicio_id"
                                                    id="servicio_id"
                                                    class="form-control custom-select"
                                                >
                                                    <option value="">-- Seleccione --</option>
                                                    <option
                                                        v-for="(sc, index) in serviciosCitas"
                                                        :key="index"
                                                        :value="sc.id"
                                                    >
                                                        {{ sc.nombre }} &mdash;
                                                        {{ sc.sede?.nom_sede || "Sin sede" }}
                                                    </option>
                                                </select>
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.servicio_id"
                                                >
                                                    {{ errores.servicio_id[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label for="fecha_hora_ini" class="control-label">
                                                    Fec. Inicio
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="date"
                                                    id="fecha_hora_ini"
                                                    name="fecha_hora_ini"
                                                    v-model="registro.fecha_hora_ini"
                                                    class="form-control"
                                                />
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.fecha_hora_ini"
                                                >
                                                    {{ errores.fecha_hora_ini[0] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                    :title="
                                        editando
                                            ? 'Actualizar disponibilidad'
                                            : 'Guardar disponibilidad'
                                    "
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    {{
                                        loadingGuardar
                                            ? "Guardando..."
                                            : editando
                                              ? "Actualizar"
                                              : "Guardar"
                                    }}
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarModal()"
                                    title="Cancelar y cerrar"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { getCurrentInstance, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertError, alertSuccess, confirmAction } from "@/helpers/alerts";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import axios from "axios";

const { appContext } = getCurrentInstance();
const emitter = appContext.config.globalProperties.emitter;

const props = defineProps({ usuarioactual: { type: Object, default: () => ({}) } });

const dt = ref(null);

const editando = ref(false);
const editandoId = ref(null);
const tituloModal = ref("Guardar disponibilidad de citas");
const showSidebar = ref(false);
const sedeActual = ref(sessionStorage.getItem("ST-sede"));
const registro = ref({
    sede_id: sedeActual.value,
    quien_registro_id: props.usuarioactual.id,
    servicio_id: "",
    total_citas: 1,
    dura_cita: 15,
    fecha_hora_ini: "",
});
const busqueda = ref({ sede_id: sedeActual.value, bus_fech_ini: "", bus_fecha_fin: "" });
const serviciosCitas = ref([]);
const errores = ref([]);
const registros = ref([]);
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

useKeyboardShortcuts({
    onSearch: () => {
        if (!showSidebar.value) {
            buscarStudy();
        }
    },
    onSave: guardarRegistro,
    onEscape: cerrarModal,
    isActive: () => true,
    isLoading: loadingGuardar,
});

async function buscarStudy() {
    if (loadingBuscar.value) return;
    loadingBuscar.value = true;
    try {
        const params = {
            sede_id: busqueda.value.sede_id,
            bus_fech_ini: busqueda.value.bus_fech_ini || undefined,
            bus_fecha_fin: busqueda.value.bus_fecha_fin || undefined,
        };
        const res = await axios.get("/api/agenda-disponibilidad", { params });
        safeDestroyDataTable(dt.value || "#tablaAgendaDisponibilidad");
        registros.value = res.data.data || [];
        nextTick(() => {
            if (registros.value.length) {
                safeInitDataTable(dt.value || "#tablaAgendaDisponibilidad", {
                    dom: "Bfrtip",
                    buttons: ["copy", "csv", "excel", "pdf", "print"],
                });
            }
        });
    } catch {
        registros.value = [];
    } finally {
        loadingBuscar.value = false;
    }
}
function validarFormulario() {
    const errs = {};
    if (!registro.value.fecha_hora_ini) {
        errs.fecha_hora_ini = ["Debe seleccionar la fecha de inicio."];
    }
    if (!registro.value.servicio_id) {
        errs.servicio_id = ["Debe seleccionar un servicio de cita."];
    }
    if (!registro.value.total_citas || registro.value.total_citas < 1) {
        errs.total_citas = ["El total de citas debe ser al menos 1."];
    }
    if (!registro.value.dura_cita || registro.value.dura_cita < 1) {
        errs.dura_cita = ["La duración debe ser al menos 1 minuto."];
    }
    errores.value = errs;
    return Object.keys(errs).length === 0;
}
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    if (!validarFormulario()) return;
    loadingGuardar.value = true;
    try {
        const payload = {
            sede_id: registro.value.sede_id,
            servicio_id: registro.value.servicio_id || null,
            total_citas: registro.value.total_citas,
            dura_cita: registro.value.dura_cita,
            fecha_hora_ini: registro.value.fecha_hora_ini,
        };

        let res;
        if (editando.value && editandoId.value) {
            res = await axios.put("/api/agenda-disponibilidad/" + editandoId.value, payload);
        } else {
            res = await axios.post("/api/agenda-disponibilidad", payload);
        }

        alertSuccess(
            "Éxito",
            res?.data?.message || "La disponibilidad de citas se ha guardado con éxito."
        );
        cerrarModal();
        await buscarStudy();
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
    } finally {
        loadingGuardar.value = false;
    }
}
async function editarRegistro(item) {
    editando.value = true;
    editandoId.value = item.id;
    registro.value = {
        sede_id: item.sede_id,
        quien_registro_id: props.usuarioactual.id,
        servicio_id: item.servicio_id || "",
        total_citas: item.total_citas,
        dura_cita: item.dura_cita,
        fecha_hora_ini: item.fecha_hora_ini ? item.fecha_hora_ini.substring(0, 10) : "",
    };
    tituloModal.value = "Actualizar disponibilidad de citas";
    errores.value = [];
    showSidebar.value = true;
    nextTick(() => {
        document.getElementById("total_citas")?.focus();
    });
}
async function elimnarRegistro(id) {
    const ok = await confirmAction({
        title: "\u00bfEst\u00e1s seguro?",
        text: "No podr\u00e1s recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;
    if (loadingEliminarId.value) return;
    loadingEliminarId.value = id;
    try {
        const res = await axios.delete("/api/agenda-disponibilidad/" + id);
        alertSuccess("Éxito", res?.data?.message || "Registro eliminado.");
        await buscarStudy();
    } catch {
        alertError("Error", "No se pudo eliminar la disponibilidad");
    } finally {
        loadingEliminarId.value = null;
    }
}
async function listarServiciosCitas() {
    try {
        const res = await axios.get("/api/config-servicio-citas/mis-servicios", {
            params: { sede_id: sedeActual.value },
        });
        serviciosCitas.value = res.data.data;
    } catch {
        alertError("Error", "No se pudieron cargar los servicios");
    }
}
function establecerSede() {
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    sedeActual.value = sessionStorage.getItem("ST-sede");
    registros.value = [];
}
function btnLimpiar() {
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    buscarStudy();
}
function mostrarRegistro() {
    editando.value = false;
    editandoId.value = null;
    tituloModal.value = "Guardar disponibilidad de citas";
    registro.value = {
        sede_id: sedeActual.value,
        quien_registro_id: props.usuarioactual.id,
        servicio_id: "",
        total_citas: 1,
        dura_cita: 15,
        fecha_hora_ini: "",
    };
    errores.value = [];
    showSidebar.value = true;
    nextTick(() => {
        document.getElementById("total_citas")?.focus();
    });
}
function cerrarModal() {
    showSidebar.value = false;
}
function formatFecha(fecha) {
    if (!fecha) return "-";
    const d = new Date(fecha);
    if (isNaN(d.getTime())) return fecha;
    const dia = String(d.getDate()).padStart(2, "0");
    const mes = String(d.getMonth() + 1).padStart(2, "0");
    const anio = d.getFullYear();
    const horas = String(d.getHours()).padStart(2, "0");
    const minutos = String(d.getMinutes()).padStart(2, "0");
    return `${dia}/${mes}/${anio} ${horas}:${minutos}`;
}

function onSedeSeleccionada() {
    establecerSede();
    safeDestroyDataTable(dt.value || "#tablaAgendaDisponibilidad");
}

onMounted(() => {
    establecerSede();
    listarServiciosCitas();
    buscarStudy();

    emitter?.on("sedeSeleccionada", onSedeSeleccionada);
});

onBeforeUnmount(() => {
    emitter?.off("sedeSeleccionada", onSedeSeleccionada);
    safeDestroyDataTable(dt.value || "#tablaAgendaDisponibilidad");
});
</script>
<style scoped>
.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1090;
}

.right-sidebar {
    position: fixed;
    right: -50%;
    width: 50%;
    height: 100%;
    top: 0px;
    z-index: 1100;
    background: #ffffff;
    box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
    visibility: hidden;
    pointer-events: none;
    padding-bottom: 20px;
}

.shw-rside {
    right: 0 !important;
    visibility: visible !important;
    pointer-events: auto !important;
}

@media (max-width: 767px) {
    .right-sidebar {
        width: 100% !important;
        right: -100% !important;
    }
}
</style>
