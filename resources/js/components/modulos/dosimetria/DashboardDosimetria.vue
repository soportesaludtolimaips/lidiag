<template>
    <div class="container-fluid py-3 dosimetria-dashboard">
        <!-- 1. Encabezado Clínico y Selector de Sede -->
        <div class="row align-items-center mb-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-2">
                    <h3 class="mb-0 fw-bold text-primary-clinic">
                        <i class="fa fa-radiation text-warning me-2"></i>Dosimetría y Control de
                        Radiación
                    </h3>
                    <span class="badge bg-dark-subtle text-muted border border-secondary px-2 py-1">
                        Res. 482 de 2018 | ISO 27001
                    </span>
                </div>
                <p class="text-muted small mb-0 mt-1">
                    Monitoreo Edge-to-Cloud de dosis ionizante y Niveles de Referencia para
                    Diagnóstico (NRD) por sede.
                </p>
            </div>
            <div class="col-md-5 text-md-end mt-2 mt-md-0">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-success me-2"
                    :disabled="loading || exportando"
                    @click="exportarExcel"
                >
                    <i class="fa fa-file-excel-o me-1"></i> Exportar Excel
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary me-2"
                    :disabled="loading"
                    @click="cargarDatos"
                >
                    <i class="fa fa-refresh me-1" :class="{ 'fa-spin': loading }"></i> Actualizar
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-info"
                    @click="mostrarModalUmbrales = true"
                >
                    <i class="fa fa-sliders me-1"></i> Ver Umbrales NRD
                </button>
            </div>
        </div>

        <!-- 2. Tarjetas de KPIs -->
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm kpi-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon bg-info-subtle text-info me-3">
                            <i class="fa fa-heartbeat fa-lg"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold"
                                >Estudios Monitoreados</span
                            >
                            <h4 class="mb-0 fw-bold">
                                {{ kpis.total_estudios?.toLocaleString() || 0 }}
                            </h4>
                            <span class="badge bg-light text-muted small mt-1">En el periodo</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm kpi-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon bg-primary-subtle text-primary me-3">
                            <i class="fa fa-desktop fa-lg"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold"
                                >Equipos Emisores</span
                            >
                            <h4 class="mb-0 fw-bold">{{ kpis.total_equipos || 0 }}</h4>
                            <span class="badge bg-light text-muted small mt-1"
                                >CT, MG, DX activos</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div
                    class="card h-100 border-0 shadow-sm kpi-card"
                    :class="{ 'border-danger': (kpis.total_alertas || 0) > 0 }"
                >
                    <div class="card-body d-flex align-items-center">
                        <div
                            class="kpi-icon me-3"
                            :class="
                                (kpis.total_alertas || 0) > 0
                                    ? 'bg-danger text-white'
                                    : 'bg-success-subtle text-success'
                            "
                        >
                            <i class="fa fa-shield fa-lg"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold"
                                >Superaciones NRD</span
                            >
                            <h4
                                class="mb-0 fw-bold"
                                :class="
                                    (kpis.total_alertas || 0) > 0 ? 'text-danger' : 'text-success'
                                "
                            >
                                {{ kpis.total_alertas || 0 }}
                            </h4>
                            <span
                                class="badge small mt-1"
                                :class="(kpis.total_alertas || 0) > 0 ? 'bg-danger' : 'bg-success'"
                            >
                                {{
                                    (kpis.total_alertas || 0) > 0
                                        ? "Requiere auditoría"
                                        : "En rangos seguros"
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm kpi-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="kpi-icon bg-warning-subtle text-warning me-3">
                            <i class="fa fa-lock fa-lg"></i>
                        </div>
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold"
                                >Seguridad ISO 27001</span
                            >
                            <h4 class="mb-0 fw-bold text-dark">HMAC-SHA256</h4>
                            <span class="badge bg-success small mt-1"
                                ><i class="fa fa-check me-1"></i>Salt por Sede (Anti-Rainbow)</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2.5 Monitor de Latidos Edge (Dead Man's Switch) -->
        <div class="card shadow-sm border-0 mb-3 heartbeat-card">
            <div
                class="card-body py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2"
            >
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-heartbeat text-danger"></i>
                    <span class="small fw-bold text-uppercase">Monitor de Latidos Edge:</span>
                    <span class="small text-muted">
                        {{ kpis.sedes_online || 0 }} de {{ kpis.sedes_total || sedes.length }} sedes
                        conectadas
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span
                        v-for="s in sedes"
                        :key="'hb-' + s.id"
                        class="badge d-inline-flex align-items-center gap-1 px-2 py-1"
                        :class="getSedeConexionBadgeClass(s.estado_conexion)"
                        :title="getSedeConexionTooltip(s)"
                    >
                        <span class="status-dot" :class="s.estado_conexion?.toLowerCase()"></span>
                        {{ s.nom_sede }}
                        <small v-if="s.minutos_sin_contacto !== null" class="opacity-75">
                            ({{ s.minutos_sin_contacto }}m)
                        </small>
                    </span>
                </div>
            </div>
        </div>

        <!-- 3. Panel de Alertas Semánticas NRD (Resolución 482) -->
        <div v-if="alertas.length > 0" class="alert alert-danger shadow-sm border-danger mb-3 p-3">
            <div class="d-flex align-items-start">
                <i class="fa fa-exclamation-triangle fa-2x me-3 text-danger mt-1"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading fw-bold mb-1">
                        Alerta de Radioprotección — Se detectaron promedios superiores a los Niveles
                        de Referencia para Diagnóstico (NRD)
                    </h5>
                    <p class="small mb-2 text-danger-emphasis">
                        De acuerdo con la Resolución 482 de 2018, la institución debe revisar los
                        protocolos técnicos de adquisición y calibración de los equipos:
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <span
                            v-for="(al, idx) in alertas"
                            :key="'alert-' + idx"
                            class="badge bg-danger p-2 text-white"
                        >
                            <strong>{{ al.nom_sede }}</strong> | {{ al.modalidad }} ({{
                                al.tipo_metrica
                            }}): Promedio {{ al.dosis_promedio }} {{ al.unidad_medida }} (Límite
                            NRD: {{ al.limite_nrd }} {{ al.unidad_medida }})
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Filtros Interactivos -->
        <div class="card shadow-sm border-0 mb-3 filter-card">
            <div class="card-body py-2 px-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Sede Hospitalaria</label>
                        <select
                            v-model="filtros.sede_id"
                            class="form-select form-select-sm"
                            @change="cargarDatos"
                        >
                            <option value="">Todas las Sedes</option>
                            <option v-for="s in sedes" :key="s.id" :value="s.id">
                                {{ s.nom_sede }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Modalidad</label>
                        <select
                            v-model="filtros.modalidad"
                            class="form-select form-select-sm"
                            @change="cargarDatos"
                        >
                            <option value="TODAS">Todas (CT, MG, DX)</option>
                            <option value="CT">Tomografía (CT)</option>
                            <option value="MG">Mamografía (MG)</option>
                            <option value="DX">Rayos X Digital (DX)</option>
                            <option value="CR">Rayos X Computarizado (CR)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Métrica Dosis</label>
                        <select
                            v-model="filtros.tipo_metrica"
                            class="form-select form-select-sm"
                            @change="cargarDatos"
                        >
                            <option value="TODAS">Todas las Métricas</option>
                            <option value="CTDIvol">CTDIvol (mGy)</option>
                            <option value="DLP">DLP (mGy·cm)</option>
                            <option value="DGM">DGM (mGy)</option>
                            <option value="DAP">DAP (Gy·cm²)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Desde</label>
                        <input
                            v-model="filtros.fecha_desde"
                            type="date"
                            class="form-control form-control-sm"
                            @change="cargarDatos"
                        />
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Hasta</label>
                        <input
                            v-model="filtros.fecha_hasta"
                            type="date"
                            class="form-control form-control-sm"
                            @change="cargarDatos"
                        />
                    </div>

                    <div class="col-md-1 text-end">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary w-100"
                            title="Limpiar filtros"
                            @click="limpiarFiltros"
                        >
                            <i class="fa fa-eraser"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Gráficos Analíticos de Dosimetría con Chart.js -->
        <!-- Fila 1: Barras Comparativas vs NRD y Torta de Modalidades -->
        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="card h-100 shadow-sm border-0 chart-card">
                    <div
                        class="card-header bg-transparent py-2 d-flex justify-content-between align-items-center"
                    >
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-bar-chart text-primary me-2"></i>Dosis Promedio por Sede
                            vs Límite NRD
                        </h6>
                        <span class="badge bg-secondary-subtle text-muted small"
                            >Promedio del Periodo</span
                        >
                    </div>
                    <div class="card-body">
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted small mt-2">Cargando telemetría analítica...</p>
                        </div>
                        <div
                            v-else-if="chartDataBarras.labels && chartDataBarras.labels.length > 0"
                            class="chart-wrapper"
                        >
                            <Bar :data="chartDataBarras" :options="chartOptionsBarras" />
                        </div>
                        <div v-else class="text-center py-5 text-muted">
                            <i class="fa fa-info-circle fa-2x mb-2 text-secondary"></i>
                            <p class="mb-0">
                                No se encontraron registros de dosis para los filtros seleccionados.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100 shadow-sm border-0 chart-card">
                    <div
                        class="card-header bg-transparent py-2 d-flex justify-content-between align-items-center"
                    >
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-pie-chart text-info me-2"></i>Distribución por Modalidad
                        </h6>
                        <span class="badge bg-secondary-subtle text-muted small"
                            >Estudios Registrados</span
                        >
                    </div>
                    <div class="card-body">
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-info" role="status"></div>
                            <p class="text-muted small mt-2">Calculando modalidades...</p>
                        </div>
                        <div
                            v-else-if="
                                chartDataModalidades.labels &&
                                chartDataModalidades.labels.length > 0
                            "
                            class="chart-wrapper"
                        >
                            <Doughnut
                                :data="chartDataModalidades"
                                :options="chartOptionsModalidades"
                            />
                        </div>
                        <div v-else class="text-center py-5 text-muted">
                            <i class="fa fa-info-circle fa-2x mb-2 text-secondary"></i>
                            <p class="mb-0">Sin datos de modalidades para el rango actual.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 2: Torta de Cumplimiento Regulatorio NRD y Tendencia Temporal -->
        <div class="row g-3 mb-3">
            <div class="col-lg-5">
                <div class="card h-100 shadow-sm border-0 chart-card">
                    <div
                        class="card-header bg-transparent py-2 d-flex justify-content-between align-items-center"
                    >
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-pie-chart text-warning me-2"></i>Cumplimiento Normativo
                            NRD
                        </h6>
                        <span class="badge bg-secondary-subtle text-muted small"
                            >Res. 482 de 2018</span
                        >
                    </div>
                    <div class="card-body">
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-warning" role="status"></div>
                            <p class="text-muted small mt-2">Evaluando niveles de radiación...</p>
                        </div>
                        <div
                            v-else-if="
                                chartDataCumplimiento.labels &&
                                chartDataCumplimiento.labels.length > 0
                            "
                            class="chart-wrapper"
                        >
                            <Doughnut
                                :data="chartDataCumplimiento"
                                :options="chartOptionsCumplimiento"
                            />
                        </div>
                        <div v-else class="text-center py-5 text-muted">
                            <i class="fa fa-info-circle fa-2x mb-2 text-secondary"></i>
                            <p class="mb-0">Sin evaluaciones NRD en el periodo.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card h-100 shadow-sm border-0 chart-card">
                    <div
                        class="card-header bg-transparent py-2 d-flex justify-content-between align-items-center"
                    >
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-line-chart text-success me-2"></i>Tendencia Mensual de
                            Dosis
                        </h6>
                        <span class="badge bg-secondary-subtle text-muted small">Evolución</span>
                    </div>
                    <div class="card-body">
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-success" role="status"></div>
                            <p class="text-muted small mt-2">Calculando tendencias...</p>
                        </div>
                        <div
                            v-else-if="chartDataLineas.labels && chartDataLineas.labels.length > 0"
                            class="chart-wrapper"
                        >
                            <Line :data="chartDataLineas" :options="chartOptionsLineas" />
                        </div>
                        <div v-else class="text-center py-5 text-muted">
                            <i class="fa fa-info-circle fa-2x mb-2 text-secondary"></i>
                            <p class="mb-0">Sin datos de tendencia para el rango actual.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Tabla de Últimos Registros Dosimétricos Recibidos -->
        <div class="card shadow-sm border-0">
            <div
                class="card-header bg-transparent py-2 d-flex justify-content-between align-items-center"
            >
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-table text-info me-2"></i>Últimas Mediciones de Dosis Recibidas
                    (Edge)
                </h6>
                <span class="badge bg-light text-muted small">Últimos 50 estudios</span>
            </div>
            <div class="card-body p-0">
                <!-- Skeleton Loader durante la carga para CLS = 0 -->
                <table-skeleton-loader :visible="loading" :rows="6" :columns="8" />

                <!-- Tabla de datos -->
                <div v-if="!loading && ultimosRegistros.length > 0" class="table-responsive">
                    <table class="table table-hover align-middle mb-0 clinical-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Fecha Estudio</th>
                                <th>Sede</th>
                                <th>Equipo / AET</th>
                                <th>Modalidad</th>
                                <th>Métrica</th>
                                <th>Valor Dosis</th>
                                <th>Patient ID (Hash)</th>
                                <th class="text-center">Estado NRD</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="reg in ultimosRegistros" :key="reg.id">
                                <td class="ps-3 text-nowrap">
                                    <small>{{ formatearFecha(reg.fecha_estudio) }}</small>
                                </td>
                                <td>
                                    <strong>{{ reg.nom_sede }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{
                                        reg.equipo_nombre
                                    }}</span>
                                </td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="getModalidadBadgeClass(reg.modalidad)"
                                    >
                                        {{ reg.modalidad }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ reg.tipo_metrica }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold fs-6">{{ reg.valor_dosis }}</span>
                                    <small class="text-muted ms-1">{{ reg.unidad_medida }}</small>
                                </td>
                                <td>
                                    <code class="text-muted small" :title="reg.patient_id_hashed">
                                        {{ reg.patient_id_hashed.substring(0, 10) }}...
                                    </code>
                                </td>
                                <td class="text-center">
                                    <span class="badge" :class="getEstadoNrdClass(reg)">
                                        {{ getEstadoNrdLabel(reg) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State si no hay registros -->
                <div v-if="!loading && ultimosRegistros.length === 0" class="py-5 text-center">
                    <empty-state-component
                        title="Sin registros de dosimetría"
                        message="No se han recibido telemetrías de radiación para los filtros seleccionados."
                    />
                </div>
            </div>
        </div>

        <!-- 7. Modal de Catálogo de Umbrales NRD Regulatorios -->
        <div
            v-if="mostrarModalUmbrales"
            class="modal fade show d-block"
            tabindex="-1"
            style="background-color: rgba(0, 0, 0, 0.5)"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title">
                            <i class="fa fa-sliders text-warning me-2"></i>Niveles de Referencia
                            para Diagnóstico (Resolución 482 de 2018)
                        </h5>
                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            @click="mostrarModalUmbrales = false"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">
                            Los NRD constituyen una herramienta de optimización de la protección
                            radiológica médica (IAEA/ICRP). Cuando los valores promedio
                            institucionales superan de forma reiterada estos límites, se requiere
                            auditoría médica.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Modalidad</th>
                                        <th>Métrica</th>
                                        <th>Nivel Alerta (Preventivo)</th>
                                        <th>Límite NRD Regulatorio</th>
                                        <th>Unidad</th>
                                        <th>Descripción / Procedimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="u in umbralesNrd" :key="u.id">
                                        <td>
                                            <span class="badge bg-secondary">{{
                                                u.modalidad
                                            }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ u.tipo_metrica }}</strong>
                                        </td>
                                        <td class="text-warning fw-bold">{{ u.valor_alerta }}</td>
                                        <td class="text-danger fw-bold fs-6">
                                            {{ u.valor_limite_nrd }}
                                        </td>
                                        <td>{{ u.unidad_medida }}</td>
                                        <td class="small">{{ u.descripcion }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="mostrarModalUmbrales = false"
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
/**
 * Componente Analítico de Dosimetría y Niveles de Referencia para Diagnóstico (NRD).
 * Cumple con la Resolución 482 de 2018 (Colombia) y lineamientos de seguridad ISO/IEC 27001.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-23
 */

import { ref, reactive, computed, onMounted, onBeforeUnmount, getCurrentInstance } from "vue";
import axios from "axios";
import { Bar, Line, Doughnut } from "vue-chartjs";
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
} from "chart.js";

// Registrar componentes de Chart.js
ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement
);

const { appContext } = getCurrentInstance() || {};
const emitter = appContext?.config?.globalProperties?.emitter;

// Estado reactivo
const loading = ref(false);
const exportando = ref(false);
const mostrarModalUmbrales = ref(false);

const filtros = reactive({
    sede_id: "",
    modalidad: "TODAS",
    tipo_metrica: "TODAS",
    fecha_desde: "",
    fecha_hasta: "",
});

const kpis = ref({
    total_estudios: 0,
    total_equipos: 0,
    total_alertas: 0,
});

const consolidado = ref([]);
const alertas = ref([]);
const ultimosRegistros = ref([]);
const umbralesNrd = ref([]);
const sedes = ref([]);

// Mapa reactivo de umbrales para lookup rápido
const mapaUmbrales = computed(() => {
    const map = {};
    umbralesNrd.value.forEach((u) => {
        map[`${u.modalidad}_${u.tipo_metrica}`] = u;
    });
    return map;
});

// Helper de estado de conexión para monitor de latidos
function getSedeConexionBadgeClass(estado) {
    switch (estado) {
        case "ONLINE":
            return "bg-success-subtle text-success border border-success";
        case "DELAYED":
            return "bg-warning-subtle text-warning border border-warning";
        default:
            return "bg-danger-subtle text-danger border border-danger";
    }
}

function getSedeConexionTooltip(sede) {
    if (!sede) return "";
    const estado = sede.estado_conexion || "OFFLINE";
    const mins =
        sede.minutos_sin_contacto !== null && sede.minutos_sin_contacto !== undefined
            ? `${sede.minutos_sin_contacto} min`
            : "Sin contacto";
    const ult = sede.ultimo_contacto || "Nunca";
    const version = sede.version_agente ? ` | v${sede.version_agente}` : "";
    const pend = sede.pendientes !== undefined ? ` | ${sede.pendientes} pendientes` : "";
    return `Estado: ${estado} (${mins}) | Último latido: ${ult}${version}${pend}`;
}

// Helper de clases de modalidad
function getModalidadBadgeClass(mod) {
    switch (mod) {
        case "CT":
            return "bg-primary text-white";
        case "MG":
            return "bg-danger text-white";
        case "DX":
        case "CR":
            return "bg-info text-dark";
        default:
            return "bg-secondary text-white";
    }
}

// Helper de estado NRD para filas individuales
function getEstadoNrdClass(reg) {
    const umbral = mapaUmbrales.value[`${reg.modalidad}_${reg.tipo_metrica}`];
    if (!umbral) return "bg-success-subtle text-success border border-success";

    const valor = parseFloat(reg.valor_dosis);
    if (valor >= parseFloat(umbral.valor_limite_nrd)) {
        return "bg-danger text-white";
    }
    if (valor >= parseFloat(umbral.valor_alerta)) {
        return "bg-warning text-dark";
    }
    return "bg-success text-white";
}

function getEstadoNrdLabel(reg) {
    const umbral = mapaUmbrales.value[`${reg.modalidad}_${reg.tipo_metrica}`];
    if (!umbral) return "NORMAL";

    const valor = parseFloat(reg.valor_dosis);
    if (valor >= parseFloat(umbral.valor_limite_nrd)) {
        return "SUPERÓ NRD";
    }
    if (valor >= parseFloat(umbral.valor_alerta)) {
        return "ADVERTENCIA";
    }
    return "SEGURO";
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return "-";
    const d = new Date(fechaStr);
    return isNaN(d.getTime())
        ? fechaStr
        : d.toLocaleDateString("es-CO", {
              year: "numeric",
              month: "short",
              day: "2-digit",
              hour: "2-digit",
              minute: "2-digit",
          });
}

// Configuración de Chart.js para Barras Comparativas por Sede
const chartDataBarras = computed(() => {
    const sedesMap = {};
    consolidado.value.forEach((c) => {
        if (!sedesMap[c.nom_sede]) {
            sedesMap[c.nom_sede] = { total: 0, count: 0, maxLimite: 0 };
        }
        sedesMap[c.nom_sede].total += parseFloat(c.dosis_promedio || 0);
        sedesMap[c.nom_sede].count += 1;
        if (c.limite_nrd) {
            sedesMap[c.nom_sede].maxLimite = Math.max(
                sedesMap[c.nom_sede].maxLimite,
                parseFloat(c.limite_nrd)
            );
        }
    });

    const labels = Object.keys(sedesMap);
    const dataPromedios = labels.map((l) =>
        sedesMap[l].count > 0 ? (sedesMap[l].total / sedesMap[l].count).toFixed(2) : 0
    );
    const dataLimites = labels.map((l) => sedesMap[l].maxLimite || null);

    return {
        labels,
        datasets: [
            {
                label: "Dosis Promedio Registrada",
                backgroundColor: "#0284c7",
                borderColor: "#0369a1",
                borderWidth: 1,
                data: dataPromedios,
            },
            {
                label: "Nivel de Referencia (NRD Máximo)",
                backgroundColor: "rgba(239, 68, 68, 0.35)",
                borderColor: "#dc2626",
                borderWidth: 2,
                type: "line",
                data: dataLimites,
            },
        ],
    };
});

const chartOptionsBarras = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "top",
        },
        tooltip: {
            mode: "index",
            intersect: false,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            title: {
                display: true,
                text: "Magnitud Dosimétrica (mGy / Gy·cm²)",
            },
        },
    },
};

// Configuración de Chart.js para Tendencia Temporal
const chartDataLineas = computed(() => {
    const periodosMap = {};
    consolidado.value.forEach((c) => {
        const periodo = c.mes_periodo;
        if (!periodosMap[periodo]) {
            periodosMap[periodo] = { total: 0, count: 0 };
        }
        periodosMap[periodo].total += parseFloat(c.dosis_promedio || 0);
        periodosMap[periodo].count += 1;
    });

    const labels = Object.keys(periodosMap).sort();
    const dataPuntos = labels.map((p) =>
        periodosMap[p].count > 0 ? (periodosMap[p].total / periodosMap[p].count).toFixed(2) : 0
    );

    return {
        labels,
        datasets: [
            {
                label: "Evolución Dosis Promedio",
                borderColor: "#10b981",
                backgroundColor: "rgba(16, 185, 129, 0.15)",
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6,
                data: dataPuntos,
            },
        ],
    };
});

const chartOptionsLineas = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "top",
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            title: {
                display: true,
                text: "Dosis Promedio Mensual",
            },
        },
    },
};

// Configuración de Chart.js para Distribución por Modalidad (Torta / Doughnut)
const chartDataModalidades = computed(() => {
    const modMap = {};
    consolidado.value.forEach((c) => {
        const mod = c.modalidad || "OTRA";
        if (!modMap[mod]) {
            modMap[mod] = 0;
        }
        modMap[mod] += parseInt(c.total_estudios || 0, 10);
    });

    const keys = Object.keys(modMap);
    if (keys.length === 0) {
        return { labels: [], datasets: [] };
    }

    const labelMap = {
        CT: "Tomografía (CT)",
        DX: "Rayos X Digital (DX)",
        CR: "Rayos X Comp. (CR)",
        MG: "Mamografía (MG)",
    };

    const colorMap = {
        CT: "#0284c7",
        DX: "#06b6d4",
        CR: "#3b82f6",
        MG: "#ec4899",
    };

    const labels = keys.map((k) => labelMap[k] || k);
    const data = keys.map((k) => modMap[k]);
    const backgroundColor = keys.map((k) => colorMap[k] || "#8b5cf6");

    return {
        labels,
        datasets: [
            {
                data,
                backgroundColor,
                borderWidth: 2,
                borderColor: "#ffffff",
                hoverOffset: 6,
            },
        ],
    };
});

const chartOptionsModalidades = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                boxWidth: 12,
                padding: 12,
                font: { size: 11 },
            },
        },
        tooltip: {
            callbacks: {
                label: function (context) {
                    const label = context.label || "";
                    const value = context.parsed || 0;
                    const dataset = context.dataset;
                    const total = dataset.data.reduce((acc, curr) => acc + curr, 0);
                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                    return ` ${label}: ${value.toLocaleString()} estudios (${pct}%)`;
                },
            },
        },
    },
    cutout: "60%",
};

// Configuración de Chart.js para Cumplimiento Normativo NRD (Torta / Doughnut)
const chartDataCumplimiento = computed(() => {
    let conformes = 0;
    let alertasPreventivas = 0;
    let superaciones = 0;

    consolidado.value.forEach((c) => {
        const total = parseInt(c.total_estudios || 0, 10);
        if (c.supera_nrd) {
            superaciones += total;
        } else if (c.supera_alerta) {
            alertasPreventivas += total;
        } else {
            conformes += total;
        }
    });

    const totalGeneral = conformes + alertasPreventivas + superaciones;
    if (totalGeneral === 0) {
        return { labels: [], datasets: [] };
    }

    return {
        labels: [
            "Conforme (Rango Seguro)",
            "Alerta Preventiva (>80% NRD)",
            "Superación NRD Crítica",
        ],
        datasets: [
            {
                data: [conformes, alertasPreventivas, superaciones],
                backgroundColor: ["#10b981", "#f59e0b", "#ef4444"],
                borderWidth: 2,
                borderColor: "#ffffff",
                hoverOffset: 6,
            },
        ],
    };
});

const chartOptionsCumplimiento = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                boxWidth: 12,
                padding: 12,
                font: { size: 11 },
            },
        },
        tooltip: {
            callbacks: {
                label: function (context) {
                    const label = context.label || "";
                    const value = context.parsed || 0;
                    const dataset = context.dataset;
                    const total = dataset.data.reduce((acc, curr) => acc + curr, 0);
                    const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                    return ` ${label}: ${value.toLocaleString()} estudios (${pct}%)`;
                },
            },
        },
    },
    cutout: "60%",
};

// Carga de datos desde API
async function cargarDatos() {
    loading.value = true;
    try {
        const params = {};
        if (filtros.sede_id) params.sede_id = filtros.sede_id;
        if (filtros.modalidad && filtros.modalidad !== "TODAS")
            params.modalidad = filtros.modalidad;
        if (filtros.tipo_metrica && filtros.tipo_metrica !== "TODAS")
            params.tipo_metrica = filtros.tipo_metrica;
        if (filtros.fecha_desde) params.fecha_desde = filtros.fecha_desde;
        if (filtros.fecha_hasta) params.fecha_hasta = filtros.fecha_hasta;

        const res = await axios.get("/api/dosimetria/dashboard", { params });
        const resData = res.data?.data || res.data || {};

        consolidado.value = resData.consolidado || [];
        alertas.value = resData.alertas || [];
        ultimosRegistros.value = resData.ultimos_registros || [];
        umbralesNrd.value = resData.umbrales_nrd || [];
        sedes.value = resData.sedes || [];
        kpis.value = resData.kpis || { total_estudios: 0, total_equipos: 0, total_alertas: 0 };
    } catch (error) {
        console.error("Error al cargar telemetría de dosimetría:", error);
    } finally {
        loading.value = false;
    }
}

function limpiarFiltros() {
    filtros.sede_id = "";
    filtros.modalidad = "TODAS";
    filtros.tipo_metrica = "TODAS";
    filtros.fecha_desde = "";
    filtros.fecha_hasta = "";
    cargarDatos();
}

/**
 * Exporta el informe analítico de dosimetría y registros individuales a Excel (.xlsx).
 *
 * Flujo del proceso:
 * 1. Empaqueta los filtros activos (sede, modalidad, métrica, rango de fechas).
 * 2. Abre en pestaña nueva la ruta /api/dosis/exportar para streaming de descarga.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-26
 */
function exportarExcel() {
    const params = new URLSearchParams();
    if (filtros.sede_id) params.append("sede_id", filtros.sede_id);
    if (filtros.modalidad && filtros.modalidad !== "TODAS")
        params.append("modalidad", filtros.modalidad);
    if (filtros.tipo_metrica && filtros.tipo_metrica !== "TODAS")
        params.append("tipo_metrica", filtros.tipo_metrica);
    if (filtros.fecha_desde) params.append("fecha_desde", filtros.fecha_desde);
    if (filtros.fecha_hasta) params.append("fecha_hasta", filtros.fecha_hasta);

    window.open(`/api/dosis/exportar?${params.toString()}`, "_blank");
}

// Sincronización reactiva con EventBus global
function onSedeSeleccionada(datosSede) {
    if (!datosSede) return;
    const id = typeof datosSede === "object" ? datosSede.idSede || datosSede.id : datosSede;
    if (id && filtros.sede_id !== id) {
        filtros.sede_id = id;
        cargarDatos();
    }
}

onMounted(() => {
    // Sincronizar sede guardada en sesión si existe
    const sedeGuardada = sessionStorage.getItem("ST-sede");
    if (sedeGuardada) {
        filtros.sede_id = sedeGuardada;
    }

    if (emitter) {
        emitter.on("sedeSeleccionada", onSedeSeleccionada);
    }

    cargarDatos();
});

onBeforeUnmount(() => {
    if (emitter) {
        emitter.off("sedeSeleccionada", onSedeSeleccionada);
    }
});
</script>

<style scoped>
.dosimetria-dashboard {
    min-height: calc(100vh - 120px);
}

.kpi-card {
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
    border-radius: 8px;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.chart-card {
    border-radius: 8px;
}

.chart-wrapper {
    height: 280px;
    position: relative;
}

.filter-card {
    border-radius: 8px;
}

.clinical-table th {
    font-size: 0.82rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.clinical-table td {
    font-size: 0.88rem;
}

.heartbeat-card {
    border-radius: 8px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.status-dot.online {
    background-color: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
}

.status-dot.delayed {
    background-color: #f59e0b;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25);
}

.status-dot.offline {
    background-color: #ef4444;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25);
}

/* Modo oscuro radiológico (Slate Clínico) */
:global(body.theme-dark) .dosimetria-dashboard {
    color: #e2e8f0;
}

:global(body.theme-dark) .kpi-card,
:global(body.theme-dark) .chart-card,
:global(body.theme-dark) .filter-card,
:global(body.theme-dark) .heartbeat-card {
    background-color: #181a20 !important;
    border-color: #2d3343 !important;
}

:global(body.theme-dark) .clinical-table th {
    background-color: #14171f !important;
    color: #94a3b8 !important;
    border-color: #2d3343 !important;
}

:global(body.theme-dark) .clinical-table td {
    border-color: #2d3343 !important;
    color: #e2e8f0 !important;
}

:global(body.theme-dark) .clinical-table tbody tr:hover {
    background-color: #222632 !important;
}
</style>
