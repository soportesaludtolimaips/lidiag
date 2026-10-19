<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Configuración</a></li>
                    <li class="breadcrumb-item active">Helpdesk - Categorías</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-action-clinical m-r-5"
                    @click="listar"
                    :disabled="loadingListar"
                    title="Actualizar listado (F2)"
                >
                    <i class="fa" :class="loadingListar ? 'fa-spinner fa-spin' : 'fa-refresh'"></i>
                    Refrescar
                    <span
                        class="badge badge-light text-dark ml-1 d-none d-md-inline"
                        style="font-size: 10px; opacity: 0.9"
                        >F2</span
                    >
                </button>
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar btn-action-clinical"
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
                        <h4 class="card-title">Listado de categorías</h4>
                        <h6 class="card-subtitle">Catálogo de Helpdesk.</h6>
                        <div v-if="loadingListar" class="text-center m-t-40">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="text-muted m-t-10">Cargando categorías...</p>
                        </div>
                        <div v-else class="table-responsive m-t-40">
                            <table
                                id="example23"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.nombre }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.activo"
                                                >Activo</span
                                            >
                                            <span class="label label-danger" v-else>Inactivo</span>
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
                                                @click="desactivarRegistro(item)"
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
            style="width: 450px"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <button
                            type="button"
                            class="close right-side-toggle"
                            aria-label="Cerrar panel"
                            title="Cerrar (Esc)"
                            @click="cerrarSidebar"
                        >
                            &times;
                        </button>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#" @submit.prevent>
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Nombre</label>
                                        <input
                                            id="categoria_nombre"
                                            type="text"
                                            v-model="registro.nombre"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.nombre }"
                                            maxlength="100"
                                        />
                                        <span class="text-danger" v-if="errores.nombre">{{
                                            Array.isArray(errores.nombre)
                                                ? errores.nombre[0]
                                                : errores.nombre
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
                                    class="btn btn-success m-r-5 btn-action-clinical"
                                    v-if="!actualizar"
                                    @click="guardarRegistro"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    Guardar
                                    <span
                                        class="badge badge-light text-dark ml-1 d-none d-md-inline"
                                        style="font-size: 10px; opacity: 0.9"
                                        >Ctrl+S</span
                                    >
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-warning m-r-5 btn-action-clinical"
                                    v-if="actualizar"
                                    @click="guardarRegistro"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-pencil'"
                                    ></i>
                                    Actualizar
                                    <span
                                        class="badge badge-light text-dark ml-1 d-none d-md-inline"
                                        style="font-size: 10px; opacity: 0.9"
                                        >Ctrl+S</span
                                    >
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse btn-action-clinical"
                                    @click="cerrarSidebar"
                                >
                                    Cancelar
                                    <span
                                        class="badge badge-secondary ml-1 d-none d-md-inline"
                                        style="font-size: 10px"
                                        >Esc</span
                                    >
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
import { toastMessage, confirmAction } from "@/helpers/alerts.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts.js";
import axios from "axios";

const registros = ref([]);
const registro = ref({ id: 0, nombre: "", activo: true });
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);
const sidebarVisible = ref(false);

const errores = ref({});

function clearErrors() {
    errores.value = {};
}

function setErrors(newErrors) {
    errores.value = newErrors || {};
}

async function listar() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    clearErrors();
    try {
        const res = await axios.get("/api/helpdesk-categorias");
        safeDestroyDataTable(dt.value || "#example23");
        registros.value = res?.data?.data || res?.data || [];
        nextTick(() => {
            safeInitDataTable(dt.value || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible listar las categorías.",
            icon: "error",
        });
    } finally {
        loadingListar.value = false;
    }
}

function prepararEdicion(item) {
    actualizar.value = true;
    tituloModal.value = "Actualizar registro";
    registro.value = { id: item.id, nombre: item.nombre, activo: !!item.activo };
    clearErrors();
    abrirSidebar();
}

function prepararNuevo() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    registro.value = { id: 0, nombre: "", activo: true };
    clearErrors();
    abrirSidebar();
}

function abrirSidebar() {
    sidebarVisible.value = true;
    nextTick(() => {
        document.getElementById("categoria_nombre")?.focus();
    });
}

function cerrarSidebar() {
    sidebarVisible.value = false;
}

useKeyboardShortcuts({
    onSearch: listar,
    onSave: guardarRegistro,
    onEscape: cerrarSidebar,
    isActive: sidebarVisible,
    isLoading: loadingGuardar,
});

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    clearErrors();
    try {
        if (!actualizar.value) {
            await axios.post("/api/helpdesk-categorias", {
                nombre: registro.value.nombre,
                activo: registro.value.activo ? 1 : 0,
            });
            toastMessage({ heading: "Ok", text: "Categoría creada.", icon: "success" });
        } else {
            await axios.put(`/api/helpdesk-categorias/${registro.value.id}`, {
                nombre: registro.value.nombre,
                activo: registro.value.activo ? 1 : 0,
            });
            toastMessage({
                heading: "Ok",
                text: "Categoría actualizada.",
                icon: "success",
            });
        }
        cerrarSidebar();
        await listar();
    } catch (error) {
        if (error?.response?.status === 422) {
            setErrors(error.response.data.errors || {});
        } else {
            toastMessage({
                heading: "Error",
                text: "No fue posible guardar la categoría.",
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

async function desactivarRegistro(item) {
    const ok = await confirmAction({
        title: "¿Desactivar?",
        text: "El registro quedará inactivo.",
    });
    if (!ok) return;
    loadingEliminarId.value = item.id;
    try {
        await axios.delete(`/api/helpdesk-categorias/${item.id}`);
        toastMessage({ heading: "Ok", text: "Categoría desactivada.", icon: "success" });
        await listar();
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible desactivar la categoría.",
            icon: "error",
        });
    } finally {
        loadingEliminarId.value = null;
    }
}

onMounted(() => {
    listar();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#example23");
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
    backdrop-filter: blur(2px);
    z-index: 1090;
}

.right-sidebar {
    position: fixed;
    right: -450px;
    width: 450px;
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

@media (max-width: 768px) {
    .right-sidebar {
        width: 100% !important;
        right: -100% !important;
    }
}
</style>
