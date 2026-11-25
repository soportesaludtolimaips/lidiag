<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
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
                            Configuración
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Oftalmología
                        </button>
                    </li>
                    <li class="breadcrumb-item active">{{ tituloSeccion }}</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary m-r-5"
                    @click="cargarPlantillas"
                    :disabled="cargando"
                    title="Actualizar listado"
                >
                    <i class="fa" :class="cargando ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="
                        actualizar = false;
                        mostrarRegistro();
                    "
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
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
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Plantillas de Oftalmología</h4>
                        <h6 class="card-subtitle">
                            Administración y parametrización de plantillas clínicas predeterminadas
                            para consulta, medicamentos y procedimientos de oftalmología.
                        </h6>

                        <!-- Filtro y Pestañas por Sección -->
                        <ul class="nav nav-tabs customtab m-b-20">
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: seccionFiltro === 'CONCEPTO_PLAN' }"
                                    href="javascript:void(0)"
                                    @click="cambiarSeccion('CONCEPTO_PLAN')"
                                >
                                    <i class="fa fa-file-text-o m-r-5 text-primary"></i> Plantillas
                                    Consulta
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: seccionFiltro === 'MEDICAMENTOS' }"
                                    href="javascript:void(0)"
                                    @click="cambiarSeccion('MEDICAMENTOS')"
                                >
                                    <i class="fa fa-medkit m-r-5 text-success"></i> Plantillas
                                    Medicamentos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: seccionFiltro === 'PROCEDIMIENTOS' }"
                                    href="javascript:void(0)"
                                    @click="cambiarSeccion('PROCEDIMIENTOS')"
                                >
                                    <i class="fa fa-list-alt m-r-5 text-info"></i> Plantillas
                                    Procedimientos
                                </a>
                            </li>
                        </ul>

                        <table-skeleton-loader :visible="cargando" :rows="5" :columns="5" />
                        <empty-state-component
                            v-if="!cargando && plantillas.length === 0"
                            icon="fa fa-folder-open-o"
                            title="No hay plantillas registradas"
                            description="No se encontraron plantillas configuradas para esta sección clínica."
                            action-text="Nuevo registro"
                            action-icon="fa fa-plus"
                            @action="
                                actualizar = false;
                                mostrarRegistro();
                            "
                        />

                        <!-- Listado de Plantillas con DataTables nativo -->
                        <div
                            v-show="!cargando && plantillas.length > 0"
                            class="table-responsive m-t-10"
                        >
                            <table
                                id="tablaPlantillasOftalmologia"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th style="width: 25%">Título</th>
                                        <th style="width: 45%">Contenido / Formulación</th>
                                        <th style="width: 15%">Alcance / Médico</th>
                                        <th style="width: 8%" class="text-center">Estado</th>
                                        <th style="width: 7%" class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in plantillas" :key="item.id">
                                        <td class="font-weight-bold text-dark align-middle">
                                            {{ item.titulo }}
                                        </td>
                                        <td
                                            class="align-middle text-muted small"
                                            style="white-space: pre-wrap"
                                        >
                                            {{ truncarTexto(item.contenido, 180) }}
                                        </td>
                                        <td class="align-middle small">
                                            <span v-if="item.medico" class="label label-info">
                                                <i class="fa fa-user-md m-r-5"></i
                                                >{{ item.medico.name || item.medico.nombre }}
                                            </span>
                                            <span v-else class="label label-light-inverse">
                                                <i class="fa fa-building-o m-r-5"></i>Institucional
                                            </span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="label label-success" v-if="item.activo">
                                                Activo
                                            </span>
                                            <span class="label label-info" v-else> Inactivo </span>
                                        </td>
                                        <td class="text-nowrap text-center align-middle">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                title="Editar"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                title="Eliminar"
                                                @click="confirmarEliminar(item)"
                                                :disabled="loadingEliminarId === item.id"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        loadingEliminarId === item.id
                                                            ? 'fa-spinner fa-spin'
                                                            : 'fa-trash'
                                                    "
                                                ></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- Backdrop overlay para cerrar al hacer clic afuera -->
        <div v-if="sidebarVisible" class="sidebar-backdrop" @click="cerrarSidebar()"></div>

        <!-- Right sidebar (Drawer nativo AdminWrap) -->
        <div
            ref="sidebarRef"
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible }"
            style="overflow-x: hidden; overflow-y: auto; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="cerrarSidebar()"
                        aria-label="Cerrar panel"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <form action="#" @submit.prevent="guardarPlantilla()">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="seccion"
                                            >Sección Clínica
                                            <span class="text-danger">*</span></label
                                        >
                                        <select
                                            id="seccion"
                                            v-model="form.seccion"
                                            class="form-control"
                                            required
                                        >
                                            <option value="CONCEPTO_PLAN">
                                                Plantilla Consulta (Concepto y Plan)
                                            </option>
                                            <option value="MEDICAMENTOS">
                                                Plantilla Medicamentos (Formulación)
                                            </option>
                                            <option value="PROCEDIMIENTOS">
                                                Plantilla Procedimientos (Órdenes)
                                            </option>
                                            <option value="EXAMEN_FISICO">
                                                Examen Físico Oftalmológico
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label" for="titulo"
                                            >Título de la Plantilla
                                            <span class="text-danger">*</span></label
                                        >
                                        <input
                                            type="text"
                                            id="titulo"
                                            v-model="form.titulo"
                                            class="form-control"
                                            placeholder="Ej: Glaucoma de ángulo abierto..."
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label" for="contenido"
                                            >Contenido de la Plantilla
                                            <span class="text-danger">*</span></label
                                        >
                                        <textarea
                                            id="contenido"
                                            v-model="form.contenido"
                                            class="form-control"
                                            rows="8"
                                            placeholder="Escriba aquí el texto o formato que se insertará en la historia clínica..."
                                            required
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="checkInstitucional"
                                                v-model="form.es_institucional"
                                            />
                                            <label for="checkInstitucional">
                                                Plantilla institucional (global para todos los
                                                médicos)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="checkActivo"
                                                v-model="form.activo"
                                            />
                                            <label for="checkActivo"> Plantilla activa </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarPlantilla()"
                                    v-if="!actualizar"
                                    :disabled="guardando"
                                >
                                    <i
                                        class="fa"
                                        :class="guardando ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-warning m-r-5"
                                    v-if="actualizar"
                                    @click="guardarPlantilla()"
                                    :disabled="guardando"
                                >
                                    <i
                                        class="fa"
                                        :class="guardando ? 'fa-spinner fa-spin' : 'fa-pencil'"
                                    ></i>
                                    Actualizar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarSidebar()"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Right sidebar -->
        <!-- ============================================================== -->
    </div>
</template>

<script setup>
/**
 * Componente de Configuración de Plantillas Clínicas de Oftalmología.
 *
 * Flujo del proceso:
 * 1. Lista las plantillas registradas filtradas por sección clínica (CONCEPTO_PLAN, MEDICAMENTOS, PROCEDIMIENTOS).
 * 2. Inicializa DataTables de forma segura con botones de exportación estándar.
 * 3. Permite crear, actualizar y eliminar plantillas mediante el drawer nativo AdminWrap (.right-sidebar).
 *
 * @author Jhon Javer Lozano Arce
 * @date 2026-11-20
 */
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useRoute } from "vue-router";
import axios from "axios";
import { alertError, alertSuccess, confirmAction, toastMessage } from "@/helpers/alerts.js";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";

const route = useRoute();

const dt = ref(null);
const sidebarRef = ref(null);
const seccionFiltro = ref(route.query.seccion || "CONCEPTO_PLAN");
const plantillas = ref([]);
const cargando = ref(false);
const guardando = ref(false);
const loadingEliminarId = ref(null);

const sidebarVisible = ref(false);
const actualizar = ref(false);
const tituloModal = ref("Nuevo registro");
const plantillaSeleccionadaId = ref(null);

const form = reactive({
    seccion: "CONCEPTO_PLAN",
    titulo: "",
    contenido: "",
    es_institucional: true,
    activo: true,
});

const tituloSeccion = computed(() => {
    switch (seccionFiltro.value) {
        case "MEDICAMENTOS":
            return "Plantillas de Órdenes de Medicamentos";
        case "PROCEDIMIENTOS":
            return "Plantillas de Órdenes de Procedimientos";
        default:
            return "Plantillas de Consulta";
    }
});

function cambiarSeccion(sec) {
    seccionFiltro.value = sec;
    cargarPlantillas();
}

async function cargarPlantillas() {
    if (cargando.value) return;
    cargando.value = true;
    try {
        const res = await axios.get("/api/config-plantillas-oftalmologia", {
            params: { seccion: seccionFiltro.value },
        });
        safeDestroyDataTable(dt.value || "#tablaPlantillasOftalmologia");
        if (res.data?.success) {
            plantillas.value = res.data.data || [];
        } else {
            plantillas.value = [];
        }
        nextTick(() => {
            safeInitDataTable(dt.value || "#tablaPlantillasOftalmologia", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch {
        plantillas.value = [];
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar las plantillas de oftalmología.",
            icon: "error",
        });
    } finally {
        cargando.value = false;
    }
}

function abrirSidebar() {
    sidebarVisible.value = true;
    nextTick(() => {
        document.getElementById("titulo")?.focus();
    });
}

function cerrarSidebar() {
    sidebarVisible.value = false;
}

function mostrarRegistro(item = null) {
    if (actualizar.value && item) {
        tituloModal.value = `Actualizar plantilla: ${item.titulo}`;
        plantillaSeleccionadaId.value = item.id;
        form.seccion = item.seccion;
        form.titulo = item.titulo;
        form.contenido = item.contenido;
        form.es_institucional = !item.medico_id;
        form.activo = Boolean(item.activo);
    } else {
        actualizar.value = false;
        tituloModal.value = "Nuevo registro";
        plantillaSeleccionadaId.value = null;
        form.seccion = seccionFiltro.value;
        form.titulo = "";
        form.contenido = "";
        form.es_institucional = true;
        form.activo = true;
    }
    abrirSidebar();
}

async function guardarPlantilla() {
    if (guardando.value) return;
    guardando.value = true;
    try {
        if (actualizar.value) {
            const res = await axios.put(
                `/api/config-plantillas-oftalmologia/${plantillaSeleccionadaId.value}`,
                form
            );
            if (res.data?.success) {
                alertSuccess("Ok!", "Plantilla actualizada con éxito.", "success");
                cerrarSidebar();
                await cargarPlantillas();
            }
        } else {
            const res = await axios.post("/api/config-plantillas-oftalmologia", form);
            if (res.data?.success) {
                alertSuccess("Ok!", "Plantilla registrada con éxito.", "success");
                cerrarSidebar();
                await cargarPlantillas();
            }
        }
    } catch (e) {
        alertError("Error", e.response?.data?.message || "No fue posible guardar la plantilla.");
    } finally {
        guardando.value = false;
    }
}

async function confirmarEliminar(item) {
    const confirmed = await confirmAction({
        title: "¿Estas seguro?",
        text: `No podrás recuperar la plantilla "${item.titulo}"!`,
        icon: "warning",
    });

    if (confirmed) {
        try {
            if (loadingEliminarId.value) return;
            loadingEliminarId.value = item.id;
            const res = await axios.delete(`/api/config-plantillas-oftalmologia/${item.id}`);
            if (res.data?.success) {
                alertSuccess("Ok!", "Plantilla eliminada correctamente.", "success");
                await cargarPlantillas();
            }
        } catch (e) {
            alertError(
                "Error",
                e.response?.data?.message || "No fue posible eliminar la plantilla."
            );
        } finally {
            loadingEliminarId.value = null;
        }
    }
}

function truncarTexto(txt, max) {
    if (!txt) return "";
    return txt.length > max ? txt.substring(0, max) + "..." : txt;
}

onMounted(() => {
    cargarPlantillas();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#tablaPlantillasOftalmologia");
});
</script>

<style scoped>
.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1090;
}

.right-sidebar {
    position: fixed;
    right: -50%;
    width: 50%;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
    visibility: hidden;
    pointer-events: none;
    overflow-y: auto;
}

.shw-rside {
    right: 0px !important;
    visibility: visible !important;
    pointer-events: auto !important;
}

@media (max-width: 767px) {
    .right-sidebar {
        width: 100% !important;
        right: -100% !important;
    }
}
</style>
