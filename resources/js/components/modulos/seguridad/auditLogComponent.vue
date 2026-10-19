<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Seguridad</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a role="button" tabindex="0">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a role="button" tabindex="0">Seguridad</a>
                    </li>
                    <li class="breadcrumb-item active">Auditoría de Eventos</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary btn-action-clinical m-r-5"
                    :disabled="loading"
                    title="Actualizar registros de auditoría (F2)"
                    @click="cargarLogs"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loading }"></i>
                    Refrescar <span class="badge badge-light border ml-1">F2</span>
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row m-b-20">
                            <div class="col-md-3">
                                <label>Evento</label>
                                <select
                                    class="form-control"
                                    v-model="filtros.evento"
                                    @change="cargarLogs"
                                >
                                    <option value="">Todos los eventos</option>
                                    <option v-for="ev in eventosDisponibles" :key="ev" :value="ev">
                                        {{ ev }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Desde</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="filtros.desde"
                                    @change="cargarLogs"
                                />
                            </div>
                            <div class="col-md-2">
                                <label>Hasta</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="filtros.hasta"
                                    @change="cargarLogs"
                                />
                            </div>
                            <div class="col-md-3">
                                <label>Buscar</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="filtros.search"
                                    placeholder="Descripción, modelo, IP..."
                                    @keyup.enter="cargarLogs"
                                />
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button
                                    class="btn btn-success btn-block btn-action-clinical"
                                    @click="cargarLogs"
                                >
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>

                        <h4 class="card-title">Registro de Auditoría</h4>
                        <span class="text-muted" v-if="totalRegistros > 0">
                            {{ totalRegistros }} registro(s) encontrado(s)
                        </span>

                        <div class="table-responsive m-t-20" v-if="loading">
                            <div class="text-center p-5">
                                <i class="fa fa-spinner fa-spin fa-3x"></i>
                                <p class="m-t-10">Cargando registros de auditoría...</p>
                            </div>
                        </div>

                        <div class="table-responsive m-t-20" v-if="!loading">
                            <table
                                id="tablaAuditoria"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha / Hora</th>
                                        <th>Usuario</th>
                                        <th>Evento</th>
                                        <th>Descripción</th>
                                        <th>Modelo</th>
                                        <th>IP</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.id }}</td>
                                        <td>{{ item.created_at }}</td>
                                        <td>{{ item.user?.name || "—" }}</td>
                                        <td>
                                            <span
                                                :class="'label label-' + colorEvento(item.evento)"
                                            >
                                                {{ item.evento }}
                                            </span>
                                        </td>
                                        <td class="cell-descripcion">
                                            {{ item.descripcion || "—" }}
                                        </td>
                                        <td>
                                            <small v-if="item.model_type">
                                                {{ item.model_type.split("\\").pop() }} #{{
                                                    item.model_id
                                                }}
                                            </small>
                                            <span v-else>—</span>
                                        </td>
                                        <td>
                                            <small>{{ item.ip || "—" }}</small>
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="verDetalle(item)"
                                            >
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="text-center m-t-20" v-if="totalPaginas > 1">
                                <button
                                    class="btn btn-sm btn-outline-dark"
                                    :disabled="paginaActual <= 1"
                                    @click="irPagina(paginaActual - 1)"
                                >
                                    <i class="fa fa-chevron-left"></i> Anterior
                                </button>
                                <span class="m-x-15"
                                    >Página {{ paginaActual }} de {{ totalPaginas }}</span
                                >
                                <button
                                    class="btn btn-sm btn-outline-dark"
                                    :disabled="paginaActual >= totalPaginas"
                                    @click="irPagina(paginaActual + 1)"
                                >
                                    Siguiente <i class="fa fa-chevron-right"></i>
                                </button>
                            </div>

                            <div class="text-center m-t-10" v-if="totalRegistros === 0">
                                <p class="text-muted">No se encontraron registros de auditoría.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="mostrarDetalle"
            class="modal fade show d-block"
            id="modalDetalleAuditoria"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1060"
            @click.self="mostrarDetalle = false"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Detalle de Auditoría #{{ detalle.id }}</h4>
                        <button
                            type="button"
                            class="close"
                            @click="mostrarDetalle = false"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 150px">Fecha</th>
                                        <td>{{ detalle.created_at }}</td>
                                    </tr>
                                    <tr>
                                        <th>Usuario</th>
                                        <td>
                                            {{ detalle.user?.name || "—" }} (ID:
                                            {{ detalle.user_id || "—" }})
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Evento</th>
                                        <td>
                                            <span
                                                :class="
                                                    'label label-' + colorEvento(detalle.evento)
                                                "
                                            >
                                                {{ detalle.evento }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Descripción</th>
                                        <td>{{ detalle.descripcion || "—" }}</td>
                                    </tr>
                                    <tr>
                                        <th>Modelo</th>
                                        <td>
                                            {{ detalle.model_type || "—" }} #{{
                                                detalle.model_id || "—"
                                            }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>IP</th>
                                        <td>{{ detalle.ip || "—" }}</td>
                                    </tr>
                                    <tr>
                                        <th>User-Agent</th>
                                        <td style="word-break: break-all">
                                            <small>{{ detalle.user_agent || "—" }}</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="detalle.datos_viejos" class="m-t-20">
                            <h5>Datos anteriores</h5>
                            <pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto">{{
                                JSON.stringify(detalle.datos_viejos, null, 2)
                            }}</pre>
                        </div>
                        <div v-if="detalle.datos_nuevos" class="m-t-20">
                            <h5>Datos nuevos</h5>
                            <pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto">{{
                                JSON.stringify(detalle.datos_nuevos, null, 2)
                            }}</pre>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-inverse btn-action-clinical"
                            title="Cerrar (Esc)"
                            @click="mostrarDetalle = false"
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const dt = ref(null);

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { toastMessage } from "@/helpers/alerts";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import axios from "axios";

const registros = ref([]);
const eventosDisponibles = ref([]);
const totalRegistros = ref(0);
const totalPaginas = ref(0);
const paginaActual = ref(1);
const loading = ref(false);
const filtros = ref({
    evento: "",
    desde: "",
    hasta: "",
    search: "",
});
const detalle = ref({
    id: null,
    created_at: "",
    user: null,
    user_id: null,
    evento: "",
    descripcion: "",
    model_type: "",
    model_id: null,
    ip: "",
    user_agent: "",
    datos_viejos: null,
    datos_nuevos: null,
});

const mostrarDetalle = ref(false);

async function cargarEventos() {
    try {
        const res = await axios.get("/api/seguridad-auditoria/eventos");
        eventosDisponibles.value = res.data?.data || res.data || [];
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudieron cargar los eventos.",
            icon: "error",
        });
    }
}
async function cargarLogs() {
    if (loading.value) return;
    loading.value = true;

    safeDestroyDataTable(dt.value || "#tablaAuditoria");

    try {
        const params = {
            page: paginaActual.value,
            per_page: 50,
            ...filtros.value,
        };

        if (!params.desde) delete params.desde;
        if (!params.hasta) delete params.hasta;
        if (!params.search) delete params.search;
        if (!params.evento) delete params.evento;

        const res = await axios.get("/api/seguridad-auditoria", { params });

        const paginador = res.data?.data || res.data || {};
        registros.value = Array.isArray(paginador.data)
            ? paginador.data
            : Array.isArray(paginador)
              ? paginador
              : [];
        totalRegistros.value = paginador.total ?? registros.value.length;
        totalPaginas.value = paginador.last_page ?? 1;
        paginaActual.value = paginador.current_page ?? 1;

        nextTick(() => {
            safeInitDataTable(dt.value || "#tablaAuditoria", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                retrieve: true,
                paging: false,
                info: false,
                searching: false,
            });
        });
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudieron cargar los registros de auditoría.",
            icon: "error",
        });
    } finally {
        loading.value = false;
    }
}
function irPagina(pagina) {
    if (pagina < 1 || pagina > totalPaginas.value) return;
    paginaActual.value = pagina;
    cargarLogs();
}
function verDetalle(item) {
    detalle.value = { ...item };
    mostrarDetalle.value = true;
}
function colorEvento(evento) {
    const colores = {
        crear: "success",
        actualizar: "warning",
        eliminar: "danger",
        inicio_sesion: "info",
        cierre_sesion: "default",
        inicio_sesion_fallido: "danger",
    };
    return colores[evento] || "primary";
}

onMounted(() => {
    cargarEventos();
    cargarLogs();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#tablaAuditoria");
});

useKeyboardShortcuts({
    onSearch: () => {
        if (!loading.value && !mostrarDetalle.value) {
            cargarLogs();
        }
    },
    onEscape: () => {
        if (mostrarDetalle.value) {
            mostrarDetalle.value = false;
        }
    },
});
</script>
<style scoped>
.cell-descripcion {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.btn-action-clinical {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.btn-action-clinical:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
