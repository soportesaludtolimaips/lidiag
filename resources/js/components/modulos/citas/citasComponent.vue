<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Gestión de Citas</h3>
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
                    <li class="breadcrumb-item active">Gestión de citas</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info"
                    @click="listarRegistros()"
                    :disabled="loadingBuscar"
                    title="Actualizar listado"
                >
                    <i class="fa" :class="loadingBuscar ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
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
                            <form
                                v-show="filtrosVisible"
                                action="#"
                                @submit.prevent="listarRegistros()"
                            >
                                <div class="form-body">
                                    <div class="row p-t-2">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="filtroEstadoCitas" class="control-label"
                                                    >Estado</label
                                                >
                                                <select
                                                    id="filtroEstadoCitas"
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
                                                <label
                                                    for="filtroServicioCitas"
                                                    class="control-label"
                                                    >Servicio</label
                                                >
                                                <select
                                                    id="filtroServicioCitas"
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
                                                <label
                                                    for="filtroFechaInicioCitas"
                                                    class="control-label"
                                                    >Fecha Inicio</label
                                                >
                                                <input
                                                    id="filtroFechaInicioCitas"
                                                    type="date"
                                                    v-model="busqueda.fecha_inicio"
                                                    class="form-control"
                                                    @keyup.enter="listarRegistros()"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label
                                                    for="filtroFechaFinCitas"
                                                    class="control-label"
                                                    >Fecha Fin</label
                                                >
                                                <input
                                                    id="filtroFechaFinCitas"
                                                    type="date"
                                                    v-model="busqueda.fecha_fin"
                                                    class="form-control"
                                                    @keyup.enter="listarRegistros()"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label
                                                    for="filtroBusquedaCitas"
                                                    class="control-label"
                                                    >Búsqueda</label
                                                >
                                                <input
                                                    id="filtroBusquedaCitas"
                                                    type="text"
                                                    v-model="busqueda.busqueda"
                                                    placeholder="Documento o nombre"
                                                    class="form-control"
                                                    @keyup.enter="listarRegistros()"
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

                        <table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="9" />

                        <empty-state-component
                            v-if="!loadingBuscar && registros.length === 0"
                            icon="fa fa-calendar-check-o"
                            title="No se encontraron citas"
                            description="No hay solicitudes o citas registradas para los filtros indicados."
                            action-text="Limpiar filtros"
                            action-icon="fa fa-refresh"
                            @action="limpiarFiltros()"
                        />

                        <div v-show="!loadingBuscar && registros.length > 0">
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
                                            <td class="text-nowrap align-middle">
                                                <div
                                                    class="text-primary font-weight-bold"
                                                    style="font-size: 15px"
                                                >
                                                    <i class="fa fa-id-card-o mr-1"></i>
                                                    {{ item.paciente?.num_docu }}
                                                </div>
                                            </td>
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
            class="modal show"
            style="display: block"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="showModalAsignar = false"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title font-weight-bold">
                            Asignar Cita
                            <span
                                v-if="
                                    citaSeleccionada.nombre_paciente ||
                                    citaSeleccionada.numero_documento
                                "
                                class="text-primary font-weight-normal ml-2"
                            >
                                — {{ citaSeleccionada.nombre_paciente }}
                                <span
                                    v-if="citaSeleccionada.numero_documento"
                                    class="font-weight-bold"
                                >
                                    ({{ citaSeleccionada.numero_documento }})
                                </span>
                            </span>
                        </h4>
                        <button
                            type="button"
                            class="close"
                            @click="showModalAsignar = false"
                            aria-label="Cerrar modal"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label" for="modal_servicio_id"
                                        >Servicio</label
                                    >
                                    <select
                                        id="modal_servicio_id"
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
                                    <label class="control-label" for="modal_sede_id">Sede *</label>
                                    <select
                                        id="modal_sede_id"
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
                                    <label class="control-label" for="modal_fecha_id"
                                        >Fecha *</label
                                    >
                                    <input
                                        id="modal_fecha_id"
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
                                    <label class="control-label" for="modal_hora_id">Hora *</label>
                                    <select
                                        id="modal_hora_id"
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
                                    <label class="control-label" for="modal_duracion_id"
                                        >Duración (min)</label
                                    >
                                    <input
                                        id="modal_duracion_id"
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
                                    <label class="control-label" for="modal_observaciones_id"
                                        >Observaciones</label
                                    >
                                    <textarea
                                        id="modal_observaciones_id"
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
            class="modal show"
            style="display: block"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="showModalConversacion = false"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Conversación WhatsApp</h4>
                        <button
                            type="button"
                            class="close"
                            @click="showModalConversacion = false"
                            aria-label="Cerrar modal"
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
import { computed, getCurrentInstance, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const instance = getCurrentInstance();
const emitter = instance?.appContext?.config?.globalProperties?.emitter;

import { formatFecha, getEstadoLabel, getEstadoClase } from "@/composables/useCitasUtils.js";
import { alertError, alertSuccess, alertWarning, confirmAction } from "@/helpers/alerts";
import axios from "axios";
import { useFilterPersistence } from "@/composables/useFilterPersistence";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";

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

function cerrarModales() {
    showModalAsignar.value = false;
    showModalConversacion.value = false;
}

useKeyboardShortcuts({
    onSearch: () => {
        if (!showModalAsignar.value && !showModalConversacion.value) {
            listarRegistros();
        }
    },
    onSave: () => {
        if (showModalAsignar.value) {
            asignarCita();
        }
    },
    onEscape: cerrarModales,
    isActive: () => true,
    isLoading: loadingGuardar,
});

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
    loadingBuscar.value = true;

    var params = new URLSearchParams();
    if (busqueda.value.estado) params.append("estado", busqueda.value.estado);
    if (busqueda.value.servicio_id) params.append("servicio_id", busqueda.value.servicio_id);
    if (busqueda.value.fecha_inicio) params.append("fecha_inicio", busqueda.value.fecha_inicio);
    if (busqueda.value.fecha_fin) params.append("fecha_fin", busqueda.value.fecha_fin);
    if (busqueda.value.busqueda) params.append("busqueda", busqueda.value.busqueda);
    if (page) params.append("page", page);

    axios
        .get("/api/citas?" + params.toString())
        .then(function (response) {
            registros.value = response.data.data;
            datosPaginacion.value = response.data;
            guardarFiltros();
        })
        .catch(function (error) {
            mostrarError(error);
        })
        .then(function () {
            loadingBuscar.value = false;
        });
}

function cargarListas() {
    axios.get("/api/citas/servicios").then(function (response) {
        servicios.value = response.data?.data || response.data || [];
    });

    axios
        .get("/api/config-sedes/listar/sedes/activas")
        .then(function (response) {
            sedes.value = response.data?.data || response.data || [];
        })
        .catch(function (e) {
            mostrarError(e);
        });
}

function cargarProductosPorServicio() {
    var servicioId = citaSeleccionada.value.servicio_id;
    var sedeId = citaSeleccionada.value.sede_id;

    citaSeleccionada.value.producto_id = "";
    citaSeleccionada.value.hora = "";
    citaSeleccionada.value.agenda_disponibilidad_detalle_id = "";
    detalles.value = [];
    productosFiltrados.value = [];

    if (!servicioId) return;

    var url = "/api/citas/productos/" + servicioId;
    if (sedeId) url += "?sede_id=" + sedeId;

    axios
        .get(url)
        .then(function (response) {
            productosFiltrados.value = response.data?.data || response.data || [];
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
    if (
        !citaSeleccionada.value.sede_id ||
        !citaSeleccionada.value.servicio_id ||
        !citaSeleccionada.value.producto_id ||
        !citaSeleccionada.value.fecha
    ) {
        detalles.value = [];
        return;
    }

    axios
        .get("/api/citas/disponibilidad", {
            params: {
                producto_id: citaSeleccionada.value.producto_id,
                sede_id: citaSeleccionada.value.sede_id,
                servicio_id: citaSeleccionada.value.servicio_id,
                fecha: citaSeleccionada.value.fecha,
            },
        })
        .then(function (response) {
            detalles.value = response.data?.data?.detalles || response.data?.detalles || [];
        })
        .catch(function (error) {
            mostrarError(error);
            detalles.value = [];
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
    nextTick(() => {
        document.getElementById("modal_servicio_id")?.focus();
    });
}

function asignarCita() {
    if (
        !citaSeleccionada.value.producto_id ||
        !citaSeleccionada.value.sede_id ||
        !citaSeleccionada.value.fecha ||
        !citaSeleccionada.value.agenda_disponibilidad_detalle_id
    ) {
        alertWarning("Advertencia", "Todos los campos obligatorios deben ser llenados");
        return;
    }

    const detalleSeleccionado = detalles.value.find(
        (d) => d.id == citaSeleccionada.value.agenda_disponibilidad_detalle_id
    );

    loadingGuardar.value = true;

    axios
        .post("/api/citas/" + citaSeleccionada.value.id + "/asignar", {
            producto_id: citaSeleccionada.value.producto_id,
            sede_id: citaSeleccionada.value.sede_id,
            agenda_disponibilidad_detalle_id:
                citaSeleccionada.value.agenda_disponibilidad_detalle_id,
            fecha_hora_inicio: detalleSeleccionado
                ? detalleSeleccionado.inicio
                : citaSeleccionada.value.fecha,
            duracion_minutos: citaSeleccionada.value.duracion_minutos,
            observaciones: citaSeleccionada.value.observaciones,
        })
        .then(function (_response) {
            mostrarExito("Cita asignada correctamente");
            showModalAsignar.value = false;
            listarRegistros();
        })
        .catch(function (error) {
            mostrarError(error);
        })
        .then(function () {
            loadingGuardar.value = false;
        });
}

async function confirmarCita(cita) {
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
            listarRegistros();
        })
        .catch(function (error) {
            mostrarError(error);
        });
}

async function cancelarCita(cita) {
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
            listarRegistros();
        })
        .catch(function (error) {
            mostrarError(error);
        });
}

async function cumplirCita(cita) {
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
            listarRegistros();
        })
        .catch(function (error) {
            mostrarError(error);
        });
}

async function noAsistioCita(cita) {
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
            listarRegistros();
        })
        .catch(function (error) {
            mostrarError(error);
        });
}

function verConversacion(cita) {
    conversacion.value = null;
    loadingConversacion.value = true;
    showModalConversacion.value = true;

    axios
        .get("/api/citas/" + cita.id + "/conversacion")
        .then(function (response) {
            conversacion.value = response.data?.data?.conversacion || response.data?.conversacion;
        })
        .catch(function (error) {
            mostrarError(error);
        })
        .then(function () {
            loadingConversacion.value = false;
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
    const idSede =
        data?.idSede ?? sessionStorage.getItem("ST-sede") ?? localStorage.getItem("ST-sede");
    if (idSede) {
        citaSeleccionada.value.sede_id = idSede;
    }
    cargarListas();
    listarRegistros();
}

onMounted(() => {
    inicializar();
    emitter?.on("sedeSeleccionada", onSedeSeleccionada);
    const sede = sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede");
    if (!sede && emitter) {
        emitter.emit("solicitarSedeActiva");
    }
});

onBeforeUnmount(() => {
    emitter?.off("sedeSeleccionada", onSedeSeleccionada);
});
</script>
<style scoped>
.modal.show {
    background-color: rgba(0, 0, 0, 0.5);
    overflow-y: auto;
}
.modal-dialog {
    margin-top: 50px;
}
</style>
