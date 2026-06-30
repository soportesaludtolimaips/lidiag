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
                                <label>Desde</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="filters.fechaIni"
                                />
                            </div>
                            <div class="col-md-2">
                                <label>Hasta</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="filters.fechaFin"
                                />
                            </div>
                            <div class="col-md-3">
                                <label>Sede</label>
                                <select class="form-control custom-select" v-model="filters.sedeId">
                                    <option value="">Todas</option>
                                    <option v-for="s in sedes" :key="s.id" :value="s.id">
                                        {{ s.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Estado</label>
                                <select
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
                                <label>Categoría</label>
                                <select
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
                                <label>Prioridad</label>
                                <select
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
                                <label>Solicitante (ID)</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="filters.solicitanteId"
                                    placeholder="Opcional"
                                />
                            </div>
                            <div class="col-md-3">
                                <label>Técnico (ID)</label>
                                <input
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
                                <thead>
                                    <tr>
                                        <th>Estado</th>
                                        <th>Total</th>
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
                                <thead>
                                    <tr>
                                        <th>Mes</th>
                                        <th>Total</th>
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
                                <thead>
                                    <tr>
                                        <th>Categoría</th>
                                        <th>Total</th>
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
                                <thead>
                                    <tr>
                                        <th>Técnico</th>
                                        <th>Total</th>
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
                        <div class="table-responsive">
                            <table
                                id="helpdeskReportesDetallado"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Título</th>
                                        <th>Estado</th>
                                        <th>Prioridad</th>
                                        <th>Categoría</th>
                                        <th>Pendiente</th>
                                        <th>Sede</th>
                                        <th>Solicitante</th>
                                        <th>Técnico</th>
                                        <th>Creado</th>
                                        <th>Resuelto</th>
                                        <th>Horas</th>
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
import { handleAxiosErrorToast } from "@/helpers/alerts.js";

export default {
    props: ["usuarioactual"],
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
        } catch (_) {}
        await this.cargarCatalogos();
        await this.buscar();
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dt || "#helpdeskReportesDetallado");
    },
    methods: {
        formatDateTime(dt) {
            if (!dt) return "";
            try {
                return window.dayjs(dt).tz("America/Bogota").format("YYYY-MM-DD HH:mm");
            } catch (_) {
                return String(dt);
            }
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
                    axios.get("/api/helpdesk-categorias"),
                    axios.get("/api/helpdesk-prioridades"),
                    axios.get("/api/helpdesk-estados"),
                    axios.get("/api/config-sedes/listar/sedes/activas"),
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
            } catch (_) {}
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
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                retrieve: true,
                order: [],
            });
        },
        async buscar() {
            if (!this.canExport) return;
            if (this.loading) return;
            this.loading = true;
            try {
                safeDestroyDataTable(this.$refs.dt || "#helpdeskReportesDetallado");
                const params = this.buildParams({ limit: this.limit });
                const [ind, det] = await Promise.all([
                    axios.get("/api/helpdesk-reportes/indicadores", { params }),
                    axios.get("/api/helpdesk-reportes/detallado", { params }),
                ]);
                this.indicadores = ind.data || null;
                this.detallado = det.data?.items || [];
                this.$nextTick(() => this.initDT());
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo generar el reporte.");
            } finally {
                this.loading = false;
            }
        },
        exportExcel() {
            const params = new URLSearchParams(this.buildParams());
            window.open(
                `/api/helpdesk-reportes/export/excel?${params.toString()}`,
                "_blank",
                "noopener,noreferrer"
            );
        },
        exportPdf() {
            const params = new URLSearchParams(this.buildParams());
            window.open(
                `/api/helpdesk-reportes/export/pdf?${params.toString()}`,
                "_blank",
                "noopener,noreferrer"
            );
        },
    },
};
</script>
