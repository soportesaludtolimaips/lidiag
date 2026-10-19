<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Gestión de Citas</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="#">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="#">Citas</a>
                    </li>
                    <li class="breadcrumb-item active">Gestión de citas</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="card-title text-themecolor mb-0">
                                <i class="fa fa-filter"></i> Filtros de búsqueda
                                <span
                                    v-if="filtrosActivosCount > 0"
                                    class="badge badge-pill badge-info ml-2"
                                >
                                    {{ filtrosActivosCount }}
                                    {{
                                        filtrosActivosCount === 1
                                            ? "filtro activo"
                                            : "filtros activos"
                                    }}
                                </span>
                            </h4>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                @click="toggleFiltros()"
                                :title="filtrosVisible ? 'Ocultar filtros' : 'Mostrar filtros'"
                            >
                                <i
                                    :class="
                                        filtrosVisible ? 'fa fa-chevron-up' : 'fa fa-chevron-down'
                                    "
                                ></i>
                                {{ filtrosVisible ? "Ocultar filtros" : "Mostrar filtros" }}
                            </button>
                        </div>
                        <transition name="fade-collapse">
                            <form v-show="filtrosVisible" action="#">
                                <div class="form-body">
                                    <div class="row p-t-2">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Estado</label>
                                                <select
                                                    v-model="busqueda.estado"
                                                    class="form-control"
                                                >
                                                    <option value="">Todos</option>
                                                    <option value="solicitada">Solicitada</option>
                                                    <option value="asignada">Asignada</option>
                                                    <option value="confirmada">Confirmada</option>
                                                    <option value="cancelada">Cancelada</option>
                                                    <option value="cumplida">Cumplida</option>
                                                    <option value="no_asistio">No asistió</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Servicio</label>
                                                <select
                                                    v-model="busqueda.servicio_id"
                                                    class="form-control"
                                                >
                                                    <option value="">Todos</option>
                                                    <option
                                                        v-for="s in servicios"
                                                        :key="s.id"
                                                        :value="s.id"
                                                    >
                                                        {{ s.nombre }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Fecha Inicio</label>
                                                <input
                                                    type="date"
                                                    v-model="busqueda.fecha_inicio"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Fecha Fin</label>
                                                <input
                                                    type="date"
                                                    v-model="busqueda.fecha_fin"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Búsqueda</label>
                                                <input
                                                    type="text"
                                                    v-model="busqueda.busqueda"
                                                    placeholder="Documento o nombre"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions mt-3">
                                        <button
                                            type="button"
                                            class="btn btn-success m-r-5"
                                            @click="listarRegistros()"
                                            :disabled="loadingBuscar"
                                        >
                                            <i
                                                class="fa"
                                                :class="
                                                    loadingBuscar
                                                        ? 'fa-spinner fa-spin'
                                                        : 'fa-search'
                                                "
                                            ></i>
                                            {{ loadingBuscar ? "Buscando..." : "Buscar" }}
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-inverse"
                                            @click="limpiarFiltros()"
                                        >
                                            <i class="fa fa-refresh"></i> Limpiar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </transition>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de Citas</h4>
                        <h6 class="card-subtitle text-info">
                            Gestione las solicitudes de citas y asígneles fecha y hora
                        </h6>

                        <loading-spinner :visible="loadingBuscar" />

                        <div :class="{ 'd-none': loadingBuscar }">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha Solicitud</th>
                                            <th>Paciente</th>
                                            <th>Documento</th>
                                            <th>Teléfono</th>
                                            <th>Servicio</th>
                                            <th>Orden</th>
                                            <th>Autorización</th>
                                            <th>Estado</th>
                                            <th>Fecha Cita</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!registros.length">
                                            <td colspan="10" class="text-center text-muted py-4">
                                                <i class="fa fa-calendar fa-2x d-block mb-2"></i>
                                                No se encontraron citas.
                                            </td>
                                        </tr>
                                        <tr v-for="item in registros" :key="item.id">
                                            <td>{{ formatFecha(item.created_at) }}</td>
                                            <td>
                                                {{ item.paciente?.nombres }}
                                                {{ item.paciente?.pri_ape }}
                                            </td>
                                            <td>{{ item.paciente?.num_docu }}</td>
                                            <td>{{ item.paciente?.cel }}</td>
                                            <td>{{ item.servicio?.nombre }}</td>
                                            <td>{{ item.numero_orden }}</td>
                                            <td>{{ item.numero_autorizacion }}</td>
                                            <td>
                                                <span
                                                    :class="getEstadoClase(item.estado)"
                                                    class="badge badge-pill font-weight-bold"
                                                >
                                                    {{ getEstadoLabel(item.estado) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{
                                                    item.fecha_hora_inicio
                                                        ? formatFecha(item.fecha_hora_inicio)
                                                        : "-"
                                                }}
                                            </td>
                                            <td class="text-nowrap" style="min-width: 220px">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary btn-sm btn-rounded m-1"
                                                    @click="
                                                        actualizar = true;
                                                        mostrarModalAsignar(item);
                                                    "
                                                    :title="'Asignar cita'"
                                                >
                                                    <i class="fa fa-calendar-plus"></i>
                                                </button>
                                                <button
                                                    v-if="item.estado === 'asignada'"
                                                    type="button"
                                                    class="btn btn-outline-success btn-sm btn-rounded m-1"
                                                    @click="confirmarCita(item)"
                                                    title="Confirmar cita"
                                                >
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <button
                                                    v-if="
                                                        item.estado === 'asignada' ||
                                                        item.estado === 'confirmada'
                                                    "
                                                    type="button"
                                                    class="btn btn-outline-danger btn-sm btn-rounded m-1"
                                                    @click="cancelarCita(item)"
                                                    title="Cancelar cita"
                                                >
                                                    <i class="fa fa-times"></i>
                                                </button>
                                                <button
                                                    v-if="item.estado === 'confirmada'"
                                                    type="button"
                                                    class="btn btn-outline-info btn-sm btn-rounded m-1"
                                                    @click="cumplirCita(item)"
                                                    title="Marcar como cumplida"
                                                >
                                                    <i class="fa fa-check-circle"></i>
                                                </button>
                                                <button
                                                    v-if="item.estado === 'confirmada'"
                                                    type="button"
                                                    class="btn btn-outline-secondary btn-sm btn-rounded m-1"
                                                    @click="noAsistioCita(item)"
                                                    title="Marcar como no asistió"
                                                >
                                                    <i class="fa fa-user-times"></i>
                                                </button>
                                                <button
                                                    v-if="item.paciente?.cel"
                                                    type="button"
                                                    class="btn btn-outline-warning btn-sm btn-rounded m-1"
                                                    @click="verConversacion(item)"
                                                    title="Ver conversación WhatsApp"
                                                >
                                                    <i class="fa fa-comments"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12 text-right">
                                    <pagination
                                        :data="datosPaginacion"
                                        @pagination-change-page="listarRegistros"
                                    >
                                    </pagination>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showModalAsignar"
            id="modalAsignar"
            class="modal"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Asignar Cita</h4>
                        <button
                            type="button"
                            class="close"
                            @click="showModalAsignar = false"
                            aria-hidden="true"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Servicio</label>
                                    <select
                                        v-model="citaSeleccionada.servicio_id"
                                        class="form-control"
                                        @change="cargarProductosPorServicio()"
                                    >
                                        <option value="">Seleccione...</option>
                                        <option v-for="s in servicios" :key="s.id" :value="s.id">
                                            {{ s.nombre }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" id="producto-parent">
                                    <label class="control-label">Producto *</label>
                                    <Select2
                                        :value="citaSeleccionada.producto_id"
                                        :options="productoOptions"
                                        width="100%"
                                        :allow-clear="true"
                                        @input="onProductoSelect"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Sede *</label>
                                    <select
                                        v-model="citaSeleccionada.sede_id"
                                        class="form-control"
                                        @change="
                                            cargarProductosPorServicio();
                                            cargarDisponibilidad();
                                        "
                                    >
                                        <option value="">Seleccione...</option>
                                        <option
                                            v-for="sede in sedes"
                                            :key="sede.id"
                                            :value="sede.id"
                                        >
                                            {{ sede.nom_sede }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Fecha *</label>
                                    <input
                                        type="date"
                                        v-model="citaSeleccionada.fecha"
                                        class="form-control"
                                        @change="cargarDisponibilidad()"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Hora *</label>
                                    <select
                                        v-model="citaSeleccionada.agenda_disponibilidad_detalle_id"
                                        class="form-control"
                                    >
                                        <option value="">Seleccione...</option>
                                        <option
                                            v-for="detalle in detalles"
                                            :key="detalle.id"
                                            :value="detalle.id"
                                            :disabled="!detalle.disponible"
                                        >
                                            {{ detalle.inicio.substring(11, 16) }}
                                            {{ !detalle.disponible ? "(Ocupado)" : "" }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Duración (min)</label>
                                    <input
                                        type="number"
                                        v-model="citaSeleccionada.duracion_minutos"
                                        class="form-control"
                                        placeholder="30"
                                        min="15"
                                        step="5"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Observaciones</label>
                                    <textarea
                                        v-model="citaSeleccionada.observaciones"
                                        class="form-control"
                                        rows="3"
                                    >
                                    </textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse"
                            @click="showModalAsignar = false"
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-success"
                            @click="asignarCita()"
                            :disabled="loadingGuardar"
                        >
                            <i
                                class="fa"
                                :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-save'"
                            ></i>
                            {{ loadingGuardar ? "Guardando..." : "Guardar" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showModalConversacion"
            id="modalConversacion"
            class="modal"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Conversación WhatsApp</h4>
                        <button
                            type="button"
                            class="close"
                            @click="showModalConversacion = false"
                            aria-hidden="true"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div v-if="loadingConversacion" class="text-center p-4">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Cargando conversación...</p>
                        </div>
                        <div v-else-if="conversacion">
                            <div class="alert alert-info">
                                <strong>Teléfono:</strong> {{ conversacion.telefono }}<br />
                                <strong>Estado:</strong> {{ conversacion.paso }}<br />
                                <strong>Último mensaje:</strong>
                                {{ formatFecha(conversacion.ultimo_mensaje) }}
                            </div>
                            <div v-if="conversacion.datos" class="mt-3">
                                <h5>Datos de la conversación</h5>
                                <pre class="bg-light p-3 rounded">{{
                                    JSON.stringify(conversacion.datos, null, 2)
                                }}</pre>
                            </div>
                        </div>
                        <div v-else class="text-center p-4">
                            <i class="fa fa-comments-o fa-3x text-muted"></i>
                            <p class="mt-2 text-muted">
                                No se encontró conversación para este paciente.
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse"
                            @click="showModalConversacion = false"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, getCurrentInstance, onBeforeUnmount, onMounted, ref } from "vue";

const { proxy, appContext } = getCurrentInstance();
const emitter = appContext.config.globalProperties.emitter;

import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import { formatFecha, getEstadoLabel, getEstadoClase } from "@/composables/useCitasUtils.js";
import { alertError, alertSuccess, alertWarning, confirmAction } from "@/helpers/alerts";
import axios from "axios";
import { useFilterPersistence } from "@/composables/useFilterPersistence";

const registros = ref([]);
const productosFiltrados = ref([]);
const sedes = ref([]);
const servicios = ref([]);
const detalles = ref([]);
const datosPaginacion = ref({});
const conversacion = ref(null);
const loadingConversacion = ref(false);

const defaultBusqueda = {
    estado: "",
    servicio_id: "",
    fecha_inicio: "",
    fecha_fin: "",
    busqueda: "",
};

const {
    filtros: busqueda,
    filtrosVisible,
    filtrosActivosCount,
    guardarFiltros,
    toggleFiltros,
    limpiarFiltrosStorage,
} = useFilterPersistence("lidiag_filters_citas", defaultBusqueda);
const citaSeleccionada = ref({
    id: null,
    servicio_id: "",
    producto_id: "",
    sede_id: "",
    fecha: "",
    hora: "",
    agenda_disponibilidad_detalle_id: "",
    duracion_minutos: 30,
    observaciones: "",
});
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const actualizar = ref(false);
const showModalAsignar = ref(false);
const showModalConversacion = ref(false);

const productoOptions = computed(() => {
    return (productosFiltrados.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});

function inicializar() {
    listarRegistros();
    cargarListas();
}

function listarRegistros(page) {
    var thisVue = proxy;
    thisVue.loadingBuscar = true;

    var params = new URLSearchParams();
    if (thisVue.busqueda.estado) params.append("estado", thisVue.busqueda.estado);
    if (thisVue.busqueda.servicio_id) params.append("servicio_id", thisVue.busqueda.servicio_id);
    if (thisVue.busqueda.fecha_inicio) params.append("fecha_inicio", thisVue.busqueda.fecha_inicio);
    if (thisVue.busqueda.fecha_fin) params.append("fecha_fin", thisVue.busqueda.fecha_fin);
    if (thisVue.busqueda.busqueda) params.append("busqueda", thisVue.busqueda.busqueda);
    if (page) params.append("page", page);

    axios
        .get("/api/citas?" + params.toString())
        .then(function (response) {
            thisVue.registros = response.data.data;
            thisVue.datosPaginacion = response.data;
            guardarFiltros();
        })
        .catch(function (error) {
            thisVue.mostrarError(error);
        })
        .then(function () {
            thisVue.loadingBuscar = false;
        });
}

function cargarListas() {
    var thisVue = proxy;

    axios.get("/api/citas/servicios").then(function (response) {
        thisVue.servicios = response.data?.data || response.data || [];
    });

    axios
        .get("/api/config-sedes/listar/sedes/activas")
        .then(function (response) {
            thisVue.sedes = response.data?.data || response.data || [];
        })
        .catch(function (e) {
            mostrarError(e);
        });
}

function cargarProductosPorServicio() {
    var thisVue = proxy;
    var servicioId = thisVue.citaSeleccionada.servicio_id;
    var sedeId = thisVue.citaSeleccionada.sede_id;

    thisVue.citaSeleccionada.producto_id = "";
    thisVue.citaSeleccionada.hora = "";
    thisVue.citaSeleccionada.agenda_disponibilidad_detalle_id = "";
    thisVue.detalles = [];
    thisVue.productosFiltrados = [];

    if (!servicioId) return;

    var url = "/api/citas/productos/" + servicioId;
    if (sedeId) url += "?sede_id=" + sedeId;

    axios
        .get(url)
        .then(function (response) {
            thisVue.productosFiltrados = response.data?.data || response.data || [];
        })
        .catch(function (error) {
            mostrarError(error);
        });
}

function onProductoSelect(value) {
    citaSeleccionada.value.producto_id = value || "";
    cargarDisponibilidad();
}

function cargarDisponibilidad() {
    var thisVue = proxy;

    if (
        !thisVue.citaSeleccionada.sede_id ||
        !thisVue.citaSeleccionada.servicio_id ||
        !thisVue.citaSeleccionada.producto_id ||
        !thisVue.citaSeleccionada.fecha
    ) {
        thisVue.detalles = [];
        return;
    }

    axios
        .get("/api/citas/disponibilidad", {
            params: {
                producto_id: thisVue.citaSeleccionada.producto_id,
                sede_id: thisVue.citaSeleccionada.sede_id,
                servicio_id: thisVue.citaSeleccionada.servicio_id,
                fecha: thisVue.citaSeleccionada.fecha,
            },
        })
        .then(function (response) {
            thisVue.detalles = response.data?.data?.detalles || response.data?.detalles || [];
        })
        .catch(function (error) {
            thisVue.mostrarError(error);
            thisVue.detalles = [];
        });
}

function mostrarModalAsignar(cita) {
    citaSeleccionada.value = {
        id: cita.id,
        servicio_id: cita.servicio_id || "",
        producto_id: "",
        sede_id: "",
        fecha: "",
        hora: "",
        agenda_disponibilidad_detalle_id: "",
        duracion_minutos: 30,
        observaciones: "",
    };
    productosFiltrados.value = [];
    detalles.value = [];
    actualizar.value = true;

    if (cita.servicio_id) {
        cargarProductosPorServicio();
    }

    showModalAsignar.value = true;
}

function asignarCita() {
    var thisVue = proxy;

    if (
        !thisVue.citaSeleccionada.producto_id ||
        !thisVue.citaSeleccionada.sede_id ||
        !thisVue.citaSeleccionada.fecha ||
        !thisVue.citaSeleccionada.agenda_disponibilidad_detalle_id
    ) {
        alertWarning("Advertencia", "Todos los campos obligatorios deben ser llenados");
        return;
    }

    const detalleSeleccionado = thisVue.detalles.find(
        (d) => d.id == thisVue.citaSeleccionada.agenda_disponibilidad_detalle_id
    );

    thisVue.loadingGuardar = true;

    axios
        .post("/api/citas/" + thisVue.citaSeleccionada.id + "/asignar", {
            producto_id: thisVue.citaSeleccionada.producto_id,
            sede_id: thisVue.citaSeleccionada.sede_id,
            agenda_disponibilidad_detalle_id:
                thisVue.citaSeleccionada.agenda_disponibilidad_detalle_id,
            fecha_hora_inicio: detalleSeleccionado
                ? detalleSeleccionado.inicio
                : thisVue.citaSeleccionada.fecha,
            duracion_minutos: thisVue.citaSeleccionada.duracion_minutos,
            observaciones: thisVue.citaSeleccionada.observaciones,
        })
        .then(function (_response) {
            mostrarExito("Cita asignada correctamente");
            showModalAsignar.value = false;
            thisVue.listarRegistros();
        })
        .catch(function (error) {
            thisVue.mostrarError(error);
        })
        .then(function () {
            thisVue.loadingGuardar = false;
        });
}

async function confirmarCita(cita) {
    var thisVue = proxy;

    const ok = await confirmAction({
        title: "Confirmar Cita",
        text: "¿Está seguro de confirmar esta cita?",
        icon: "warning",
    });
    if (!ok) return;

    axios
        .post("/api/citas/" + cita.id + "/confirmar")
        .then(function (_response) {
            mostrarExito("Cita confirmada");
            thisVue.listarRegistros();
        })
        .catch(function (error) {
            thisVue.mostrarError(error);
        });
}

async function cancelarCita(cita) {
    var thisVue = proxy;

    const ok = await confirmAction({
        title: "Cancelar Cita",
        text: "¿Está seguro de cancelar esta cita?",
        icon: "warning",
    });
    if (!ok) return;

    axios
        .post("/api/citas/" + cita.id + "/cancelar", {
            motivo: "Cancelada por usuario",
        })
        .then(function (_response) {
            mostrarExito("Cita cancelada");
            thisVue.listarRegistros();
        })
        .catch(function (error) {
            thisVue.mostrarError(error);
        });
}

async function cumplirCita(cita) {
    var thisVue = proxy;

    const ok = await confirmAction({
        title: "Cumplir Cita",
        text: "¿Marcar esta cita como cumplida?",
        icon: "question",
    });
    if (!ok) return;

    axios
        .post("/api/citas/" + cita.id + "/cumplir")
        .then(function (_response) {
            mostrarExito("Cita marcada como cumplida");
            thisVue.listarRegistros();
        })
        .catch(function (error) {
            thisVue.mostrarError(error);
        });
}

async function noAsistioCita(cita) {
    var thisVue = proxy;

    const ok = await confirmAction({
        title: "No Asistió",
        text: "¿Marcar esta cita como no asistió?",
        icon: "warning",
    });
    if (!ok) return;

    axios
        .post("/api/citas/" + cita.id + "/no-asistio")
        .then(function (_response) {
            mostrarExito("Inasistencia registrada");
            thisVue.listarRegistros();
        })
        .catch(function (error) {
            thisVue.mostrarError(error);
        });
}

function verConversacion(cita) {
    var thisVue = proxy;
    thisVue.conversacion = null;
    thisVue.loadingConversacion = true;
    showModalConversacion.value = true;

    axios
        .get("/api/citas/" + cita.id + "/conversacion")
        .then(function (response) {
            thisVue.conversacion = response.data?.data?.conversacion || response.data?.conversacion;
        })
        .catch(function (error) {
            thisVue.mostrarError(error);
        })
        .then(function () {
            thisVue.loadingConversacion = false;
        });
}

function limpiarFiltros() {
    limpiarFiltrosStorage();
    listarRegistros();
}

function mostrarError(error) {
    var mensaje = error.response?.data?.error || error.message || "Error desconocido";
    alertError("Error", mensaje);
}

function mostrarExito(mensaje) {
    alertSuccess("Éxito", mensaje);
}

function onSedeSeleccionada(data) {
    const idSede = data?.idSede ?? sessionStorage.getItem("ST-sede");
    if (idSede) {
        citaSeleccionada.value.sede_id = idSede;
    }
    cargarListas();
    listarRegistros();
}

onMounted(() => {
    inicializar();
    emitter?.on("sedeSeleccionada", onSedeSeleccionada);
});

onBeforeUnmount(() => {
    emitter?.off("sedeSeleccionada", onSedeSeleccionada);
});
</script>
