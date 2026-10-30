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
                    <li class="breadcrumb-item active">Motivos de Rechazo (Citas)</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary m-r-5"
                    @click="listarMotivos"
                    :disabled="loadingListar"
                    title="Actualizar listado"
                >
                    <i class="fa" :class="loadingListar ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                </button>
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="prepararNuevo"
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Motivos de Rechazo</h4>
                        <h6 class="card-subtitle">
                            Administración de motivos de rechazo para las citas agendadas.
                        </h6>

                        <div v-if="errorMotivos" class="alert alert-danger m-t-20">
                            <i class="fa fa-exclamation-triangle"></i>
                            {{ errorMotivos }}
                        </div>

                        <table-skeleton-loader :visible="loadingListar" :rows="5" :columns="3" />
                        <empty-state-component
                            v-if="!loadingListar && !errorMotivos && registros.length === 0"
                            icon="fa fa-ban"
                            title="No hay motivos de rechazo"
                            description="No se encontraron motivos de rechazo configurados."
                            action-text="Nuevo registro"
                            action-icon="fa fa-plus"
                            @action="prepararNuevo"
                        />

                        <div
                            v-show="!loadingListar && registros.length > 0"
                            class="table-responsive m-t-40"
                        >
                            <table
                                id="tblRechazoMotivos"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Motivo</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.motivo }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.activo"
                                                >Activo</span
                                            >
                                            <span class="label label-danger" v-if="!item.activo"
                                                >Inactivo</span
                                            >
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="prepararEdicion(item)"
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="eliminarRegistro(item.id, item.motivo)"
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

        <div
            class="right-sidebar"
            ref="sidebarRef"
            :class="{ 'shw-rside': sidebarVisible }"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" @click="cerrarSidebar"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Motivo <span class="text-danger">*</span></label
                                        >
                                        <input
                                            type="text"
                                            id="motivo_rechazo"
                                            v-model="registro.motivo"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.motivo }"
                                            placeholder="Ingrese el motivo de rechazo"
                                        />
                                        <span class="text-danger" v-if="errores.motivo">{{
                                            Array.isArray(errores.motivo)
                                                ? errores.motivo[0]
                                                : errores.motivo
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
                                                id="activo"
                                                v-model="registro.activo"
                                                :true-value="1"
                                                :false-value="0"
                                            />
                                            <label for="activo"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro"
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
                                    @click="guardarRegistro"
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
                                    @click="cerrarSidebar"
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
import axios from "axios";

const id = ref(0);
const registros = ref([]);
const registro = ref({ motivo: "", activo: 1 });
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const loadingListar = ref(false);
const errorMotivos = ref("");
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);
const sidebarVisible = ref(false);

useKeyboardShortcuts({
    onSearch: () => {
        if (!sidebarVisible.value) {
            listarMotivos();
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

const errores = ref({});

function clearErrors() {
    errores.value = {};
}

function setErrors(newErrors) {
    errores.value = newErrors || {};
}

async function listarMotivos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    errorMotivos.value = "";
    clearErrors();
    try {
        const res = await axios.get("/api/citas-rechazo-motivos");
        safeDestroyDataTable(dt.value || "#tblRechazoMotivos");
        registros.value = res?.data?.data || res?.data || [];
        nextTick(() => {
            safeInitDataTable(dt.value || "#tblRechazoMotivos", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
        errorMotivos.value =
            error?.response?.data?.message || "No fue posible cargar los motivos de rechazo.";
        registros.value = [];
    } finally {
        loadingListar.value = false;
    }
}

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    clearErrors();
    if (!registro.value.motivo || !registro.value.motivo.trim()) {
        setErrors({ motivo: ["El motivo es obligatorio."] });
        return;
    }
    loadingGuardar.value = true;
    try {
        const payload = {
            motivo: registro.value.motivo,
            activo: registro.value.activo === 1 || registro.value.activo === true,
        };
        let res;
        if (actualizar.value === false) {
            res = await axios.post("/api/citas-rechazo-motivos", payload);
        } else {
            res = await axios.put("/api/citas-rechazo-motivos/" + id.value, payload);
        }

        if (res.status >= 200 && res.status < 300) {
            listarMotivos();
            toastMessage({
                heading: "Ok!!!",
                text: actualizar.value
                    ? "Motivo actualizado correctamente."
                    : "Motivo guardado correctamente.",
                icon: "success",
            });
            cerrarSidebar();
            limpiar();
        }
    } catch (error) {
        if (error.response?.status === 422) {
            setErrors(error.response.data.errors || {});
        } else {
            toastMessage({
                heading: "Error",
                text: error.response?.data?.message || "No fue posible guardar el motivo.",
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

async function eliminarRegistro(idEliminar, nom) {
    const ok = await confirmAction({
        title: `¿Estás seguro de eliminar el motivo "${nom}"?`,
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;
    if (loadingEliminarId.value) return;
    loadingEliminarId.value = idEliminar;
    try {
        const response = await axios.delete("/api/citas-rechazo-motivos/" + idEliminar);
        toastMessage({
            heading: "Ok!",
            text: response?.data?.message || "Registro eliminado.",
            icon: "success",
        });
        listarMotivos();
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

function prepararEdicion(item) {
    actualizar.value = true;
    tituloModal.value = "Actualizar motivo: " + item.motivo;
    id.value = item.id;
    registro.value.motivo = item.motivo;
    registro.value.activo = item.activo ? 1 : 0;
    clearErrors();
    abrirSidebar();
}

function prepararNuevo() {
    actualizar.value = false;
    tituloModal.value = "Nuevo motivo de rechazo";
    id.value = 0;
    registro.value.motivo = "";
    registro.value.activo = 1;
    clearErrors();
    abrirSidebar();
}

function limpiar() {
    actualizar.value = false;
    tituloModal.value = "Nuevo motivo de rechazo";
    id.value = 0;
    registro.value.motivo = "";
    registro.value.activo = 1;
    clearErrors();
}

function abrirSidebar() {
    sidebarVisible.value = true;
    nextTick(() => {
        document.getElementById("motivo_rechazo")?.focus();
    });
}

function cerrarSidebar() {
    sidebarVisible.value = false;
}

onMounted(() => {
    listarMotivos();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#tblRechazoMotivos");
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
