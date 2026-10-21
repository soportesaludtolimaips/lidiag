<template>
    <div class="container-fluid">
        <!-- Cabecera de Página -->
        <div class="row page-titles">
            <div class="col-md-7 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">
                    <i class="fa fa-database mr-2 text-primary"></i> Monitoreo de Backups y
                    Sincronización
                </h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Inicio</a></li>
                    <li class="breadcrumb-item">Seguridad</li>
                    <li class="breadcrumb-item active">Monitoreo de Backups</li>
                </ol>
            </div>
            <div class="col-md-5 col-4 align-self-center text-right">
                <button
                    type="button"
                    class="btn btn-secondary btn-action-clinical mr-2"
                    @click="consultarEstado()"
                    :disabled="cargando"
                    title="Actualizar estado (F2)"
                >
                    <i :class="['fa fa-refresh mr-1', { 'fa-spin': cargando }]"></i>
                    Refrescar
                    <span class="badge badge-light text-dark ml-1 d-none d-md-inline">F2</span>
                </button>
                <button
                    type="button"
                    class="btn btn-primary btn-action-clinical"
                    @click="confirmarEjecucionManual()"
                    :disabled="ejecutandoManual || cargando"
                    title="Generar backup de base de datos inmediatamente"
                >
                    <i :class="['fa fa-play mr-1', { 'fa-spin': ejecutandoManual }]"></i>
                    {{ ejecutandoManual ? "Generando Backup..." : "Hacer Backup BD Ahora" }}
                </button>
            </div>
        </div>

        <!-- Tarjetas KPI de Estado General -->
        <div class="row">
            <!-- 1. Estado de Almacenamiento / Disco QNAP -->
            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-primary shadow-sm h-100">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-primary"
                                ><i class="fa fa-hdd-o font-20"></i
                            ></span>
                            <span :class="['badge badge-pill', badgeClaseEspacio]">
                                {{ infoAlmacenamiento.porcentaje_usado }}% Ocupado
                            </span>
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12 font-weight-bold">
                            Espacio en Disco (Backups)
                        </h6>
                        <h4 class="font-weight-bold mb-1 text-dark">
                            {{ infoAlmacenamiento.espacio_libre_humano }} libres
                        </h4>
                        <div class="progress mt-2 mb-1" style="height: 6px">
                            <div
                                :class="['progress-bar', barraClaseEspacio]"
                                role="progressbar"
                                :style="{ width: infoAlmacenamiento.porcentaje_usado + '%' }"
                                :aria-valuenow="infoAlmacenamiento.porcentaje_usado"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Total: {{ infoAlmacenamiento.espacio_total_humano }} en unidad
                            {{ infoAlmacenamiento.unidad || "C:" }}
                        </small>
                    </div>
                </div>
            </div>

            <!-- 2. Último Backup Base de Datos MySQL -->
            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-success shadow-sm h-100">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-success"
                                ><i class="fa fa-database font-20"></i
                            ></span>
                            <span
                                :class="[
                                    'badge badge-pill',
                                    ultimoDb && ultimoDb.estado === 'al_dia'
                                        ? 'badge-success'
                                        : 'badge-warning',
                                ]"
                            >
                                {{
                                    ultimoDb && ultimoDb.estado === "al_dia"
                                        ? "Al día"
                                        : "Requiere atención"
                                }}
                            </span>
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12 font-weight-bold">
                            Backup BD Central (MySQL)
                        </h6>
                        <h5
                            class="font-weight-bold mb-1 text-dark text-truncate"
                            :title="ultimoDb?.archivo || 'Sin registros'"
                        >
                            {{ ultimoDb ? ultimoDb.tamano_humano : "N/D" }}
                        </h5>
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
                <div class="card card-outline-info shadow-sm h-100">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-info"
                                ><i class="fa fa-hospital-o font-20"></i
                            ></span>
                            <span
                                :class="[
                                    'badge badge-pill',
                                    resumenSedes.critico === 0 ? 'badge-info' : 'badge-danger',
                                ]"
                            >
                                {{ resumenSedes.salud_porcentaje }}% Salud
                            </span>
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12 font-weight-bold">
                            Sedes PACS Conectadas
                        </h6>
                        <h4 class="font-weight-bold mb-1 text-dark">
                            {{ resumenSedes.al_dia }} / {{ resumenSedes.total }}
                            <span class="font-14 text-muted font-weight-normal">al día</span>
                        </h4>
                        <div class="d-flex mt-2 font-12">
                            <span class="text-success mr-2 font-weight-bold"
                                ><i class="fa fa-check-circle"></i>
                                {{ resumenSedes.al_dia }} Ok</span
                            >
                            <span class="text-warning mr-2 font-weight-bold"
                                ><i class="fa fa-exclamation-triangle"></i>
                                {{ resumenSedes.advertencia }} Adv</span
                            >
                            <span class="text-danger font-weight-bold"
                                ><i class="fa fa-times-circle"></i>
                                {{ resumenSedes.critico }} Crítico</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Respaldo de Archivos de Storage -->
            <div class="col-lg-3 col-md-6">
                <div class="card card-outline-warning shadow-sm h-100">
                    <div class="card-body">
                        <div
                            class="d-flex flex-row align-items-center justify-content-between mb-2"
                        >
                            <span class="round round-warning"
                                ><i class="fa fa-folder-open-o font-20"></i
                            ></span>
                            <span class="badge badge-pill badge-secondary">Storage</span>
                        </div>
                        <h6 class="text-muted mb-1 text-uppercase font-12 font-weight-bold">
                            Archivos Storage LIDiag
                        </h6>
                        <h5 class="font-weight-bold mb-1 text-dark">
                            {{ ultimoStorage ? ultimoStorage.tamano_humano : "N/D" }}
                        </h5>
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

        <!-- Tabla de Estado por Sede Hospitalaria (DCM / PACS) -->
        <div class="row">
            <div class="col-lg-8 col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title mb-0 font-weight-bold text-dark">
                                <i class="fa fa-server mr-2 text-primary"></i> Sincronización de
                                Sedes Hospitalarias (DCM Sedes)
                            </h4>
                            <span class="text-muted font-12">
                                Destino: <code>{{ infoAlmacenamiento.directorio }}\dcm_sedes</code>
                            </span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead class="thead-light">
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
                                    <tr v-if="cargando && sedes.length === 0">
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fa fa-spinner fa-spin mr-2"></i> Consultando
                                            telemetría de sedes...
                                        </td>
                                    </tr>
                                    <tr v-else-if="sedes.length === 0">
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No hay sedes registradas en el sistema.
                                        </td>
                                    </tr>
                                    <tr v-for="sede in sedes" :key="sede.sede_id">
                                        <td class="font-weight-bold">
                                            <i class="fa fa-hospital-o mr-2 text-muted"></i>
                                            {{ sede.nombre_sede }}
                                            <small class="text-muted d-block font-11"
                                                >ID: #{{ sede.sede_id }}</small
                                            >
                                        </td>
                                        <td>
                                            <span
                                                :class="[
                                                    'badge badge-pill font-11 px-2 py-1',
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
                                            <span
                                                v-if="sede.tiene_backup"
                                                class="font-12 text-dark font-weight-bold d-block"
                                            >
                                                {{ sede.fecha }}
                                            </span>
                                            <span v-else class="text-danger font-12 font-italic">
                                                Sin sincronizaciones
                                            </span>
                                            <small
                                                class="text-muted font-11 text-truncate d-block"
                                                style="max-width: 200px"
                                                :title="sede.archivo"
                                            >
                                                {{ sede.archivo || "N/A" }}
                                            </small>
                                        </td>
                                        <td class="font-12">
                                            {{ sede.hace }}
                                        </td>
                                        <td class="font-12 font-weight-bold text-dark">
                                            {{ sede.tamano_humano }}
                                        </td>
                                        <td class="text-center font-12">
                                            <span class="badge badge-secondary badge-pill">
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
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title mb-0 font-weight-bold text-dark">
                                <i class="fa fa-list-alt mr-2 text-info"></i> Bitácora de Eventos
                            </h4>
                            <span class="badge badge-light text-muted">Últimos eventos</span>
                        </div>
                        <p class="text-muted font-12 mb-3">
                            Registro en tiempo real de operaciones de respaldo central y recepción
                            remota de sedes hospitalarias.
                        </p>

                        <div
                            class="log-container flex-grow-1 p-2 rounded bg-dark text-white font-11"
                            style="max-height: 480px; overflow-y: auto; font-family: monospace"
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
    if (estado === "critico") return "badge-danger";
    if (estado === "advertencia") return "badge-warning";
    return "badge-success";
});

const barraClaseEspacio = computed(() => {
    const estado = infoAlmacenamiento.value.estado_espacio;
    if (estado === "critico") return "bg-danger";
    if (estado === "advertencia") return "bg-warning";
    return "bg-success";
});

function obtenerClaseBadgeSemaforo(semaforo) {
    if (semaforo === "verde") return "badge-success";
    if (semaforo === "amarillo") return "badge-warning";
    return "badge-danger";
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
        confirmButtonText: "Sí, generar ahora",
        cancelButtonText: "Cancelar",
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

<style scoped>
.btn-action-clinical {
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}
.round {
    line-height: 48px;
    color: #fff;
    width: 48px;
    height: 48px;
    display: inline-block;
    font-weight: 400;
    text-align: center;
    border-radius: 100%;
}
.round.round-primary {
    background: #745af2;
}
.round.round-success {
    background: #06d79c;
}
.round.round-info {
    background: #398bf7;
}
.round.round-warning {
    background: #ffb22b;
}
.log-container::-webkit-scrollbar {
    width: 6px;
}
.log-container::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}
</style>
