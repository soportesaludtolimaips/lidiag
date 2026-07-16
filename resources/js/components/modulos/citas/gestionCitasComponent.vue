<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">GestiÃ³n de Citas - Tablero</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Citas</a>
                    </li>
                    <li class="breadcrumb-item active">Tablero de gestiÃ³n</li>
                </ol>
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
                            <h4 class="card-title">Citas PrÃ³ximas (Hoy / MaÃ±ana)</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Paciente</th>
                                            <th>Servicio</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th>TelÃ©fono</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="proximas.length === 0">
                                            <td colspan="5" class="text-center text-muted">
                                                No hay citas prÃ³ximas
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
                            <h4 class="card-title">Acciones RÃ¡pidas</h4>
                            <div class="list-group">
                                <a :href="'/citas'" class="list-group-item list-group-item-action">
                                    <i class="fa fa-list"></i> Ir al listado de citas
                                </a>
                                <a
                                    href="javascript:void(0)"
                                    class="list-group-item list-group-item-action"
                                    @click="cargarProximas()"
                                >
                                    <i class="fa fa-refresh"></i> Refrescar datos
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
                                    <span>No asistiÃ³</span>
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
import { onMounted, ref, getCurrentInstance } from "vue";

const { proxy } = getCurrentInstance();
import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import { formatFecha, getEstadoLabel, getEstadoClase } from "@/composables/useCitasUtils.js";
import axios from "axios";

const stats = ref([]);
const statsPorEstado = ref({});
const proximas = ref([]);
const loading = ref(false);

function cargarDashboard() {
    cargarStats();
    cargarProximas();
}
function cargarStats() {
    var thisVue = proxy;

    axios.get("/api/citas?per_page=1").then(function (response) {
        var total = response.data.total || 0;
        thisVue.stats = [
            {
                label: "Total Citas",
                count: total,
                icon: "fa-calendar",
                cardClass: "bg-info text-white",
            },
        ];
    });

    var estados = ["solicitada", "asignada", "confirmada", "cancelada", "cumplida", "no_asistio"];
    thisVue.statsPorEstado = {};

    estados.forEach(function (estado) {
        axios.get("/api/citas?estado=" + estado + "&per_page=1").then(function (response) {
            thisVue.statsPorEstado[estado] = response.data.total || 0;
        });
    });
}
function cargarProximas() {
    var thisVue = proxy;
    thisVue.loading = true;

    var hoy = new Date();
    var manana = new Date(hoy);
    manana.setDate(manana.getDate() + 1);

    var fmt = function (d) {
        return (
            d.getFullYear() +
            "-" +
            String(d.getMonth() + 1).padStart(2, "0") +
            "-" +
            String(d.getDate()).padStart(2, "0")
        );
    };

    axios
        .get("/api/citas", {
            params: {
                fecha_inicio: fmt(hoy),
                fecha_fin: fmt(manana),
                per_page: 20,
            },
        })
        .then(function (response) {
            thisVue.proximas = response.data.data || [];
        })
        .catch(function () {
            thisVue.proximas = [];
        })
        .then(function () {
            thisVue.loading = false;
        });
}

onMounted(() => {
    cargarDashboard();
});
</script>
