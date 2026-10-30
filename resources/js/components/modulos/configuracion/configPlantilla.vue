<template>
    <div>
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
                    <li class="breadcrumb-item active">Plantillas</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary m-r-5"
                    @click="ListarDatos"
                    :disabled="loadingListar"
                    title="Actualizar listado"
                >
                    <i class="fa" :class="loadingListar ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="prepararNuevo()"
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de plantillas</h4>
                        <h6 class="card-subtitle">Administración de las tipos de plantillas.</h6>
                        <table-skeleton-loader :visible="loadingListar" :rows="6" :columns="5" />
                        <empty-state-component
                            v-if="!loadingListar && registrosData.length === 0"
                            icon="fa fa-file-text-o"
                            title="No hay plantillas registradas"
                            description="No se encontraron plantillas de informe radiológico."
                            action-text="Nuevo registro"
                            action-icon="fa fa-plus"
                            @action="prepararNuevo()"
                        />
                        <div
                            v-show="!loadingListar && registrosData.length > 0"
                            class="table-responsive m-t-40"
                        >
                            <table
                                id="tablaPlantillas"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th width="150px">Tipo de Plantilla</th>
                                        <th>Plantilla</th>
                                        <th>Medico</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registrosData" :key="item.id">
                                        <td>{{ item.plantilla_tipo?.nom_tipo || "" }}</td>
                                        <td>{{ item.nom_plantilla }}</td>
                                        <td>{{ item.medico?.name || "" }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado"
                                                >Activo</span
                                            >
                                            <span class="label label-danger" v-if="!item.estado"
                                                >Inactivo</span
                                            >
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="prepararEdicion(item)"
                                                :disabled="loadingEliminarId === item.id"
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="
                                                    eliminarRegistro(item.id, item.nom_plantilla)
                                                "
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

        <!-- Backdrop overlay para cerrar al hacer clic afuera -->
        <div v-if="sidebarVisible" class="sidebar-backdrop" @click="cerrarSidebar"></div>

        <div class="right-sidebar" ref="sidebarRef" :class="{ 'shw-rside': sidebarVisible }">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" @click="cerrarSidebar"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body" style="padding-top: 5px">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Nombre de la plantilla</label>
                                        <input
                                            type="text"
                                            id="nom_plantilla"
                                            name="nom_plantilla"
                                            v-model="registro.nom_plantilla"
                                            class="form-control"
                                            placeholder="Nombre de la plantilla"
                                        />
                                        <span class="text-danger" v-if="errores.nom_plantilla">{{
                                            errores.nom_plantilla[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Medico</label>
                                        <select
                                            class="form-control custom-select"
                                            style="width: 100%; height: 36px"
                                            v-model="registro.medico_id"
                                            name="medico_id"
                                        >
                                            <option>Seleccione el médico</option>
                                            <option
                                                v-for="ItemMedico in medicos"
                                                :key="ItemMedico.id"
                                                :value="ItemMedico.id"
                                            >
                                                {{ ItemMedico.name }}
                                            </option>
                                        </select>
                                        <span class="text-danger" v-if="errores.medico_id">{{
                                            errores.medico_id[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Tipo de plantilla</label>
                                        <select
                                            class="form-control custom-select"
                                            style="width: 100%; height: 36px"
                                            v-model="registro.plantilla_tipo_id"
                                            name="plantilla_tipo_id"
                                        >
                                            <option>Seleccione el tipo de plantilla</option>
                                            <option
                                                v-for="ItemTipoPlantilla in tiposPlantillas"
                                                :key="ItemTipoPlantilla.id"
                                                :value="ItemTipoPlantilla.id"
                                            >
                                                {{ ItemTipoPlantilla.nom_tipo }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Plantillas</label>
                                        <WysiwygEditor
                                            v-model="registro.plantilla"
                                            placeholder="Descripción de la plantilla"
                                            :rows="4"
                                        />
                                        <span class="text-danger" v-if="errores.plantilla">{{
                                            errores.plantilla[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Opinion</label>
                                        <WysiwygEditor
                                            v-model="registro.opinion"
                                            placeholder="Opinion"
                                            :rows="3"
                                        />
                                        <span class="text-danger" v-if="errores.opinion">{{
                                            errores.opinion[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input
                                                type="checkbox"
                                                id="estado"
                                                name="estado"
                                                v-model="registro.estado"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="estado"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    v-if="!actualizar"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-warning m-r-5"
                                    v-if="actualizar"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-pencil'"
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
    </div>
</template>
<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const dt = ref(null);
const sidebarRef = ref(null);

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { confirmAction, toastMessage } from "@/helpers/alerts.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import WysiwygEditor from "@/components/shared/WysiwygEditor.vue";
import axios from "axios";

const id = ref(0);
const registrosData = ref([]);
const registro = ref({
    plantilla_tipo_id: "",
    medico_id: "",
    nom_plantilla: "",
    plantilla: "",
    opinion: "",
    estado: true,
});
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const errores = ref({});
const medicos = ref([]);
const tiposPlantillas = ref([]);
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);
const sidebarVisible = ref(false);

useKeyboardShortcuts({
    onSearch: () => {
        if (!sidebarVisible.value) {
            ListarDatos();
        }
    },
    onSave: () => {
        if (sidebarVisible.value) {
            guardarRegistro();
        }
    },
    onEscape: cerrarSidebar,
    isActive: () => true,
    isLoading: loadingGuardar,
});

async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        const res = await axios.get("/api/config-plantillas");
        registrosData.value = res?.data?.data || res?.data || [];

        safeDestroyDataTable(dt.value || "#tablaPlantillas");

        nextTick(() => {
            safeInitDataTable(dt.value || "#tablaPlantillas", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch {
        registrosData.value = [];
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar el listado de plantillas.",
            icon: "error",
        });
    } finally {
        loadingListar.value = false;
    }
}

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    errores.value = {};
    try {
        if (actualizar.value === false) {
            const res = await axios.post("/api/config-plantillas", registro.value);

            if (res.status >= 200 && res.status < 300) {
                toastMessage({
                    heading: "Ok!!!",
                    text: res.data?.data?.message || res.data?.message || "Guardado correctamente.",
                    icon: "success",
                });

                ListarDatos();
                limpiarFormulario();
                cerrarSidebar();
            }
        } else {
            const res = await axios.put("/api/config-plantillas/" + id.value, registro.value);
            if (res.status >= 200 && res.status < 300) {
                toastMessage({
                    heading: "Ok!!!",
                    text:
                        res.data?.data?.message ||
                        res.data?.message ||
                        "Actualizado correctamente.",
                    icon: "success",
                });

                ListarDatos();
                limpiarFormulario();
                cerrarSidebar();
            }
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errores.value = error.response.data?.errors || {};
        } else {
            toastMessage({
                heading: "Error",
                text: error.response?.data?.message || "No fue posible guardar la plantilla.",
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

async function eliminarRegistro(idEliminar, nom) {
    const ok = await confirmAction({
        title: `¿Estás seguro de eliminar la plantilla "${nom}"?`,
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;

    try {
        if (loadingEliminarId.value) return;
        loadingEliminarId.value = idEliminar;
        const response = await axios.delete("/api/config-plantillas/" + idEliminar);
        toastMessage({
            heading: "Ok!",
            text: response?.data?.message || "Registro eliminado.",
            icon: "success",
        });
        ListarDatos();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible eliminar el registro.",
            icon: "error",
        });
    } finally {
        loadingEliminarId.value = null;
    }
}

async function listarMedicos() {
    try {
        const pathname = String(window?.location?.pathname || "").toLowerCase();
        const moduloId =
            sessionStorage.getItem("ST-modulo") || (pathname.includes("ecografia") ? "2" : "1");
        const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
        medicos.value = res?.data?.data || res?.data || [];
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible listar los médicos.",
            icon: "error",
        });
    }
}

async function listarTiposPlantillas() {
    try {
        const res = await axios.get("/api/config-tipos-plantillas/listar/activos");
        tiposPlantillas.value = res?.data?.data || res?.data || [];
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible listar los tipos de plantillas.",
            icon: "error",
        });
    }
}

function prepararNuevo() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    registro.value.plantilla_tipo_id = "";
    registro.value.medico_id = "";
    registro.value.nom_plantilla = "";
    registro.value.plantilla = "";
    registro.value.opinion = "";
    registro.value.estado = true;
    errores.value = {};
    abrirSidebar();
}

function prepararEdicion(data) {
    actualizar.value = true;
    tituloModal.value = "Actualizar el registro: " + data.nom_plantilla;
    id.value = data.id;
    registro.value.plantilla_tipo_id = data.plantilla_tipo_id;
    registro.value.medico_id = data.medico_id;
    registro.value.nom_plantilla = data.nom_plantilla;
    registro.value.plantilla = data.plantilla;
    registro.value.opinion = data.opinion;
    registro.value.estado = data.estado;
    errores.value = {};
    abrirSidebar();
}

function limpiarFormulario() {
    errores.value = {};
    registro.value.plantilla_tipo_id = "";
    registro.value.medico_id = "";
    registro.value.nom_plantilla = "";
    registro.value.plantilla = "";
    registro.value.opinion = "";
    registro.value.estado = true;
}

function abrirSidebar() {
    sidebarVisible.value = true;
    nextTick(() => {
        document.getElementById("nom_plantilla")?.focus();
    });
}

function cerrarSidebar() {
    sidebarVisible.value = false;
}

onMounted(() => {
    ListarDatos();
    listarMedicos();
    listarTiposPlantillas();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#tablaPlantillas");
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
    right: -80%;
    width: 80%;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    overflow-y: auto;
    box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
    transition:
        right 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        visibility 0.35s;
    visibility: hidden;
    pointer-events: none;
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

.right-sidebar .form-group {
    margin-bottom: 10px;
}

.right-sidebar .form-body .row .col-md-12,
.right-sidebar .form-body .row .col-md-6 {
    margin-bottom: 0;
}
</style>
