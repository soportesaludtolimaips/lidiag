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
                                <label id="labelVista">Vista</label>
                                <div
                                    class="btn-group btn-group-sm d-flex"
                                    role="tablist"
                                    aria-labelledby="labelVista"
                                >
                                    <button
                                        type="button"
                                        role="tab"
                                        :aria-selected="scope === 'mine'"
                                        class="btn"
                                        :class="scope === 'mine' ? 'btn-info' : 'btn-outline-info'"
                                        @click="setScope('mine')"
                                    >
                                        Mis tickets
                                    </button>
                                    <button
                                        type="button"
                                        role="tab"
                                        :aria-selected="scope === 'assigned'"
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
                                        role="tab"
                                        :aria-selected="scope === 'all'"
                                        class="btn"
                                        :class="scope === 'all' ? 'btn-info' : 'btn-outline-info'"
                                        @click="setScope('all')"
                                    >
                                        Todos
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <label for="ticketEstado">Estado</label>
                                <select
                                    id="ticketEstado"
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
                                <label for="ticketCategoria">Categoría</label>
                                <select
                                    id="ticketCategoria"
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
                                <label for="ticketPrioridad">Prioridad</label>
                                <select
                                    id="ticketPrioridad"
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
                                <label for="ticketBusqueda">Búsqueda</label>
                                <input
                                    id="ticketBusqueda"
                                    v-model="filters.q"
                                    type="text"
                                    class="form-control"
                                    placeholder="Folio / título / descripción"
                                />
                            </div>
                        </div>

                        <div class="row p-t-2">
                            <div class="col-md-2">
                                <label for="ticketDesde">Desde</label>
                                <input
                                    id="ticketDesde"
                                    v-model="filters.desde"
                                    type="date"
                                    class="form-control"
                                />
                            </div>
                            <div class="col-md-2">
                                <label for="ticketHasta">Hasta</label>
                                <input
                                    id="ticketHasta"
                                    v-model="filters.hasta"
                                    type="date"
                                    class="form-control"
                                />
                            </div>
                            <div class="col-md-3">
                                <label for="ticketSede">Sede</label>
                                <select
                                    id="ticketSede"
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

                        <div v-if="loading" class="text-center p-5">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p class="m-t-10">Cargando tickets...</p>
                        </div>
                        <div v-else class="table-responsive">
                            <table
                                id="helpdeskTicketsTable"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <caption class="sr-only">
                                    Listado de tickets del helpdesk
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Folio</th>
                                        <th scope="col">Título</th>
                                        <th scope="col">Estado</th>
                                        <th scope="col">Prioridad</th>
                                        <th scope="col">Categoría</th>
                                        <th scope="col">Pendiente de</th>
                                        <th scope="col">Solicitante</th>
                                        <th scope="col">Asignado</th>
                                        <th scope="col">Sede</th>
                                        <th scope="col">Creación</th>
                                        <th scope="col">Actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!tickets.length">
                                        <td colspan="11" class="text-center text-muted">
                                            No se encontraron tickets.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="t in tickets"
                                        :key="t.id"
                                        :class="prioridadRowClass(t.prioridad?.nombre)"
                                    >
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
import { formatDateTime } from "@/helpers/dateUtils.js";
import { helpdesk } from "@/helpers/api.js";

export default {
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
        } catch (e) {
            console.warn("No se pudo leer sede activa de sessionStorage", e);
        }

        await this.cargarCatalogos();
        await this.buscar();
    },
    beforeUnmount() {
        this.destroyDataTable();
    },
    methods: {
        formatDateTime(dt) {
            return formatDateTime(dt);
        },
        prioridadRowClass(nombre) {
            if (!nombre) return "";
            const p = nombre.toLowerCase();
            if (p.includes("alta") || p.includes("critico") || p.includes("critica"))
                return "table-danger";
            if (p.includes("media") || p.includes("normal")) return "table-warning";
            return "";
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
            } catch (e) {
                console.warn("No se pudo leer sede activa de sessionStorage", e);
            }
            this.buscar();
        },
        initDataTable() {
            safeInitDataTable(this.$refs.dt || "#helpdeskTicketsTable", {
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
        destroyDataTable() {
            safeDestroyDataTable(this.$refs.dt || "#helpdeskTicketsTable");
        },
        async buscar() {
            this.loading = true;
            try {
                const res = await helpdesk.tickets({
                    scope: this.scope,
                    ...this.filters,
                });
                this.tickets = res.data || [];
                this.$nextTick(() => {
                    this.destroyDataTable();
                    this.initDataTable();
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudieron cargar los tickets.");
                this.tickets = [];
                this.$nextTick(() => {
                    this.destroyDataTable();
                    this.initDataTable();
                });
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
