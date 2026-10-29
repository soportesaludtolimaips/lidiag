<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Gestión de Citas - Tablero</h3>
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
                    <li class="breadcrumb-item active">Tablero de gestión</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info"
                    @click="cargarDashboard()"
                    :disabled="loading"
                    title="Actualizar datos del tablero"
                >
                    <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
            </div>
        </div>

        <loading-spinner :visible="loading" />

        <div :class="{ 'd-none': loading }">
            <div class="row">
                <div class="col-md-3" v-for="stat in stats" :key="stat.label">
                    <div class="card" :class="stat.cardClass">
                        <div class="card-body">
                            <div class="d-flex no-block align-items-center">
                                <div>
                                    <h3 class="font-weight-bold mb-0">{{ stat.count }}</h3>
                                    <span>{{ stat.label }}</span>
                                </div>
                                <div class="ml-auto">
                                    <i class="fa" :class="stat.icon + ' fa-2x text-white-50'"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Citas Próximas (Hoy / Mañana)</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Paciente</th>
                                            <th>Servicio</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th>Teléfono</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="proximas.length === 0">
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i
                                                    class="fa fa-calendar-o fa-2x text-muted mb-2 d-block"
                                                ></i>
                                                No hay citas programadas para hoy o mañana
                                            </td>
                                        </tr>
                                        <tr v-for="item in proximas" :key="item.id">
                                            <td>
                                                {{ item.paciente?.nombres }}
                                                {{ item.paciente?.pri_ape }}
                                            </td>
                                            <td>{{ item.servicio?.nombre }}</td>
                                            <td>{{ formatFecha(item.fecha_hora_inicio) }}</td>
                                            <td>
                                                <span
                                                    :class="getEstadoClase(item.estado)"
                                                    class="badge badge-pill font-weight-bold"
                                                >
                                                    {{ getEstadoLabel(item.estado) }}
                                                </span>
                                            </td>
                                            <td>{{ item.paciente?.cel }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Acciones Rápidas</h4>
                            <div class="list-group">
                                <a :href="'/citas'" class="list-group-item list-group-item-action">
                                    <i class="fa fa-list"></i> Ir al listado de citas
                                </a>
                                <a
                                    href="#"
                                    class="list-group-item list-group-item-action"
                                    @click.prevent="cargarDashboard()"
                                >
                                    <i
                                        class="fa"
                                        :class="loading ? 'fa-spinner fa-spin' : 'fa-refresh'"
                                    ></i>
                                    Refrescar datos
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Resumen</h4>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                    <span>Solicitadas</span>
                                    <span class="badge badge-warning">{{
                                        statsPorEstado.solicitada || 0
                                    }}</span>
                                </div>
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                    <span>Asignadas</span>
                                    <span class="badge badge-info">{{
                                        statsPorEstado.asignada || 0
                                    }}</span>
                                </div>
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                    <span>Confirmadas</span>
                                    <span class="badge badge-success">{{
                                        statsPorEstado.confirmada || 0
                                    }}</span>
                                </div>
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                    <span>Canceladas</span>
                                    <span class="badge badge-danger">{{
                                        statsPorEstado.cancelada || 0
                                    }}</span>
                                </div>
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                    <span>Cumplidas</span>
                                    <span class="badge badge-primary">{{
                                        statsPorEstado.cumplida || 0
                                    }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>No asistió</span>
                                    <span class="badge badge-secondary">{{
                                        statsPorEstado.no_asistio || 0
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
/**
 * Tablero de Gestión de Citas
 * Visualiza estadísticas por estado y citas próximas con sincronización reactiva multisede.
 *
 * Flujo del proceso:
 * 1. Inicializa sede actual desde sessionStorage o evento global emitter.
 * 2. Carga estadísticas consolidadas y próximas citas concurrentemente vía Promise.all.
 * 3. Actualiza reactivamente ante cambios en la sede activa.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-19
 */
import { getCurrentInstance, onBeforeUnmount, onMounted, ref } from "vue";

import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import { formatFecha, getEstadoLabel, getEstadoClase } from "@/composables/useCitasUtils.js";
import axios from "axios";

const { appContext } = getCurrentInstance() || {};
const emitter = appContext?.config?.globalProperties?.emitter;

const stats = ref([]);
const statsPorEstado = ref({});
const proximas = ref([]);
const loading = ref(false);
const sedeActual = ref(sessionStorage.getItem("ST-sede") || "");

function onSedeSeleccionada(data) {
    sedeActual.value = data?.idSede || "";
    cargarDashboard();
}

async function cargarStats() {
    try {
        const params = { per_page: 1 };
        if (sedeActual.value) {
            params.sede_id = sedeActual.value;
        }

        const resTotal = await axios.get("/api/citas", { params });
        const total = resTotal.data?.total ?? resTotal.data?.data?.total ?? 0;
        stats.value = [
            {
                label: "Total Citas",
                count: total,
                icon: "fa-calendar",
                cardClass: "bg-info text-white",
            },
        ];

        const estados = [
            "solicitada",
            "asignada",
            "confirmada",
            "cancelada",
            "cumplida",
            "no_asistio",
        ];

        const promises = estados.map((estado) => {
            const p = { ...params, estado };
            return axios
                .get("/api/citas", { params: p })
                .then((res) => ({
                    estado,
                    count: res.data?.total ?? res.data?.data?.total ?? 0,
                }))
                .catch(() => ({ estado, count: 0 }));
        });

        const results = await Promise.all(promises);
        const newStats = {};
        results.forEach((r) => {
            newStats[r.estado] = r.count;
        });
        statsPorEstado.value = newStats;
    } catch {
        // Fallback defensivo ante interrupciones de red
    }
}

async function cargarProximas() {
    try {
        const hoy = new Date();
        const manana = new Date(hoy);
        manana.setDate(manana.getDate() + 1);

        const fmt = (d) =>
            `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;

        const params = {
            fecha_inicio: fmt(hoy),
            fecha_fin: fmt(manana),
            per_page: 20,
        };
        if (sedeActual.value) {
            params.sede_id = sedeActual.value;
        }

        const res = await axios.get("/api/citas", { params });
        proximas.value = res.data?.data || res.data || [];
    } catch {
        proximas.value = [];
    }
}

async function cargarDashboard() {
    if (loading.value) return;
    loading.value = true;
    try {
        await Promise.all([cargarStats(), cargarProximas()]);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    if (emitter) {
        emitter.on("sedeSeleccionada", onSedeSeleccionada);
    }
    cargarDashboard();
});

onBeforeUnmount(() => {
    if (emitter) {
        emitter.off("sedeSeleccionada", onSedeSeleccionada);
    }
});
</script>
