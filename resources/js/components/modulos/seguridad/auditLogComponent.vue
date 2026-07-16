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
                    <li class="breadcrumb-item active">AuditorÃ­a de Eventos</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info btn-rounded" @click="cargarLogs">
                    <i class="fa fa-refresh m-r-5"></i> Refrescar
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
                                    placeholder="DescripciÃ³n, modelo, IP..."
                                    @keyup.enter="cargarLogs"
                                />
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-success btn-block" @click="cargarLogs">
                                    <i class="fa fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>

                        <h4 class="card-title">Registro de AuditorÃ­a</h4>
                        <span class="text-muted" v-if="totalRegistros > 0">
                            {{ totalRegistros }} registro(s) encontrado(s)
                        </span>

                        <div class="table-responsive m-t-20" v-if="loading">
                            <div class="text-center p-5">
                                <i class="fa fa-spinner fa-spin fa-3x"></i>
                                <p class="m-t-10">Cargando registros de auditorÃ­a...</p>
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
                                        <th>DescripciÃ³n</th>
                                        <th>Modelo</th>
                                        <th>IP</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.id }}</td>
                                        <td>{{ item.created_at }}</td>
                                        <td>{{ item.user?.name || "â€”" }}</td>
                                        <td>
                                            <span
                                                :class="'label label-' + colorEvento(item.evento)"
                                            >
                                                {{ item.evento }}
                                            </span>
                                        </td>
                                        <td class="cell-descripcion">
                                            {{ item.descripcion || "â€”" }}
                                        </td>
                                        <td>
                                            <small v-if="item.model_type">
                                                {{ item.model_type.split("\\").pop() }} #{{
                                                    item.model_id
                                                }}
                                            </small>
                                            <span v-else>â€”</span>
                                        </td>
                                        <td>
                                            <small>{{ item.ip || "â€”" }}</small>
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
                                    >PÃ¡gina {{ paginaActual }} de {{ totalPaginas }}</span
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
                                <p class="text-muted">No se encontraron registros de auditorÃ­a.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="modal fade"
            id="modalDetalleAuditoria"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Detalle de AuditorÃ­a #{{ detalle.id }}</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
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
                                            {{ detalle.user?.name || "â€”" }} (ID:
                                            {{ detalle.user_id || "â€”" }})
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
                                        <th>DescripciÃ³n</th>
                                        <td>{{ detalle.descripcion || "â€”" }}</td>
                                    </tr>
                                    <tr>
                                        <th>Modelo</th>
                                        <td>
                                            {{ detalle.model_type || "â€”" }} #{{
                                                detalle.model_id || "â€”"
                                            }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>IP</th>
                                        <td>{{ detalle.ip || "â€”" }}</td>
                                    </tr>
                                    <tr>
                                        <th>User-Agent</th>
                                        <td style="word-break: break-all">
                                            <small>{{ detalle.user_agent || "â€”" }}</small>
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
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">
                            Cerrar
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

import { handleAxiosErrorToast } from "@/helpers/alerts";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
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

async function cargarEventos() {
    try {
        const res = await axios.get("/api/seguridad-auditoria/eventos");
        eventosDisponibles.value = res.data;
    } catch (e) {
        handleAxiosErrorToast(e, "Error al cargar tipos de evento");
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

        registros.value = res.data.data;
        totalRegistros.value = res.data.data.total;
        totalPaginas.value = res.data.data.last_page;
        paginaActual.value = res.data.data.current_page;

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
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible cargar los registros de auditorÃ­a.");
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
    $("#modalDetalleAuditoria").modal("show");
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
</script>
<style scoped>
.cell-descripcion {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
