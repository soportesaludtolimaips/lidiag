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
        <!-- Tarjetas KPI de Estado General (Nativo AdminWrap) -->
        <!-- ============================================================== -->
        <div class="row">
            <!-- 1. Estado de Almacenamiento / Disco QNAP -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i :class="['fa fa-hdd-o', colorIconoEspacio]"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="cargando">...</span>
                                    <span v-else>{{
                                        infoAlmacenamiento.espacio_libre_humano
                                    }}</span>
                                </h2>
                                <h6 class="text-muted m-b-0">
                                    Espacio libre ({{ infoAlmacenamiento.porcentaje_usado }}% usado)
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Último Backup Base de Datos MySQL -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i
                                    :class="[
                                        'fa fa-database',
                                        ultimoDb && ultimoDb.estado === 'al_dia'
                                            ? 'text-success'
                                            : 'text-warning',
                                    ]"
                                ></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="cargando">...</span>
                                    <span v-else>{{
                                        ultimoDb ? ultimoDb.tamano_humano : "N/D"
                                    }}</span>
                                </h2>
                                <h6 class="text-muted m-b-0">Backup BD Central (MySQL)</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Salud de Sedes Hospitalarias (DCM / PACS) -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i
                                    :class="[
                                        'fa fa-hospital-o',
                                        resumenSedes.critico === 0 ? 'text-info' : 'text-danger',
                                    ]"
                                ></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="cargando">...</span>
                                    <span v-else
                                        >{{ resumenSedes.al_dia }} / {{ resumenSedes.total }}</span
                                    >
                                </h2>
                                <h6 class="text-muted m-b-0">
                                    Sedes al día ({{ resumenSedes.salud_porcentaje }}% salud)
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Respaldo de Archivos de Storage -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-primary fa fa-folder-open-o"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">
                                    <span v-if="cargando">...</span>
                                    <span v-else>{{
                                        ultimoStorage ? ultimoStorage.tamano_humano : "N/D"
                                    }}</span>
                                </h2>
                                <h6 class="text-muted m-b-0">Archivos Storage LIDiag</h6>
                            </div>
                        </div>
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
                                        <th class="text-center">Transferencia</th>
                                        <th>Disco Imágenes (PACS)</th>
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
                                        <td class="text-center">
                                            <template
                                                v-if="
                                                    sede.transferencia &&
                                                    sede.transferencia.estado === 'SUCCESS'
                                                "
                                            >
                                                <span
                                                    class="label label-success"
                                                    :title="
                                                        sede.transferencia.destino
                                                            ? 'Transferido a ' +
                                                              sede.transferencia.destino
                                                            : 'Transferido exitosamente'
                                                    "
                                                >
                                                    <i class="fa fa-check"></i> Transferido
                                                </span>
                                                <small
                                                    v-if="sede.transferencia.destino_tipo"
                                                    class="text-muted d-block font-10 m-t-5"
                                                    :title="sede.transferencia.destino || ''"
                                                >
                                                    <i class="fa fa-hdd-o"></i>
                                                    {{ sede.transferencia.destino_tipo }}
                                                    <span
                                                        v-if="sede.transferencia.tasa_mbs"
                                                        class="text-success font-weight-bold"
                                                    >
                                                        ({{ sede.transferencia.tasa_mbs }} MB/s)
                                                    </span>
                                                </small>
                                            </template>
                                            <template
                                                v-else-if="
                                                    sede.transferencia &&
                                                    sede.transferencia.estado === 'ERROR'
                                                "
                                            >
                                                <span
                                                    class="label label-danger"
                                                    :title="
                                                        sede.transferencia.error ||
                                                        'Error en la transferencia del backup'
                                                    "
                                                >
                                                    <i class="fa fa-times"></i> Error
                                                </span>
                                                <small
                                                    v-if="sede.transferencia.error"
                                                    class="text-danger d-block font-10 text-truncate"
                                                    style="max-width: 150px"
                                                    :title="sede.transferencia.error"
                                                >
                                                    {{ sede.transferencia.error }}
                                                </small>
                                            </template>
                                            <template v-else-if="sede.tiene_backup">
                                                <span
                                                    class="label label-warning"
                                                    title="Respaldo conservado localmente en sede"
                                                >
                                                    <i class="fa fa-clock-o"></i> Local
                                                </span>
                                            </template>
                                            <template v-else>
                                                <span
                                                    class="label label-light-danger"
                                                    title="Sin backup registrado"
                                                >
                                                    <i class="fa fa-times text-danger"></i> Sin
                                                    datos
                                                </span>
                                            </template>
                                        </td>
                                        <td>
                                            <template
                                                v-if="
                                                    sede.disco_imagenes &&
                                                    sede.disco_imagenes.libre_gb !== null
                                                "
                                            >
                                                <div
                                                    class="d-flex align-items-center justify-content-between font-11"
                                                >
                                                    <strong>
                                                        <i
                                                            class="fa fa-hdd-o text-primary mr-1"
                                                        ></i>
                                                        {{ sede.disco_imagenes.unidad || "D:" }}
                                                        <span class="text-muted font-10">
                                                            ({{ sede.disco_imagenes.libre_gb }} GB
                                                            libres)
                                                        </span>
                                                    </strong>
                                                    <span
                                                        :class="[
                                                            'label font-10',
                                                            obtenerClaseEstadoDisco(
                                                                sede.disco_imagenes.estado
                                                            ),
                                                        ]"
                                                    >
                                                        {{
                                                            sede.disco_imagenes.porcentaje_usado !==
                                                                null &&
                                                            sede.disco_imagenes.porcentaje_usado !==
                                                                undefined
                                                                ? sede.disco_imagenes
                                                                      .porcentaje_usado + "%"
                                                                : sede.disco_imagenes.estado
                                                        }}
                                                    </span>
                                                </div>
                                                <div
                                                    v-if="
                                                        sede.disco_imagenes.porcentaje_usado !==
                                                            null &&
                                                        sede.disco_imagenes.porcentaje_usado !==
                                                            undefined
                                                    "
                                                    class="progress m-t-5 m-b-2"
                                                    style="height: 6px"
                                                    :title="sede.disco_imagenes.texto_tooltip"
                                                >
                                                    <div
                                                        :class="[
                                                            'progress-bar',
                                                            obtenerClaseBarraDisco(
                                                                sede.disco_imagenes.estado
                                                            ),
                                                        ]"
                                                        role="progressbar"
                                                        :style="{
                                                            width:
                                                                sede.disco_imagenes
                                                                    .porcentaje_usado + '%',
                                                        }"
                                                        :aria-valuenow="
                                                            sede.disco_imagenes.porcentaje_usado
                                                        "
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                    ></div>
                                                </div>
                                                <small
                                                    v-if="sede.disco_imagenes.ruta"
                                                    class="text-muted d-block font-10 text-truncate"
                                                    style="max-width: 170px"
                                                    :title="sede.disco_imagenes.ruta"
                                                >
                                                    {{ sede.disco_imagenes.ruta }}
                                                </small>
                                                <small
                                                    v-if="sede.disco_imagenes.hace"
                                                    class="text-info d-block font-10"
                                                >
                                                    <i class="fa fa-clock-o"></i>
                                                    {{ sede.disco_imagenes.hace }}
                                                </small>
                                            </template>
                                            <template v-else>
                                                <span class="text-muted font-11">
                                                    <i class="fa fa-hdd-o text-muted mr-1"></i> Sin
                                                    datos Dd
                                                </span>
                                            </template>
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
                                                <small
                                                    v-if="sede.qnap_destino"
                                                    class="text-info d-block text-truncate font-10"
                                                    style="max-width: 220px"
                                                    :title="sede.qnap_destino"
                                                >
                                                    <i class="fa fa-server"></i>
                                                    {{ sede.qnap_destino }}
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
                                                {{ sede.total_archivos }}
                                                {{
                                                    sede.origen === "telemetria_sede"
                                                        ? "reporte"
                                                        : "zips"
                                                }}
                                            </span>
                                            <span
                                                v-if="sede.sync_imagenes"
                                                class="label label-success d-block m-t-5 font-10"
                                                :title="
                                                    'Imágenes sincronizadas: ' +
                                                    (sede.sync_imagenes.copiados || 0) +
                                                    ' (' +
                                                    (sede.sync_imagenes.hace || 'Reciente') +
                                                    ')'
                                                "
                                            >
                                                <i class="fa fa-image"></i> Img:
                                                {{ sede.sync_imagenes.estado }}
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

const colorIconoEspacio = computed(() => {
    const estado = infoAlmacenamiento.value.estado_espacio;
    if (estado === "critico") return "text-danger";
    if (estado === "advertencia") return "text-warning";
    return "text-primary";
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

function obtenerClaseEstadoDisco(estado) {
    const est = String(estado || "").toUpperCase();
    if (est === "CRITICO" || est === "CRÍTICO") return "label-danger";
    if (est === "ADVERTENCIA") return "label-warning";
    return "label-success";
}

function obtenerClaseBarraDisco(estado) {
    const est = String(estado || "").toUpperCase();
    if (est === "CRITICO" || est === "CRÍTICO") return "bg-danger";
    if (est === "ADVERTENCIA") return "bg-warning";
    return "bg-success";
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
