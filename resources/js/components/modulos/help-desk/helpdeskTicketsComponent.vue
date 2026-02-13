<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Helpdesk</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
                    <li class="breadcrumb-item active">Tickets</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right">
                <a href="/helpdesk-tickets/crear" class="btn btn-info btn-sm">
                    <i class="fa fa-plus"></i> Crear ticket
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros
                        </h4>

                        <div class="row p-t-2">
                            <div class="col-md-3">
                                <label>Vista</label>
                                <div
                                    class="btn-group btn-group-sm d-flex"
                                    role="group"
                                    aria-label="Tabs"
                                >
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="scope === 'mine' ? 'btn-info' : 'btn-outline-info'"
                                        @click="setScope('mine')"
                                    >
                                        Mis tickets
                                    </button>
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="
                                            scope === 'assigned' ? 'btn-info' : 'btn-outline-info'
                                        "
                                        @click="setScope('assigned')"
                                    >
                                        Asignados a mí
                                    </button>
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="scope === 'all' ? 'btn-info' : 'btn-outline-info'"
                                        @click="setScope('all')"
                                    >
                                        Todos
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <label>Estado</label>
                                <select
                                    v-model="filters.estado_id"
                                    class="form-control custom-select"
                                >
                                    <option value="">Todos</option>
                                    <option v-for="e in estados" :key="e.id" :value="e.id">
                                        {{ e.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Categoría</label>
                                <select
                                    v-model="filters.categoria_id"
                                    class="form-control custom-select"
                                >
                                    <option value="">Todas</option>
                                    <option v-for="c in categorias" :key="c.id" :value="c.id">
                                        {{ c.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Prioridad</label>
                                <select
                                    v-model="filters.prioridad_id"
                                    class="form-control custom-select"
                                >
                                    <option value="">Todas</option>
                                    <option v-for="p in prioridades" :key="p.id" :value="p.id">
                                        {{ p.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Búsqueda</label>
                                <input
                                    v-model="filters.q"
                                    type="text"
                                    class="form-control"
                                    placeholder="Folio / título / descripción"
                                />
                            </div>
                        </div>

                        <div class="row p-t-2">
                            <div class="col-md-2">
                                <label>Desde</label>
                                <input v-model="filters.desde" type="date" class="form-control" />
                            </div>
                            <div class="col-md-2">
                                <label>Hasta</label>
                                <input v-model="filters.hasta" type="date" class="form-control" />
                            </div>
                            <div class="col-md-3">
                                <label>Sede</label>
                                <select
                                    v-model="filters.sede_id"
                                    class="form-control custom-select"
                                >
                                    <option value="">Todas</option>
                                    <option v-for="s in sedes" :key="s.id" :value="s.id">
                                        {{ s.nom_sede }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-5 d-flex align-items-end">
                                <button
                                    class="btn btn-success m-r-5"
                                    type="button"
                                    :disabled="loading"
                                    @click="buscar()"
                                >
                                    <i v-if="loading" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-search"></i>
                                    Buscar
                                </button>
                                <button
                                    class="btn btn-inverse"
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

        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de tickets</h4>
                        <h6 class="card-subtitle text-info">
                            Orden por defecto: Prioridad (Alta→Baja) y luego fecha.
                        </h6>

                        <div class="table-responsive">
                            <table
                                id="helpdeskTicketsTable"
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
                                        <th>Pendiente de</th>
                                        <th>Solicitante</th>
                                        <th>Asignado</th>
                                        <th>Sede</th>
                                        <th>Creación</th>
                                        <th>Actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="t in tickets" :key="t.id">
                                        <td>
                                            <a :href="`/helpdesk-tickets/${t.id}`">{{
                                                t.folio || "#" + t.id
                                            }}</a>
                                        </td>
                                        <td>{{ t.titulo }}</td>
                                        <td>{{ t.estado?.nombre || "" }}</td>
                                        <td>{{ t.prioridad?.nombre || "" }}</td>
                                        <td>{{ t.categoria?.nombre || "" }}</td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="
                                                    t.pendiente_de === 'SOPORTE'
                                                        ? 'badge-warning'
                                                        : 'badge-primary'
                                                "
                                            >
                                                {{ t.pendiente_de || "" }}
                                            </span>
                                        </td>
                                        <td>{{ t.solicitante?.name || "" }}</td>
                                        <td>{{ t.asignado?.name || "" }}</td>
                                        <td>{{ t.sede?.nom_sede || "" }}</td>
                                        <td>{{ formatDateTime(t.created_at) }}</td>
                                        <td>{{ formatDateTime(t.updated_at) }}</td>
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
        return {
            scope: "mine",
            loading: false,
            tickets: [],
            categorias: [],
            prioridades: [],
            estados: [],
            sedes: [],
            filters: {
                estado_id: "",
                categoria_id: "",
                prioridad_id: "",
                sede_id: "",
                desde: "",
                hasta: "",
                q: "",
            },
        };
    },
    async mounted() {
        try {
            // sede activa por defecto
            const sedeActiva = sessionStorage.getItem("ST-sede");
            if (sedeActiva) this.filters.sede_id = sedeActiva;
        } catch (e) {}

        await this.cargarCatalogos();
        await this.buscar();
    },
    beforeUnmount() {
        this.destroyDataTable();
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
        setScope(scope) {
            this.scope = scope;
            this.buscar();
        },
        limpiar() {
            this.filters = {
                estado_id: "",
                categoria_id: "",
                prioridad_id: "",
                sede_id: "",
                desde: "",
                hasta: "",
                q: "",
            };
            try {
                const sedeActiva = sessionStorage.getItem("ST-sede");
                if (sedeActiva) this.filters.sede_id = sedeActiva;
            } catch (e) {}
            this.buscar();
        },
        initDataTable() {
            safeInitDataTable(this.$refs.dt || "#helpdeskTicketsTable", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                retrieve: true,
                order: [],
            });
        },
        destroyDataTable() {
            safeDestroyDataTable(this.$refs.dt || "#helpdeskTicketsTable");
        },
        async buscar() {
            this.loading = true;
            try {
                this.destroyDataTable();
                const res = await axios.get("/api/helpdesk-tickets", {
                    params: {
                        scope: this.scope,
                        ...this.filters,
                    },
                });
                this.tickets = res.data || [];
                this.$nextTick(() => this.initDataTable());
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudieron cargar los tickets.");
                this.tickets = [];
                this.$nextTick(() => this.initDataTable());
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
