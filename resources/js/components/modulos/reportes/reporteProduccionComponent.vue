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
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary btn-action-clinical m-r-5"
                    :disabled="loadingSedes"
                    title="Actualizar sedes y catálogos asistenciales (F2)"
                    @click="listarSedes()"
                >
                    <i class="fa fa-refresh mr-1" :class="{ 'fa-spin': loadingSedes }"></i>
                    Refrescar <span class="badge badge-light border ml-1">F2</span>
                </button>
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
                                <a href="javascript:void(0)" @click="abrirModalProduccion('Rx')">
                                    <i class="fa fa-check text-info"></i>
                                    Producción detallada (RX, Tomografía, Mamografía, Resonancia)
                                </a>
                            </div>
                            <div class="col-12" v-if="can('ecografias.reporte')">
                                <a
                                    href="javascript:void(0)"
                                    @click="abrirModalProduccion('Ecografia')"
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
                                <a href="javascript:void(0)" @click="abrirModalPAC()">
                                    <i class="fa fa-check text-info"></i>
                                    Descargar reporte de estudios tomados
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
                            <i class="fa fa-radiation text-warning"></i> Dosimetría y Radiación
                        </h4>

                        <div class="row">
                            <div class="col-12">
                                <a href="javascript:void(0)" @click="abrirModalDosimetria()">
                                    <i class="fa fa-check text-info"></i>
                                    Reporte de Dosimetría y Niveles NRD (Res. 482)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Producción Detallada -->
        <div
            v-if="modalProduccionVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
            @click.self="
                resetearFormulario();
                modalProduccionVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro para el reporte ({{ tipoReporte }})</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormulario();
                                modalProduccionVisible = false;
                            "
                        >
                            &times;
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
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormulario();
                                modalProduccionVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporte()"
                            :disabled="loadingDescargarProduccion"
                        >
                            <i
                                v-if="loadingDescargarProduccion"
                                class="fa fa-spinner fa-spin mr-1"
                            ></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reportes
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal PAC -->
        <div
            v-if="modalPACVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
            @click.self="
                resetearFormularioPAC();
                modalPACVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtro para el reporte de estudios tomados</h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioPAC();
                                modalPACVisible = false;
                            "
                        >
                            &times;
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
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioPAC();
                                modalPACVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporteEstudiosPAC()"
                            :disabled="loadingDescargarPAC"
                        >
                            <i v-if="loadingDescargarPAC" class="fa fa-spinner fa-spin mr-1"></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reporte
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Dosimetría / Control de Radiación -->
        <div
            v-if="modalDosimetriaVisible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-hidden="false"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 1050"
            @click.self="
                resetearFormularioDosimetria();
                modalDosimetriaVisible = false;
            "
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            Filtro para reporte de Dosimetría (Res. 482 de 2018)
                        </h4>
                        <button
                            type="button"
                            class="close"
                            aria-label="Cerrar modal"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioDosimetria();
                                modalDosimetriaVisible = false;
                            "
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label class="control-label">Sede Hospitalaria</label>
                                <select
                                    class="form-control custom-select"
                                    style="width: 100%; height: 36px"
                                    v-model="datosDosimetria.sedeId"
                                    id="dosis-sede"
                                >
                                    <option value="0">...::: Todas las sedes :::...</option>
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
                                                id="dosis-fechaIni"
                                                v-model="datosDosimetria.fechaIni"
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
                                                id="dosis-fechaFin"
                                                v-model="datosDosimetria.fechaFin"
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
                            class="btn btn-inverse btn-action-clinical waves-effect"
                            title="Cerrar (Esc)"
                            @click="
                                resetearFormularioDosimetria();
                                modalDosimetriaVisible = false;
                            "
                        >
                            Cerrar
                            <span class="badge badge-secondary ml-1">Esc</span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-info btn-action-clinical waves-effect waves-light"
                            title="Descargar reporte en Excel (Ctrl+S)"
                            @click="descargarReporteDosimetria()"
                            :disabled="loadingDescargarDosimetria"
                        >
                            <i
                                v-if="loadingDescargarDosimetria"
                                class="fa fa-spinner fa-spin mr-1"
                            ></i>
                            <i v-else class="fa fa-download mr-1"></i>
                            Descargar reporte
                            <span class="badge badge-light text-dark ml-1">Ctrl+S</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onMounted, ref } from "vue";

import { listarUsuariosYSedes } from "../../../helpers/usuarios";
import { toastMessage } from "@/helpers/alerts.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
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
const datosDosimetria = ref({ sedeId: 0, fechaIni: "", fechaFin: "" });
const quienes_asignaron = ref([]);
const medicos = ref([]);
const tipoReporte = ref("Rx");
const loadingSedes = ref(false);
const loadingDescargarProduccion = ref(false);
const loadingDescargarPAC = ref(false);
const loadingDescargarDosimetria = ref(false);
const modalProduccionVisible = ref(false);
const modalPACVisible = ref(false);
const modalDosimetriaVisible = ref(false);

function resetearFormulario() {
    datosReporte.value = { sedeId: 0, quien_asigno: "", medico: "", fechaIni: "", fechaFin: "" };
}

function resetearFormularioPAC() {
    datosPAC.value = { sedeId: 0, fechaIni: "", fechaFin: "" };
}

function resetearFormularioDosimetria() {
    datosDosimetria.value = { sedeId: 0, fechaIni: "", fechaFin: "" };
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
    if (loadingSedes.value) return;
    loadingSedes.value = true;
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
    } finally {
        loadingSedes.value = false;
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

/**
 * Descarga el reporte de dosimetría y control de radiación (Res. 482 de 2018) en Excel.
 */
async function descargarReporteDosimetria() {
    if (loadingDescargarDosimetria.value) return;
    loadingDescargarDosimetria.value = true;
    try {
        if (!validarFechas(datosDosimetria.value.fechaIni, datosDosimetria.value.fechaFin)) {
            return;
        }

        const params = new URLSearchParams({
            sedeId: String(datosDosimetria.value.sedeId ?? 0),
            fechaIni: String(datosDosimetria.value.fechaIni),
            fechaFin: String(datosDosimetria.value.fechaFin),
        });

        const url = `/descarga-reportes-dosimetria?${params.toString()}`;
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
            text: "No fue posible generar el reporte de dosimetría.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingDescargarDosimetria.value = false;
        }, 700);
    }
}

function abrirModalProduccion(tipo) {
    tipoReporte.value = tipo;
    modalProduccionVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("prod-sede");
        if (el) el.focus();
    });
}

function abrirModalPAC() {
    modalPACVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("pac-sede");
        if (el) el.focus();
    });
}

function abrirModalDosimetria() {
    modalDosimetriaVisible.value = true;
    nextTick(() => {
        const el = document.getElementById("dosis-sede");
        if (el) el.focus();
    });
}

onMounted(() => {
    listarSedes();
});

useKeyboardShortcuts({
    onSearch: () => {
        if (
            !modalProduccionVisible.value &&
            !modalPACVisible.value &&
            !modalDosimetriaVisible.value &&
            !loadingSedes.value
        ) {
            listarSedes();
        }
    },
    onSave: () => {
        if (modalProduccionVisible.value && !loadingDescargarProduccion.value) {
            descargarReporte();
        } else if (modalPACVisible.value && !loadingDescargarPAC.value) {
            descargarReporteEstudiosPAC();
        } else if (modalDosimetriaVisible.value && !loadingDescargarDosimetria.value) {
            descargarReporteDosimetria();
        }
    },
    onEscape: () => {
        if (modalProduccionVisible.value) {
            resetearFormulario();
            modalProduccionVisible.value = false;
        } else if (modalPACVisible.value) {
            resetearFormularioPAC();
            modalPACVisible.value = false;
        } else if (modalDosimetriaVisible.value) {
            resetearFormularioDosimetria();
            modalDosimetriaVisible.value = false;
        }
    },
});
</script>

<style scoped>
.btn-action-clinical {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.btn-action-clinical:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
