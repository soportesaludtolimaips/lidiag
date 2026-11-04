<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-6 align-self-center">
                <h3 class="text-themecolor">Reporte de Estudios PACS</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Reportes</a>
                    </li>
                    <li class="breadcrumb-item active">Estudios PACS</li>
                </ol>
            </div>
            <div class="col-md-6 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info btn-sm"
                    :disabled="loadingDescargar"
                    @click="descargarReporte()"
                >
                    <i
                        class="fa mr-1"
                        :class="loadingDescargar ? 'fa-spinner fa-spin' : 'fa-file-excel-o'"
                    ></i>
                    Descargar Excel
                </button>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <div class="row">
            <!-- Formulario de Consulta -->
            <div class="col-lg-7 col-md-12">
                <div class="card card-outline-info">
                    <div class="card-header bg-info">
                        <h4 class="m-b-0 text-white">
                            <i class="fa fa-filter mr-2"></i> Filtros de Descarga
                        </h4>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="descargarReporte()">
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label
                                                class="control-label font-weight-bold"
                                                for="sedeId"
                                            >
                                                Sede Hospitalaria
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select
                                                id="sedeId"
                                                class="form-control custom-select"
                                                v-model="datosReporte.sedeId"
                                                :disabled="loadingSedes"
                                            >
                                                <option :value="0">
                                                    ...::: Seleccione una sede :::...
                                                </option>
                                                <option
                                                    v-for="itemSede in sedes"
                                                    :key="itemSede.id"
                                                    :value="itemSede.id"
                                                >
                                                    {{ itemSede.nom_sede }}
                                                </option>
                                            </select>
                                            <small class="form-text text-muted" v-if="loadingSedes">
                                                <i class="fa fa-spinner fa-spin"></i> Cargando sedes
                                                activas...
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label
                                                class="control-label font-weight-bold"
                                                for="fechaIni"
                                            >
                                                Fecha Inicial (Desde)
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                id="fechaIni"
                                                name="fechaIni"
                                                type="date"
                                                class="form-control"
                                                v-model="datosReporte.fechaIni"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label
                                                class="control-label font-weight-bold"
                                                for="fechaFin"
                                            >
                                                Fecha Final (Hasta)
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                id="fechaFin"
                                                name="fechaFin"
                                                type="date"
                                                class="form-control"
                                                v-model="datosReporte.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr />

                            <div class="form-actions text-right">
                                <button
                                    type="button"
                                    class="btn btn-inverse btn-sm mr-2"
                                    @click="limpiarFiltros()"
                                    :disabled="loadingDescargar"
                                >
                                    <i class="fa fa-refresh mr-1"></i> Limpiar
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-success btn-sm"
                                    :disabled="loadingDescargar || loadingSedes"
                                >
                                    <i
                                        class="fa mr-1"
                                        :class="
                                            loadingDescargar ? 'fa-spinner fa-spin' : 'fa-download'
                                        "
                                    ></i>
                                    Generar Reporte Excel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Información del Reporte -->
            <div class="col-lg-5 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-info-circle mr-2"></i> Información del Reporte
                        </h4>
                        <p class="text-muted font-14">
                            Este reporte realiza una consulta directa a la base de datos del
                            servidor PACS local de la sede seleccionada, extrayendo los estudios
                            DICOM registrados durante el rango de fechas indicado.
                        </p>

                        <div class="m-t-20">
                            <h6 class="font-weight-bold">
                                <i class="fa fa-database text-info mr-2"></i> Campos del Archivo
                                Excel:
                            </h6>
                            <ul class="list-icons m-t-10">
                                <li><i class="fa fa-check text-success"></i> Sede hospitalaria</li>
                                <li>
                                    <i class="fa fa-check text-success"></i> Fecha y hora del
                                    estudio en PACS
                                </li>
                                <li>
                                    <i class="fa fa-check text-success"></i> Número de documento del
                                    paciente
                                </li>
                                <li>
                                    <i class="fa fa-check text-success"></i> Nombre completo del
                                    paciente
                                </li>
                                <li>
                                    <i class="fa fa-check text-success"></i> Descripción del estudio
                                    (Study Description)
                                </li>
                                <li>
                                    <i class="fa fa-check text-success"></i> Modalidades adquiridas
                                    (CR, CT, MR, MG, US, DX)
                                </li>
                            </ul>
                        </div>

                        <div class="alert alert-info m-t-20">
                            <i class="fa fa-lightbulb-o mr-1"></i>
                            <strong>Nota:</strong> Los estudios mostrados corresponden
                            exclusivamente a las adquisiciones registradas en el PACS Dcm4chee de la
                            sede.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { toastMessage, getUserSafeError } from "@/helpers/alerts.js";
import axios from "axios";

defineProps({
    usuarioactual: {
        type: Object,
        default: () => ({}),
    },
    permisos: {
        type: Array,
        default: () => [],
    },
});

const sedes = ref([]);
const loadingSedes = ref(false);
const loadingDescargar = ref(false);

const datosReporte = ref({
    sedeId: 0,
    fechaIni: "",
    fechaFin: "",
});

/**
 * Carga las sedes activas para el selector de sedes.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-04
 */
async function listarSedes() {
    loadingSedes.value = true;
    try {
        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = Array.isArray(res?.data?.data) ? res.data.data : [];

        // Si solo hay una sede o hay una sede activa en sesión, preseleccionarla
        const sedeGuardada = sessionStorage.getItem("ST-sede");
        if (sedeGuardada && sedes.value.some((s) => String(s.id) === String(sedeGuardada))) {
            datosReporte.value.sedeId = Number(sedeGuardada);
        } else if (sedes.value.length === 1) {
            datosReporte.value.sedeId = sedes.value[0].id;
        }
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: getUserSafeError(error, "No fue posible cargar las sedes activas."),
            icon: "error",
        });
    } finally {
        loadingSedes.value = false;
    }
}

/**
 * Limpia los filtros del formulario.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-04
 */
function limpiarFiltros() {
    const sedeGuardada = sessionStorage.getItem("ST-sede");
    datosReporte.value.sedeId = sedeGuardada ? Number(sedeGuardada) : 0;
    datosReporte.value.fechaIni = "";
    datosReporte.value.fechaFin = "";
}

/**
 * Valida los parámetros y descarga el archivo Excel del reporte de estudios PACS.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-04
 */
function descargarReporte() {
    if (loadingDescargar.value) return;

    if (!datosReporte.value.sedeId || Number(datosReporte.value.sedeId) === 0) {
        toastMessage({
            heading: "Atención",
            text: "Debes seleccionar una sede hospitalaria.",
            icon: "warning",
        });
        return;
    }

    if (!datosReporte.value.fechaIni || !datosReporte.value.fechaFin) {
        toastMessage({
            heading: "Atención",
            text: "Debes seleccionar la fecha inicial y la fecha final.",
            icon: "warning",
        });
        return;
    }

    if (datosReporte.value.fechaIni > datosReporte.value.fechaFin) {
        toastMessage({
            heading: "Atención",
            text: "La fecha inicial no puede ser mayor a la fecha final.",
            icon: "warning",
        });
        return;
    }

    loadingDescargar.value = true;

    try {
        const params = new URLSearchParams({
            sedeId: String(datosReporte.value.sedeId),
            fechaIni: String(datosReporte.value.fechaIni),
            fechaFin: String(datosReporte.value.fechaFin),
        });

        window.open(
            `/descarga-reportes-estudios-pac?${params.toString()}`,
            "_blank",
            "noopener,noreferrer"
        );

        toastMessage({
            heading: "Generando Reporte",
            text: "Se ha iniciado la descarga del reporte de estudios PACS.",
            icon: "info",
        });
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: getUserSafeError(error, "Ocurrió un error al solicitar la descarga."),
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargar.value = false;
        }, 1200);
    }
}

onMounted(() => {
    listarSedes();
});
</script>
