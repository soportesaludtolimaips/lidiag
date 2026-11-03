<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Seguridad</h3>
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
                            Seguridad
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Monitoreo de Backups</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary waves-effect waves-light m-r-5"
                    @click="consultarEstado"
                    :disabled="cargando"
                    title="Actualizar estado"
                >
                    <i class="fa" :class="cargando ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-info waves-effect waves-light"
                    @click="confirmarEjecucionManual"
                    :disabled="ejecutandoManual || cargando"
                    title="Generar backup de base de datos inmediatamente"
                >
                    <i class="fa" :class="ejecutandoManual ? 'fa-spinner fa-spin' : 'fa-play'"></i>
                    {{ ejecutandoManual ? "Generando Backup..." : "Hacer Backup BD Ahora" }}
                </button>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Tarjetas KPI de Estado General -->
        <!-- ============================================================== -->
        <div class="row">
            <!-- 1. Estado de Almacenamiento / Disco QNAP -->
            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-primary">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-primary">
                                <i class="fa fa-hdd-o font-20"></i>
                            </span>
                            <span :class="['label', badgeClaseEspacio]">
                                {{ infoAlmacenamiento.porcentaje_usado }}% Ocupado
                            </span>
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12">
                            Espacio en Disco (Backups)
                        </h6>
                        <h4 class="text-dark mb-1">
                            {{ infoAlmacenamiento.espacio_libre_humano }} libres
                        </h4>
                        <div class="progress m-t-10 m-b-5" style="height: 6px">
                            <div
                                :class="['progress-bar', barraClaseEspacio]"
                                role="progressbar"
                                :style="{ width: infoAlmacenamiento.porcentaje_usado + '%' }"
                                :aria-valuenow="infoAlmacenamiento.porcentaje_usado"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>
                        <small class="text-muted d-block m-t-5">
                            Total: {{ infoAlmacenamiento.espacio_total_humano }} en unidad
                            {{ infoAlmacenamiento.unidad || "C:" }}
                        </small>
                    </div>
                </div>
            </div>

            <!-- 2. Último Backup Base de Datos MySQL -->
            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-success">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-success">
                                <i class="fa fa-database font-20"></i>
                            </span>
                            <span
                                :class="[
                                    'label',
                                    ultimoDb && ultimoDb.estado === 'al_dia'
                                        ? 'label-success'
                                        : 'label-warning',
                                ]"
                            >
                                {{
                                    ultimoDb && ultimoDb.estado === "al_dia"
                                        ? "Al día"
                                        : "Requiere atención"
                                }}
                            </span>
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12">
                            Backup BD Central (MySQL)
                        </h6>
                        <h4
                            class="text-dark mb-1 text-truncate"
                            :title="ultimoDb?.archivo || 'Sin registros'"
                        >
                            {{ ultimoDb ? ultimoDb.tamano_humano : "N/D" }}
                        </h4>
                        <p class="text-muted mb-0 font-12">
                            <i class="fa fa-clock-o mr-1"></i>
                            {{ ultimoDb ? ultimoDb.hace : "Sin registros recientes" }}
                        </p>
                        <small
                            class="text-muted d-block text-truncate"
                            :title="ultimoDb?.fecha || ''"
                        >
                            {{ ultimoDb?.fecha || "Nunca generado" }}
                        </small>
                    </div>
                </div>
            </div>

            <!-- 3. Salud de Sedes Hospitalarias (DCM / PACS) -->
            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-info">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-info">
                                <i class="fa fa-hospital-o font-20"></i>
                            </span>
                            <span
                                :class="[
                                    'label',
                                    resumenSedes.critico === 0 ? 'label-info' : 'label-danger',
                                ]"
                            >
                                {{ resumenSedes.salud_porcentaje }}% Salud
                            </span>
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12">
                            Sedes PACS Conectadas
                        </h6>
                        <h4 class="text-dark mb-1">
                            {{ resumenSedes.al_dia }} / {{ resumenSedes.total }}
                            <small class="text-muted">al día</small>
                        </h4>
                        <div class="d-flex m-t-10 font-12">
                            <span class="text-success mr-2 font-weight-bold">
                                <i class="fa fa-check-circle"></i> {{ resumenSedes.al_dia }} Ok
                            </span>
                            <span class="text-warning mr-2 font-weight-bold">
                                <i class="fa fa-exclamation-triangle"></i>
                                {{ resumenSedes.advertencia }} Adv
                            </span>
                            <span class="text-danger font-weight-bold">
                                <i class="fa fa-times-circle"></i>
                                {{ resumenSedes.critico }} Crítico
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Respaldo de Archivos de Storage -->
            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-warning">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-warning">
                                <i class="fa fa-folder-open-o font-20"></i>
                            </span>
                            <span class="label label-light-inverse">Storage</span>
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12">
                            Archivos Storage LIDiag
                        </h6>
                        <h4 class="text-dark mb-1">
                            {{ ultimoStorage ? ultimoStorage.tamano_humano : "N/D" }}
                        </h4>
                        <p class="text-muted mb-0 font-12">
                            <i class="fa fa-clock-o mr-1"></i>
                            {{ ultimoStorage ? ultimoStorage.hace : "Sin registros" }}
                        </p>
                        <small
                            class="text-muted d-block text-truncate"
                            :title="ultimoStorage?.fecha || ''"
                        >
                            {{ ultimoStorage?.fecha || "Sin respaldo reciente" }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Tabla de Estado por Sede y Bitácora -->
        <!-- ============================================================== -->
        <div class="row">
            <div class="col-lg-8 col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            Sincronización de Sedes Hospitalarias (DCM Sedes)
                        </h4>
                        <h6 class="card-subtitle">
                            Destino en almacenamiento:
                            <code>{{ infoAlmacenamiento.directorio }}\dcm_sedes</code>
                        </h6>

                        <table-skeleton-loader
                            :visible="cargando && sedes.length === 0"
                            :rows="4"
                            :columns="6"
                        />
                        <empty-state-component
                            v-if="!cargando && sedes.length === 0"
                            icon="fa fa-server"
                            title="No hay sedes registradas"
                            description="No se encontraron sedes hospitalarias configuradas en el sistema."
                            action-text="Recargar telemetría"
                            action-icon="fa fa-refresh"
                            @action="consultarEstado"
                        />

                        <div class="table-responsive m-t-40" v-show="sedes.length > 0">
                            <table
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Sede Hospitalaria</th>
                                        <th>Estado de Salud</th>
                                        <th>Último Backup Recibido</th>
                                        <th>Antigüedad</th>
                                        <th>Tamaño</th>
                                        <th class="text-center">Histórico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="sede in sedes" :key="sede.sede_id">
                                        <td>
                                            <strong>{{ sede.nombre_sede }}</strong>
                                            <small class="text-muted d-block"
                                                >ID: #{{ sede.sede_id }}</small
                                            >
                                        </td>
                                        <td>
                                            <span
                                                :class="[
                                                    'label',
                                                    obtenerClaseBadgeSemaforo(sede.semaforo),
                                                ]"
                                            >
                                                <i
                                                    :class="[
                                                        'fa mr-1',
                                                        obtenerIconoSemaforo(sede.semaforo),
                                                    ]"
                                                ></i>
                                                {{ sede.estado_texto }}
                                            </span>
                                        </td>
                                        <td>
                                            <span v-if="sede.tiene_backup">
                                                {{ sede.fecha }}
                                                <small
                                                    class="text-muted d-block text-truncate"
                                                    style="max-width: 220px"
                                                    :title="sede.archivo"
                                                >
                                                    {{ sede.archivo || "N/A" }}
                                                </small>
                                            </span>
                                            <span v-else class="label label-danger">
                                                Sin sincronizaciones
                                            </span>
                                        </td>
                                        <td>
                                            {{ sede.hace }}
                                        </td>
                                        <td>
                                            {{ sede.tamano_humano }}
                                        </td>
                                        <td class="text-center">
                                            <span class="label label-light-inverse">
                                                {{ sede.total_archivos }} zips
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bitácora de Eventos y Actividad (backups.log) -->
            <div class="col-lg-4 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="card-title mb-0">Bitácora de Eventos</h4>
                            <span class="label label-light-inverse">Últimos eventos</span>
                        </div>
                        <h6 class="card-subtitle">
                            Registro en tiempo real de operaciones de respaldo central y recepción
                            remota.
                        </h6>

                        <div
                            class="p-3 bg-dark text-white rounded"
                            style="
                                max-height: 480px;
                                overflow-y: auto;
                                font-family: monospace;
                                font-size: 11px;
                            "
                        >
                            <div v-if="logs.length === 0" class="text-center text-muted py-4">
                                No hay registros recientes en la bitácora.
                            </div>
                            <div
                                v-for="(log, idx) in logs"
                                :key="idx"
                                :class="[
                                    'py-1 border-bottom border-secondary',
                                    obtenerClaseColorLog(log.tipo),
                                ]"
                            >
                                {{ log.texto }}
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
 * Componente: Monitoreo de Backups y Sincronización
 *
 * Flujo del proceso:
 * 1. Consulta al montar y mediante atajo F2 el estado de almacenamiento en C:/QNAP y recepción de sedes.
 * 2. Visualiza semáforo asistencial de salud de backups por sede hospitalaria.
 * 3. Permite disparar la ejecución manual e inmediata de un respaldo de base de datos MySQL.
 * 4. Muestra la bitácora de eventos recientes de storage/logs/backups.log.
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-21
 */
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import axios from "axios";
import { confirmAction, alertError, alertSuccess } from "@/helpers/alerts";

const cargando = ref(false);
const ejecutandoManual = ref(false);

const infoAlmacenamiento = ref({
    unidad: "C:",
    directorio: "C:\\Backups_Lidiag",
    espacio_total_humano: "0 GB",
    espacio_libre_humano: "0 GB",
    porcentaje_usado: 0,
    estado_espacio: "optimo",
});

const ultimoDb = ref(null);
const ultimoStorage = ref(null);
const resumenSedes = ref({
    total: 0,
    al_dia: 0,
    advertencia: 0,
    critico: 0,
    salud_porcentaje: 100,
});
const sedes = ref([]);
const logs = ref([]);

const badgeClaseEspacio = computed(() => {
    const estado = infoAlmacenamiento.value.estado_espacio;
    if (estado === "critico") return "label-danger";
    if (estado === "advertencia") return "label-warning";
    return "label-success";
});

const barraClaseEspacio = computed(() => {
    const estado = infoAlmacenamiento.value.estado_espacio;
    if (estado === "critico") return "bg-danger";
    if (estado === "advertencia") return "bg-warning";
    return "bg-success";
});

function obtenerClaseBadgeSemaforo(semaforo) {
    if (semaforo === "verde") return "label-success";
    if (semaforo === "amarillo") return "label-warning";
    return "label-danger";
}

function obtenerIconoSemaforo(semaforo) {
    if (semaforo === "verde") return "fa-check";
    if (semaforo === "amarillo") return "fa-exclamation-triangle";
    return "fa-times";
}

function obtenerClaseColorLog(tipo) {
    if (tipo === "error") return "text-danger font-weight-bold";
    if (tipo === "warning") return "text-warning";
    if (tipo === "success") return "text-success";
    return "text-light";
}

async function consultarEstado() {
    cargando.value = true;
    try {
        const res = await axios.get("/api/seguridad-backups/estado");
        if (res.data && res.data.success) {
            const data = res.data.data;
            infoAlmacenamiento.value = data.almacenamiento || infoAlmacenamiento.value;
            ultimoDb.value = data.centrales?.ultimo_db || null;
            ultimoStorage.value = data.centrales?.ultimo_storage || null;
            resumenSedes.value = data.resumen_sedes || resumenSedes.value;
            sedes.value = data.sedes || [];
            logs.value = data.logs || [];
        }
    } catch (error) {
        console.error("Error al consultar estado de backups:", error);
        alertError(
            error.response?.data?.message || "No fue posible cargar la información de backups.",
            "Error de Conexión"
        );
    } finally {
        cargando.value = false;
    }
}

async function confirmarEjecucionManual() {
    const ok = await confirmAction({
        title: "¿Generar Backup Inmediato?",
        text: "Se iniciará la exportación completa de la base de datos MySQL central y su empaquetado en C:\\Backups_Lidiag.",
        icon: "warning",
    });

    if (!ok) return;

    ejecutandoManual.value = true;
    try {
        const res = await axios.post("/api/seguridad-backups/ejecutar-db");
        if (res.data && res.data.success) {
            alertSuccess(
                res.data.message || "El backup de base de datos se ha completado correctamente.",
                "Backup Generado"
            );
            await consultarEstado();
        } else {
            alertError(res.data?.message || "Error desconocido.", "Fallo al Generar Backup");
        }
    } catch (error) {
        console.error("Error al generar backup:", error);
        alertError(
            error.response?.data?.message || "Ocurrió un fallo durante la ejecución del backup.",
            "Error"
        );
    } finally {
        ejecutandoManual.value = false;
    }
}

function onKeyDown(e) {
    if (e.key === "F2") {
        e.preventDefault();
        consultarEstado();
    }
}

onMounted(() => {
    consultarEstado();
    window.addEventListener("keydown", onKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener("keydown", onKeyDown);
});
</script>
