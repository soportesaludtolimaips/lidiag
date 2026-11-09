<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Helpdesk</h3>
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
                    <li class="breadcrumb-item active">Reportes</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    class="btn btn-success btn-sm m-r-5"
                    type="button"
                    :disabled="loading"
                    title="Consultar reporte"
                    @click="buscar()"
                >
                    <i class="fa mr-1" :class="loading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                    Consultar
                </button>
                <button
                    class="btn btn-info btn-sm m-r-5"
                    type="button"
                    :disabled="!canExport"
                    @click="exportExcel()"
                >
                    <i class="fa fa-file-excel-o mr-1"></i> Excel
                </button>
                <button
                    class="btn btn-danger btn-sm"
                    type="button"
                    :disabled="!canExport"
                    @click="exportPdf()"
                >
                    <i class="fa fa-file-pdf-o mr-1"></i> PDF
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Reportes Helpdesk</h4>
                        <div class="row">
                            <div class="col-md-2">
                                <label for="reporteFechaIni">Desde</label>
                                <input
                                    id="reporteFechaIni"
                                    type="date"
                                    class="form-control"
                                    v-model="filters.fechaIni"
                                    @keyup.enter="buscar()"
                                />
                            </div>
                            <div class="col-md-2">
                                <label for="reporteFechaFin">Hasta</label>
                                <input
                                    id="reporteFechaFin"
                                    type="date"
                                    class="form-control"
                                    v-model="filters.fechaFin"
                                    @keyup.enter="buscar()"
                                />
                            </div>
                            <div class="col-md-3">
                                <label for="reporteSede">Sede</label>
                                <select
                                    id="reporteSede"
                                    class="form-control custom-select"
                                    v-model="filters.sedeId"
                                >
                                    <option value="">Todas</option>
                                    <option v-for="s in sedes" :key="s.id" :value="s.id">
                                        {{ s.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="reporteEstado">Estado</label>
                                <select
                                    id="reporteEstado"
                                    class="form-control custom-select"
                                    v-model="filters.estadoId"
                                >
                                    <option value="">Todos</option>
                                    <option v-for="e in estados" :key="e.id" :value="e.id">
                                        {{ e.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="reporteCategoria">Categoría</label>
                                <select
                                    id="reporteCategoria"
                                    class="form-control custom-select"
                                    v-model="filters.categoriaId"
                                >
                                    <option value="">Todas</option>
                                    <option v-for="c in categorias" :key="c.id" :value="c.id">
                                        {{ c.nombre }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row m-t-10">
                            <div class="col-md-3">
                                <label for="reportePrioridad">Prioridad</label>
                                <select
                                    id="reportePrioridad"
                                    class="form-control custom-select"
                                    v-model="filters.prioridadId"
                                >
                                    <option value="">Todas</option>
                                    <option v-for="p in prioridades" :key="p.id" :value="p.id">
                                        {{ p.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="reporteSolicitante">Solicitante (ID)</label>
                                <input
                                    id="reporteSolicitante"
                                    type="number"
                                    class="form-control"
                                    v-model="filters.solicitanteId"
                                    placeholder="Opcional"
                                    @keyup.enter="buscar()"
                                />
                            </div>
                            <div class="col-md-3">
                                <label for="reporteTecnico">Técnico (ID)</label>
                                <input
                                    id="reporteTecnico"
                                    type="number"
                                    class="form-control"
                                    v-model="filters.tecnicoId"
                                    placeholder="Opcional"
                                    @keyup.enter="buscar()"
                                />
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button
                                    class="btn btn-inverse btn-sm"
                                    type="button"
                                    :disabled="loading"
                                    title="Limpiar filtros"
                                    @click="limpiar()"
                                >
                                    <i class="fa fa-eraser mr-1"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" v-if="indicadores">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total</h6>
                        <h2 class="m-b-0">{{ indicadores.total || 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Abiertos</h6>
                        <h2 class="m-b-0">
                            {{ indicadores.abiertos_vs_resueltos?.abiertos || 0 }}
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Resueltos</h6>
                        <h2 class="m-b-0">
                            {{ indicadores.abiertos_vs_resueltos?.resueltos || 0 }}
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Prom. resolución (hrs)</h6>
                        <h2 class="m-b-0">{{ indicadores.promedio_resolucion_horas ?? "N/A" }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" v-if="indicadores">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Totales por estado</h4>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <caption class="sr-only">
                                    Resumen de tickets agrupados por estado
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Estado</th>
                                        <th scope="col">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(r, idx) in indicadores.totales_por_estado || []"
                                        :key="idx"
                                    >
                                        <td>{{ r.estado }}</td>
                                        <td>{{ r.total }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Tickets por mes</h4>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <caption class="sr-only">
                                    Tickets creados por mes
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Mes</th>
                                        <th scope="col">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="r in indicadores.tickets_por_mes || []" :key="r.mes">
                                        <td>{{ r.mes }}</td>
                                        <td>{{ r.total }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" v-if="indicadores">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Tickets por categoría</h4>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <caption class="sr-only">
                                    Tickets por categoría
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Categoría</th>
                                        <th scope="col">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="r in indicadores.tickets_por_categoria || []"
                                        :key="r.categoria"
                                    >
                                        <td>{{ r.categoria }}</td>
                                        <td>{{ r.total }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Top técnicos</h4>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <caption class="sr-only">
                                    Tickets resueltos por técnico
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Técnico</th>
                                        <th scope="col">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="r in indicadores.tickets_por_tecnico || []"
                                        :key="r.tecnico_id || r.tecnico"
                                    >
                                        <td>{{ r.tecnico }}</td>
                                        <td>{{ r.total }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Detalle (últimos {{ limit }} registros)</h4>
                        <table-skeleton-loader :visible="loading" :rows="8" :columns="12" />
                        <empty-state-component
                            v-if="!loading && !detallado.length"
                            title="No se encontraron registros"
                            description="No hay registros detallados para los filtros seleccionados."
                            icon="fa-bar-chart"
                        />
                        <div
                            v-show="!loading && detallado.length > 0"
                            class="table-responsive m-t-20"
                        >
                            <table
                                id="helpdeskReportesDetallado"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <caption class="sr-only">
                                    Listado detallado de tickets
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Folio</th>
                                        <th scope="col">Título</th>
                                        <th scope="col">Estado</th>
                                        <th scope="col">Prioridad</th>
                                        <th scope="col">Categoría</th>
                                        <th scope="col">Pendiente</th>
                                        <th scope="col">Sede</th>
                                        <th scope="col">Solicitante</th>
                                        <th scope="col">Técnico</th>
                                        <th scope="col">Creado</th>
                                        <th scope="col">Resuelto</th>
                                        <th scope="col">Horas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="t in detallado" :key="t.id">
                                        <td>
                                            <router-link :to="`/helpdesk-tickets/${t.id}`">{{
                                                t.folio || "#" + t.id
                                            }}</router-link>
                                        </td>
                                        <td>{{ t.titulo }}</td>
                                        <td>{{ t.estado }}</td>
                                        <td>{{ t.prioridad }}</td>
                                        <td>{{ t.categoria }}</td>
                                        <td>{{ t.pendiente_de }}</td>
                                        <td>{{ t.sede }}</td>
                                        <td>{{ t.solicitante }}</td>
                                        <td>{{ t.tecnico }}</td>
                                        <td>{{ formatDateTime(t.created_at) }}</td>
                                        <td>{{ formatDateTime(t.resolved_at) }}</td>
                                        <td>{{ formatHoras(t.segundos_resolucion) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, getCurrentInstance, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { toastMessage } from "@/helpers/alerts.js";
import { formatDateTime } from "@/helpers/dateUtils.js";
import { helpdesk } from "@/helpers/api.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";

const { appContext } = getCurrentInstance();
const emitter = appContext.config.globalProperties.emitter;

const dt = ref(null);
const loading = ref(false);
const limit = ref(500);
const indicadores = ref(null);
const detallado = ref([]);
const sedes = ref([]);
const estados = ref([]);
const categorias = ref([]);
const prioridades = ref([]);
const _today = new Date();
const _todayStr = `${_today.getFullYear()}-${String(_today.getMonth() + 1).padStart(2, "0")}-${String(_today.getDate()).padStart(2, "0")}`;
const _firstDayStr = `${_today.getFullYear()}-${String(_today.getMonth() + 1).padStart(2, "0")}-01`;

function obtenerSedeActiva() {
    return sessionStorage.getItem("ST-sede") || localStorage.getItem("ST-sede") || "";
}

const filters = ref({
    fechaIni: _firstDayStr,
    fechaFin: _todayStr,
    sedeId: obtenerSedeActiva(),
    estadoId: "",
    categoriaId: "",
    prioridadId: "",
    solicitanteId: "",
    tecnicoId: "",
});

const canExport = computed(() => {
    return !!(filters.value.fechaIni && filters.value.fechaFin && detallado.value.length > 0);
});

function formatHoras(segundos) {
    if (!segundos) return "";
    const h = Number(segundos) / 3600;
    if (Number.isNaN(h)) return "";
    return h.toFixed(2);
}

async function cargarCatalogos() {
    try {
        const [cats, prios, ests, sedesRes] = await Promise.all([
            helpdesk.categorias(),
            helpdesk.prioridades(),
            helpdesk.estados(),
            helpdesk.sedesActivas(),
        ]);
        const extractArray = (res) =>
            Array.isArray(res?.data?.data)
                ? res.data.data
                : Array.isArray(res?.data)
                  ? res.data
                  : [];
        categorias.value = extractArray(cats).filter((x) => x.activo);
        prioridades.value = extractArray(prios).filter((x) => x.activo);
        estados.value = extractArray(ests).filter((x) => x.activo);
        sedes.value = extractArray(sedesRes);
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudieron cargar los catálogos.",
            icon: "error",
        });
    }
}

function limpiar() {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const dd = String(today.getDate()).padStart(2, "0");
    const todayStr = `${yyyy}-${mm}-${dd}`;
    const firstDayStr = `${yyyy}-${mm}-01`;
    filters.value = {
        fechaIni: firstDayStr,
        fechaFin: todayStr,
        sedeId: obtenerSedeActiva(),
        estadoId: "",
        categoriaId: "",
        prioridadId: "",
        solicitanteId: "",
        tecnicoId: "",
    };
    buscar();
}

function buildParams(extra = {}) {
    return {
        fechaIni: filters.value.fechaIni,
        fechaFin: filters.value.fechaFin,
        sedeId: filters.value.sedeId || "",
        estadoId: filters.value.estadoId || "",
        categoriaId: filters.value.categoriaId || "",
        prioridadId: filters.value.prioridadId || "",
        solicitanteId: filters.value.solicitanteId || "",
        tecnicoId: filters.value.tecnicoId || "",
        ...extra,
    };
}

function initDT() {
    if (!detallado.value.length) return;
    safeInitDataTable(dt.value || "#helpdeskReportesDetallado", {
        dom: "Bfrtip",
        buttons: [
            { extend: "copy", titleAttr: "Copiar al portapapeles" },
            { extend: "csv", titleAttr: "Exportar a CSV" },
            { extend: "excel", titleAttr: "Exportar a Excel" },
            { extend: "pdf", titleAttr: "Exportar a PDF" },
            { extend: "print", titleAttr: "Imprimir" },
        ],
        autoWidth: false,
        columnDefs: [{ targets: [11], orderable: false }],
        order: [],
    });
}

async function buscar() {
    if (loading.value) return;
    if (
        filters.value.fechaIni &&
        filters.value.fechaFin &&
        filters.value.fechaFin < filters.value.fechaIni
    ) {
        toastMessage({
            heading: "Upss!!!",
            text: "La fecha 'Hasta' no puede ser anterior a 'Desde'.",
            icon: "warning",
        });
        nextTick(() => document.getElementById("reporteFechaFin")?.focus());
        return;
    }
    loading.value = true;
    safeDestroyDataTable(dt.value || "#helpdeskReportesDetallado");

    try {
        const params = buildParams({ limit: limit.value });
        const [ind, det] = await Promise.all([
            helpdesk.indicadores(params),
            helpdesk.detallado(params),
        ]);
        indicadores.value = ind.data?.data ?? ind.data ?? null;
        const detPayload = det.data?.data ?? det.data;
        detallado.value = detPayload?.items || (Array.isArray(detPayload) ? detPayload : []);
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No se pudieron cargar los reportes.",
            icon: "error",
        });
        detallado.value = [];
    } finally {
        loading.value = false;
        await nextTick();
        if (detallado.value.length > 0) {
            safeDestroyDataTable(dt.value || "#helpdeskReportesDetallado");
            initDT();
        }
    }
}

function exportExcel() {
    toastMessage({ heading: "Exportando", text: "Generando archivo Excel...", icon: "info" });
    const params = new URLSearchParams(buildParams());
    window.open(helpdesk.exportExcelUrl(params), "_blank", "noopener,noreferrer");
}

function exportPdf() {
    toastMessage({ heading: "Exportando", text: "Generando archivo PDF...", icon: "info" });
    const params = new URLSearchParams(buildParams());
    window.open(helpdesk.exportPdfUrl(params), "_blank", "noopener,noreferrer");
}

function onSedeSeleccionada(data) {
    const raw =
        data?.sede ??
        (typeof data === "object" ? data.idSede || data.id : data) ??
        obtenerSedeActiva();
    const idSede = raw ? String(raw) : "";
    if (filters.value.sedeId === idSede) return;
    filters.value.sedeId = idSede;
    buscar();
}

useKeyboardShortcuts({
    onSearch: () => {
        buscar();
    },
    onEscape: () => {
        limpiar();
    },
});

onMounted(async () => {
    emitter.on("sedeSeleccionada", onSedeSeleccionada);
    const sedeActiva = obtenerSedeActiva();
    if (sedeActiva) {
        filters.value.sedeId = sedeActiva;
    } else if (emitter) {
        emitter.emit("solicitarSedeActiva");
    }
    await cargarCatalogos();
    await buscar();
});

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
    safeDestroyDataTable(dt.value || "#helpdeskReportesDetallado");
});
</script>
