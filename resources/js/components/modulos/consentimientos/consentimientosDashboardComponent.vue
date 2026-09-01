<template>
    <div class="ci-dash">
        <!-- Encabezado -->
        <div class="ci-header mb-3">
            <div class="ci-header-icon"><i class="fa fa-bar-chart"></i></div>
            <div class="me-auto">
                <h4 class="mb-0 fw-bold">Dashboard de consentimientos</h4>
                <small class="text-muted">Indicadores de generación, firma y productividad.</small>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card ci-card shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Sede</label>
                        <select v-model="filtros.sede_id" class="form-select">
                            <option :value="null">Todas</option>
                            <option v-for="s in sedes" :key="s.id" :value="s.id">{{ s.nom_sede }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Producto</label>
                        <select v-model="filtros.producto_id" class="form-select">
                            <option :value="null">Todos</option>
                            <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nom_produc }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Desde</label>
                        <input v-model="filtros.fecha_inicio" type="date" class="form-control" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Hasta</label>
                        <input v-model="filtros.fecha_fin" type="date" class="form-control" />
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary w-100" type="button" :disabled="cargando" @click="cargar">
                            <span v-if="cargando" class="spinner-border spinner-border-sm"></span>
                            <span v-else><i class="fa fa-filter me-1"></i>Aplicar</span>
                        </button>
                    </div>
                    <div class="col-md-1">
                        <a class="btn btn-outline-success w-100" :href="exportarUrl" title="Exportar a CSV">
                            <i class="fa fa-file-excel-o me-1"></i>CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas resumen -->
        <div class="row g-3 mb-3">
            <div v-for="c in tarjetas" :key="c.label" class="col-6 col-md-3">
                <div class="stat-card" :class="'stat-' + c.color">
                    <div class="stat-icon"><i :class="'fa ' + c.icon"></i></div>
                    <div>
                        <div class="stat-num">{{ c.valor }}</div>
                        <div class="stat-lbl">{{ c.label }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Tiempos promedio -->
            <div class="col-md-6">
                <div class="card ci-card h-100 shadow-sm">
                    <div class="card-body">
                        <h6 class="ci-title"><i class="fa fa-clock-o text-primary me-1"></i>Tiempos promedio (min)</h6>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Firma paciente</span>
                            <strong>{{ tiempos.minutos_promedio_firma_paciente ?? "—" }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Firma médico</span>
                            <strong>{{ tiempos.minutos_promedio_firma_medico ?? "—" }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos populares -->
            <div class="col-md-6">
                <div class="card ci-card h-100 shadow-sm">
                    <div class="card-body">
                        <h6 class="ci-title"><i class="fa fa-star text-warning me-1"></i>Productos más usados</h6>
                        <ul class="list-group list-group-flush">
                            <li
                                v-for="p in populares"
                                :key="p.producto_id"
                                class="list-group-item d-flex justify-content-between align-items-center px-0"
                            >
                                <span class="text-truncate me-2">{{
                                    p.producto?.nom_produc || "Producto " + p.producto_id
                                }}</span>
                                <span class="badge bg-primary rounded-pill">{{ p.total }}</span>
                            </li>
                            <li v-if="!populares.length" class="list-group-item text-muted px-0">Sin datos</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Por médico -->
            <div class="col-12">
                <div class="card ci-card shadow-sm">
                    <div class="card-body">
                        <h6 class="ci-title"><i class="fa fa-user-md text-primary me-1"></i>Por médico</h6>
                        <div class="table-responsive">
                            <table class="table ci-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Médico</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Pendientes</th>
                                        <th class="text-center">Completos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="m in porMedico" :key="m.medico_id">
                                        <td class="fw-semibold">{{ m.medico?.name || "#" + m.medico_id }}</td>
                                        <td class="text-center">{{ m.total }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-warning-subtle text-warning rounded-pill">{{
                                                m.pendientes
                                            }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success rounded-pill">{{
                                                m.completos
                                            }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="!porMedico.length">
                                        <td colspan="4" class="text-muted text-center py-3">Sin datos</td>
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
import { computed, onMounted, reactive, ref } from "vue";

import { consentimientos as api } from "../../../helpers/api.js";
import { handleAxiosErrorToast } from "../../../helpers/alerts.js";

const sedes = ref([]);
const productos = ref([]);
const filtros = reactive({ sede_id: null, producto_id: null, fecha_inicio: "", fecha_fin: "" });

const cargando = ref(false);
const resumen = reactive({
    total_generados: 0,
    completos: 0,
    expirados: 0,
    pendientes: 0,
    tasa_completado: 0,
});
const tiempos = reactive({
    minutos_promedio_firma_paciente: null,
    minutos_promedio_firma_medico: null,
});
const populares = ref([]);
const porMedico = ref([]);

const tarjetas = computed(() => [
    { label: "Generados", valor: resumen.total_generados, color: "primary", icon: "fa-file-text-o" },
    { label: "Completos", valor: resumen.completos, color: "success", icon: "fa-check-circle" },
    { label: "Expirados", valor: resumen.expirados, color: "danger", icon: "fa-times-circle" },
    { label: "% Completado", valor: resumen.tasa_completado + "%", color: "info", icon: "fa-percent" },
]);

function params() {
    const p = {};
    if (filtros.sede_id) p.sede_id = filtros.sede_id;
    if (filtros.producto_id) p.producto_id = filtros.producto_id;
    if (filtros.fecha_inicio) p.fecha_inicio = filtros.fecha_inicio;
    if (filtros.fecha_fin) p.fecha_fin = filtros.fecha_fin;
    return p;
}

const exportarUrl = computed(() => api.estadExportarUrl(params()));

async function cargarCombos() {
    try {
        const [rp, rs] = await Promise.all([api.productos(), api.sedesActivas()]);
        productos.value = rp.data?.data ?? rp.data ?? [];
        sedes.value = rs.data?.data ?? rs.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar catálogos.");
    }
}

async function cargar() {
    cargando.value = true;
    try {
        const p = params();
        const [rr, rt, rp, rm] = await Promise.all([
            api.estadResumen(p),
            api.estadTiempo(p),
            api.estadPopulares(p),
            api.estadMedico(p),
        ]);
        Object.assign(resumen, rr.data.data);
        Object.assign(tiempos, rt.data.data);
        populares.value = rp.data?.data ?? [];
        porMedico.value = rm.data?.data ?? [];
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar estadísticas.");
    } finally {
        cargando.value = false;
    }
}

onMounted(async () => {
    await cargarCombos();
    await cargar();
});
</script>

<style scoped>
.ci-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
    border: 1px solid #e5e7eb;
    border-left: 4px solid var(--bs-primary, #0d6efd);
    border-radius: 0.6rem;
}
.ci-header-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: var(--bs-primary, #0d6efd);
    color: #fff;
    font-size: 1.25rem;
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.25);
}
.ci-card {
    border: 1px solid #eef0f4;
    border-radius: 0.6rem;
}
.ci-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
}
/* Tarjetas de estadística */
.stat-card {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    padding: 1rem 1.15rem;
    border-radius: 0.7rem;
    background: #fff;
    border: 1px solid #eef0f4;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    height: 100%;
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 1.35rem;
    color: #fff;
    flex-shrink: 0;
}
.stat-num {
    font-size: 1.7rem;
    font-weight: 700;
    line-height: 1;
    color: #1f2937;
}
.stat-lbl {
    font-size: 0.8rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-top: 0.2rem;
}
.stat-primary .stat-icon {
    background: #0d6efd;
}
.stat-primary {
    border-left: 4px solid #0d6efd;
}
.stat-success .stat-icon {
    background: #198754;
}
.stat-success {
    border-left: 4px solid #198754;
}
.stat-danger .stat-icon {
    background: #dc3545;
}
.stat-danger {
    border-left: 4px solid #dc3545;
}
.stat-info .stat-icon {
    background: #0dcaf0;
}
.stat-info {
    border-left: 4px solid #0dcaf0;
}
.ci-table thead th {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6b7280;
    background: #f8fafc;
    border-bottom: 2px solid #e5e7eb;
    padding: 0.55rem 0.75rem;
}
.ci-table tbody td {
    padding: 0.55rem 0.75rem;
    border-bottom: 1px solid #f1f3f5;
}
.ci-table tbody tr:hover {
    background: #f6f9ff;
}
</style>
