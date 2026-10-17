<template>
    <div>
        <!-- Bread crumb -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Reportes</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Reportes</a>
                    </li>
                    <li class="breadcrumb-item active">Producción</li>
                </ol>
            </div>
        </div>

        <!-- Page Content -->
        <div class="row">
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Producción
                        </h4>

                        <div class="row">
                            <div class="col-12">
                                <a
                                    href="javascript:void(0)"
                                    @click="
                                        tipoReporte = 'Rx';
                                        modalProduccionVisible = true;
                                    "
                                >
                                    <i class="fa fa-check text-info"></i>
                                    Producción detallada (RX, Tomografía, Mamografía, Resonancia)
                                </a>
                            </div>
                            <div class="col-12" v-if="can('ecografias.reporte')">
                                <a
                                    href="javascript:void(0)"
                                    @click="
                                        tipoReporte = 'Ecografia';
                                        modalProduccionVisible = true;
                                    "
                                >
                                    <i class="fa fa-check text-info"></i>
                                    Producción detallada (Ecografias)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Estudios tomados en PAC
                        </h4>

                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="modalPACVisible = true">
                                    <i class="fa fa-check text-info"></i>
                                    Descargar reporte de estudios tomados
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Producción Detallada -->
        <div
            class="modal fade"
            :class="{ show: modalProduccionVisible }"
            :style="{ display: modalProduccionVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="modalProduccionVisible = false"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro para el reporte</h4>
                        <button
                            type="button"
                            class="close"
                            @click="modalProduccionVisible = false"
                            aria-hidden="true"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosReporte.sedeId"
                                    id="prod-sede"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosReporte.quien_asigno"
                                    id="prod-quien-asigno"
                                >
                                    <option value="0">...::: Todos quienes agendaron :::...</option>
                                    <option
                                        v-for="usuario in quienes_asignaron"
                                        :key="usuario.id"
                                        :value="usuario.id"
                                    >
                                        {{ usuario.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosReporte.medico"
                                    id="prod-medico"
                                >
                                    <option value="" selected>
                                        ...::: Todos los medicos :::...
                                    </option>
                                    <option
                                        v-for="usuario in medicos"
                                        :key="usuario.id"
                                        :value="usuario.id"
                                    >
                                        {{ usuario.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Desde</h5>
                                            <input
                                                class="form-control input-daterange-datepicker"
                                                type="date"
                                                id="prod-fechaIni"
                                                v-model="datosReporte.fechaIni"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Hasta</h5>
                                            <input
                                                class="form-control input-daterange-datepicker"
                                                type="date"
                                                id="prod-fechaFin"
                                                v-model="datosReporte.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormulario();
                                modalProduccionVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporte()"
                            :disabled="loadingDescargarProduccion"
                        >
                            <i v-if="loadingDescargarProduccion" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reportes
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalProduccionVisible" class="modal-backdrop fade show"></div>

        <!-- Modal PAC -->
        <div
            class="modal fade"
            :class="{ show: modalPACVisible }"
            :style="{ display: modalPACVisible ? 'block' : 'none' }"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            @click.self="modalPACVisible = false"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro para el reporte</h4>
                        <button
                            type="button"
                            class="close"
                            @click="modalPACVisible = false"
                            aria-hidden="true"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label class="control-label">Sede</label>
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosPAC.sedeId"
                                    id="pac-sede"
                                >
                                    <option value="0">...::: Seleccione una sede :::...</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Desde</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="pac-fechaIni"
                                                v-model="datosPAC.fechaIni"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <div class="example">
                                            <h5 class="box-title m-t-30">Hasta</h5>
                                            <input
                                                class="form-control"
                                                type="date"
                                                id="pac-fechaFin"
                                                v-model="datosPAC.fechaFin"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-default waves-effect"
                            @click="
                                resetearFormularioPAC();
                                modalPACVisible = false;
                            "
                        >
                            Cerrar
                        </button>
                        <button
                            type="button"
                            class="btn btn-info waves-effect waves-light"
                            @click="descargarReporteEstudiosPAC()"
                            :disabled="loadingDescargarPAC"
                        >
                            <i v-if="loadingDescargarPAC" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-download"></i>
                            Descargar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="modalPACVisible" class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";

import { listarUsuariosYSedes } from "../../../helpers/usuarios";
import { toastMessage } from "@/helpers/alerts.js";
import usePermissions from "@/helpers/permissions.js";
import axios from "axios";

const MAX_DATE_RANGE_DAYS = 365;

const props = defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});

const { can } = usePermissions(props.permisos);

const sedes = ref([]);
const datosReporte = ref({ sedeId: 0, quien_asigno: "", medico: "", fechaIni: "", fechaFin: "" });
const datosPAC = ref({ sedeId: 0, fechaIni: "", fechaFin: "" });
const quienes_asignaron = ref([]);
const medicos = ref([]);
const tipoReporte = ref("Rx");
const loadingDescargarProduccion = ref(false);
const loadingDescargarPAC = ref(false);
const modalProduccionVisible = ref(false);
const modalPACVisible = ref(false);

function resetearFormulario() {
    datosReporte.value = { sedeId: 0, quien_asigno: "", medico: "", fechaIni: "", fechaFin: "" };
}

function resetearFormularioPAC() {
    datosPAC.value = { sedeId: 0, fechaIni: "", fechaFin: "" };
}

function validarFechas(fechaIni, fechaFin) {
    if (!fechaIni || !fechaFin) {
        toastMessage({
            heading: "Atención",
            text: "Debes seleccionar fecha inicio y fecha fin.",
            icon: "warning",
        });
        return false;
    }
    if (fechaIni > fechaFin) {
        toastMessage({
            heading: "Atención",
            text: "La fecha inicio no puede ser mayor a la fecha fin.",
            icon: "warning",
        });
        return false;
    }
    const ini = new Date(fechaIni);
    const fin = new Date(fechaFin);
    const diffDays = Math.ceil((fin - ini) / (1000 * 60 * 60 * 24));
    if (diffDays > MAX_DATE_RANGE_DAYS) {
        toastMessage({
            heading: "Atención",
            text: `El rango de fechas no puede superar ${MAX_DATE_RANGE_DAYS} días.`,
            icon: "warning",
        });
        return false;
    }
    return true;
}

/**
 * Carga las sedes activas y los catálogos de usuarios (quienes asignaron, médicos).
 */
async function listarSedes() {
    try {
        await listarUsuariosYSedes({
            set quienes_asignaron(v) {
                quienes_asignaron.value = v;
            },
            set medicos(v) {
                medicos.value = v;
            },
        });

        const res = await axios.get("/api/config-sedes/listar/sedes/activas");
        sedes.value = res?.data?.data || res?.data || [];
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar las sedes o usuarios.",
            icon: "error",
        });
    }
}

/**
 * Descarga el reporte de producción (RX, Ecografía) como archivo Excel.
 */
async function descargarReporte() {
    if (loadingDescargarProduccion.value) return;
    loadingDescargarProduccion.value = true;
    try {
        if (!validarFechas(datosReporte.value.fechaIni, datosReporte.value.fechaFin)) {
            return;
        }

        const params = new URLSearchParams({
            tipo: String(tipoReporte.value || "Rx"),
            sedeId: String(datosReporte.value.sedeId ?? 0),
            quien_asigno: String(datosReporte.value.quien_asigno ?? 0),
            medico: String(datosReporte.value.medico ?? 0),
            fechaIni: String(datosReporte.value.fechaIni),
            fechaFin: String(datosReporte.value.fechaFin),
        });

        const url = `/descarga-reportes-produccion?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de producción.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarProduccion.value = false;
        }, 700);
    }
}

/**
 * Descarga el reporte de estudios tomados en PAC como archivo Excel.
 */
async function descargarReporteEstudiosPAC() {
    if (loadingDescargarPAC.value) return;
    loadingDescargarPAC.value = true;
    try {
        if (!datosPAC.value.sedeId || datosPAC.value.sedeId === 0) {
            toastMessage({
                heading: "Atención",
                text: "Debes seleccionar una sede.",
                icon: "warning",
            });
            return;
        }
        if (!validarFechas(datosPAC.value.fechaIni, datosPAC.value.fechaFin)) {
            return;
        }

        const params = new URLSearchParams({
            sedeId: String(datosPAC.value.sedeId),
            fechaIni: String(datosPAC.value.fechaIni),
            fechaFin: String(datosPAC.value.fechaFin),
        });

        const url = `/descarga-reportes-estudios-pac?${params.toString()}`;
        const newWindow = window.open(url, "_blank", "noopener,noreferrer");

        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } catch (_error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible generar el reporte de estudios PAC.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarPAC.value = false;
        }, 700);
    }
}

onMounted(() => {
    listarSedes();
});
</script>

<style scoped>
.modal.show {
    background: rgba(0, 0, 0, 0.5);
}
</style>
