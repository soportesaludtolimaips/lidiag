<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Helpdesk</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
                    <li class="breadcrumb-item active">Reportes</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right">
                <button
                    class="btn btn-success btn-sm m-r-5"
                    type="button"
                    :disabled="loading"
                    @click="buscar()"
                >
                    <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                    Consultar
                </button>
                <button
                    class="btn btn-info btn-sm m-r-5"
                    type="button"
                    :disabled="!canExport"
                    @click="exportExcel()"
                >
                    <i class="fa fa-file-excel-o"></i> Excel
                </button>
                <button
                    class="btn btn-danger btn-sm"
                    type="button"
                    :disabled="!canExport"
                    @click="exportPdf()"
                >
                    <i class="fa fa-file-pdf-o"></i> PDF
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
                                />
                            </div>
                            <div class="col-md-2">
                                <label for="reporteFechaFin">Hasta</label>
                                <input
                                    id="reporteFechaFin"
                                    type="date"
                                    class="form-control"
                                    v-model="filters.fechaFin"
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
                                />
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button
                                    class="btn btn-inverse btn-sm"
                                    type="button"
                                    :disabled="loading"
                                    @click="limpiar()"
                                >
                                    <i class="fa fa-refresh"></i> Limpiar
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
                                        v-for="r in indicadores.totales_por_estado || []"
                                        :key="r.estado"
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
                        <div v-if="loading" class="text-center p-5">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p class="m-t-10">Cargando reporte...</p>
                        </div>
                        <div v-else-if="!detallado.length" class="text-center p-5 text-muted">
                            No se encontraron registros para los filtros seleccionados.
                        </div>
                        <div v-else class="table-responsive">
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
                                            <a :href="`/helpdesk-tickets/${t.id}`">{{
                                                t.folio || "#" + t.id
                                            }}</a>
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

<script>
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { toastMessage, handleAxiosErrorToast } from "@/helpers/alerts.js";
import { formatDateTime } from "@/helpers/dateUtils.js";
import { helpdesk } from "@/helpers/api.js";

export default {
    data() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, "0");
        const dd = String(today.getDate()).padStart(2, "0");
        const todayStr = `${yyyy}-${mm}-${dd}`;
        return {
            loading: false,
            limit: 500,
            indicadores: null,
            detallado: [],
            sedes: [],
            estados: [],
            categorias: [],
            prioridades: [],
            filters: {
                fechaIni: todayStr,
                fechaFin: todayStr,
                sedeId: "",
                estadoId: "",
                categoriaId: "",
                prioridadId: "",
                solicitanteId: "",
                tecnicoId: "",
            },
        };
    },
    computed: {
        canExport() {
            return !!(this.filters.fechaIni && this.filters.fechaFin);
        },
    },
    async mounted() {
        // sede activa por defecto
        try {
            const sedeActiva = sessionStorage.getItem("ST-sede");
            if (sedeActiva) this.filters.sedeId = sedeActiva;
        } catch (e) {
            console.warn("No se pudo leer sede activa de sessionStorage", e);
        }
        await this.cargarCatalogos();
        await this.buscar();
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dt || "#helpdeskReportesDetallado");
    },
    methods: {
        formatDateTime(dt) {
            return formatDateTime(dt);
        },
        formatHoras(segundos) {
            if (!segundos) return "";
            const h = Number(segundos) / 3600;
            if (Number.isNaN(h)) return "";
            return h.toFixed(2);
        },
        async cargarCatalogos() {
            try {
                const [cats, prios, ests, sedes] = await Promise.all([
                    helpdesk.categorias(),
                    helpdesk.prioridades(),
                    helpdesk.estados(),
                    helpdesk.sedesActivas(),
                ]);
                this.categorias = (cats.data || []).filter((x) => x.activo);
                this.prioridades = (prios.data || []).filter((x) => x.activo);
                this.estados = (ests.data || []).filter((x) => x.activo);
                this.sedes = sedes.data || [];
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudieron cargar catálogos.");
            }
        },
        limpiar() {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, "0");
            const dd = String(today.getDate()).padStart(2, "0");
            const todayStr = `${yyyy}-${mm}-${dd}`;
            this.filters = {
                fechaIni: todayStr,
                fechaFin: todayStr,
                sedeId: "",
                estadoId: "",
                categoriaId: "",
                prioridadId: "",
                solicitanteId: "",
                tecnicoId: "",
            };
            try {
                const sedeActiva = sessionStorage.getItem("ST-sede");
                if (sedeActiva) this.filters.sedeId = sedeActiva;
            } catch (e) {
                console.warn("No se pudo leer sede activa de sessionStorage", e);
            }
            this.buscar();
        },
        buildParams(extra = {}) {
            return {
                fechaIni: this.filters.fechaIni,
                fechaFin: this.filters.fechaFin,
                sedeId: this.filters.sedeId || "",
                estadoId: this.filters.estadoId || "",
                categoriaId: this.filters.categoriaId || "",
                prioridadId: this.filters.prioridadId || "",
                solicitanteId: this.filters.solicitanteId || "",
                tecnicoId: this.filters.tecnicoId || "",
                ...extra,
            };
        },
        initDT() {
            safeInitDataTable(this.$refs.dt || "#helpdeskReportesDetallado", {
                dom: "Bfrtip",
                buttons: [
                    { extend: "copy", titleAttr: "Copiar al portapapeles" },
                    { extend: "csv", titleAttr: "Exportar a CSV" },
                    { extend: "excel", titleAttr: "Exportar a Excel" },
                    { extend: "pdf", titleAttr: "Exportar a PDF" },
                    { extend: "print", titleAttr: "Imprimir" },
                ],
                retrieve: true,
                order: [],
            });
        },
        async buscar() {
            if (!this.canExport) return;
            if (this.loading) return;
            if (
                this.filters.fechaIni &&
                this.filters.fechaFin &&
                this.filters.fechaFin < this.filters.fechaIni
            ) {
                toastMessage({
                    heading: "Upss!!!",
                    text: "La fecha 'Hasta' no puede ser anterior a 'Desde'.",
                    icon: "warning",
                });
                this.$nextTick(() => document.getElementById("reporteFechaFin")?.focus());
                return;
            }
            this.loading = true;
            try {
                const params = this.buildParams({ limit: this.limit });
                const [ind, det] = await Promise.all([
                    helpdesk.indicadores(params),
                    helpdesk.detallado(params),
                ]);
                this.indicadores = ind.data || null;
                this.detallado = det.data?.items || [];
                this.$nextTick(() => {
                    safeDestroyDataTable(this.$refs.dt || "#helpdeskReportesDetallado");
                    this.initDT();
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo generar el reporte.");
            } finally {
                this.loading = false;
            }
        },
        exportExcel() {
            const params = new URLSearchParams(this.buildParams());
            window.open(helpdesk.exportExcelUrl(params), "_blank", "noopener,noreferrer");
        },
        exportPdf() {
            const params = new URLSearchParams(this.buildParams());
            window.open(helpdesk.exportPdfUrl(params), "_blank", "noopener,noreferrer");
        },
    },
};
</script>
