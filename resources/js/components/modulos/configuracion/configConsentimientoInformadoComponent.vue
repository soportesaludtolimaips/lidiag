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
                    <li class="breadcrumb-item active">Consentimiento informado</li>
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
                        <h4 class="card-title">Listado de consentimiento informado</h4>
                        <h6 class="card-subtitle">
                            Administración de los consentimiento informado.
                        </h6>
                        <table-skeleton-loader :visible="loadingListar" :rows="6" :columns="8" />
                        <empty-state-component
                            v-if="!loadingListar && registros.length === 0"
                            icon="fa fa-file-pdf-o"
                            title="No hay consentimientos registrados"
                            description="No se encontraron consentimientos informados en el sistema."
                            action-text="Nuevo registro"
                            action-icon="fa fa-plus"
                            @action="prepararNuevo"
                        />
                        <div
                            v-show="!loadingListar && registros.length > 0"
                            class="table-responsive m-t-40"
                        >
                            <table
                                id="tablaConsentimientos"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th width="150px">Producto</th>
                                        <th>Sede</th>
                                        <th>Nombre</th>
                                        <th>Archivo</th>
                                        <th>Fecha creación</th>
                                        <th>Fecha modificación</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.producto?.nom_produc || "—" }}</td>
                                        <td>{{ item.sede.nom_sede }}</td>
                                        <td>{{ item.nombre }}</td>
                                        <td>
                                            <a
                                                v-if="item.archivo"
                                                :href="archivoUrl(item)"
                                                target="_blank"
                                                >Ver PDF</a
                                            >
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                        <td>{{ item.created_at }}</td>
                                        <td>{{ item.updated_at }}</td>
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
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="eliminarRegistro(item.id, item.nombre)"
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
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Producto</label>
                                        <Select2
                                            :value="productoSeleccionado"
                                            :options="productoOptions"
                                            width="100%"
                                            :allow-clear="true"
                                            @input="onProductoSelect"
                                        />
                                        <span class="text-danger" v-if="errores.producto_id">{{
                                            errores.producto_id[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label"
                                            >Nombre de consentimiento informado</label
                                        >
                                        <input
                                            type="text"
                                            id="nombre"
                                            name="nombre"
                                            v-model="registro.nombre"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.nombre }"
                                            placeholder="Ingrese el nombre del consentimiento"
                                        />
                                        <span class="text-danger" v-if="errores.nombre">{{
                                            errores.nombre[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Archivo PDF</label>
                                        <input
                                            type="file"
                                            class="form-control"
                                            id="archivo"
                                            accept=".pdf"
                                            @change="obtenerArchivo"
                                        />
                                        <span
                                            class="text-danger"
                                            v-if="errores.hasError('archivo')"
                                            >{{ errores.getError("archivo") }}</span
                                        >
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const dt = ref(null);
const sidebarRef = ref(null);

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { confirmAction, toastMessage } from "@/helpers/alerts.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import Select2 from "@/components/shared/Select2.vue";
import axios from "axios";

const id = ref(0);
const registros = ref([]);
const registro = ref({
    producto_id: null,
    sede_id: sessionStorage.getItem("ST-sede"),
    nombre: "",
    estado: true,
});
const productoSeleccionado = ref(null);
const archivoFile = ref(null);
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);
const productos = ref([]);
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

const errores = ref({});

function clearErrors() {
    errores.value = {};
}

function setErrors(newErrors) {
    errores.value = newErrors || {};
}

const productoOptions = computed(() => {
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});

async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    clearErrors();
    try {
        const res = await axios.get("/api/config-consentimiento-informado");
        safeDestroyDataTable(dt.value || "#tablaConsentimientos");
        registros.value = res?.data?.data || res?.data || [];
        nextTick(() => {
            safeInitDataTable(dt.value || "#tablaConsentimientos", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch {
        registros.value = [];
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los consentimientos informados.",
            icon: "error",
        });
    } finally {
        loadingListar.value = false;
    }
}

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    clearErrors();
    try {
        const sedeId = sessionStorage.getItem("ST-sede");
        const formData = new FormData();
        formData.append("producto_id", String(registro.value.producto_id ?? ""));
        formData.append("sede_id", String(registro.value.sede_id ?? sedeId ?? ""));
        formData.append("nombre", String(registro.value.nombre ?? ""));
        formData.append("estado", registro.value.estado ? "1" : "0");
        if (archivoFile.value) formData.append("archivo", archivoFile.value);

        let res;
        if (!actualizar.value) {
            res = await axios.post("/api/config-consentimiento-informado", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
        } else {
            formData.append("_method", "PUT");
            res = await axios.post("/api/config-consentimiento-informado/" + id.value, formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
        }

        if (res.status === 200 || res.status === 201) {
            toastMessage({
                heading: "Ok!!!",
                text: res.data.data?.message || "Guardado correctamente.",
                icon: "success",
            });
            ListarDatos();
            limpiarFormulario();
            cerrarSidebar();
        }
    } catch (error) {
        if (error.response?.status === 422) {
            setErrors(error.response.data.errors || {});
        } else {
            toastMessage({
                heading: "Error",
                text: error.response?.data?.message || "No fue posible guardar el consentimiento.",
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

async function eliminarRegistro(idEliminar, nom) {
    const ok = await confirmAction({
        title: `¿Estás seguro de eliminar el consentimiento "${nom}"?`,
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;
    if (loadingEliminarId.value) return;
    loadingEliminarId.value = idEliminar;
    try {
        const response = await axios.delete("/api/config-consentimiento-informado/" + idEliminar);
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

async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = res?.data?.data || res?.data || [];
    } catch {
        toastMessage({
            heading: "Error",
            text: "No se pudieron cargar los productos.",
            icon: "error",
        });
    }
}

function obtenerArchivo(e) {
    archivoFile.value = e.target.files?.[0] || null;
}

function onProductoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    registro.value.producto_id = selectedId ? Number(selectedId) : null;
    productoSeleccionado.value = selectedId;
}

function archivoUrl(item) {
    if (!item?.archivo) return null;
    return `/storage/${item.archivo}`;
}

function prepararEdicion(data) {
    if (data && data.id) {
        actualizar.value = true;
        tituloModal.value = "Actualizar el registro: " + (data.nombre || "");
        id.value = data.id;
        registro.value.producto_id = data.producto_id;
        productoSeleccionado.value = String(data.producto_id ?? "");
        registro.value.sede_id = data.sede_id;
        registro.value.nombre = data.nombre;
        registro.value.estado = !!data.estado;
        archivoFile.value = null;
        clearErrors();
    } else {
        prepararNuevo();
    }
    abrirSidebar();
}

function prepararNuevo() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    limpiarFormulario();
    abrirSidebar();
}

function limpiarFormulario() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    clearErrors();
    registro.value.producto_id = null;
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    registro.value.nombre = "";
    registro.value.estado = true;
    productoSeleccionado.value = null;
    archivoFile.value = null;
}

function abrirSidebar() {
    sidebarVisible.value = true;
    nextTick(() => {
        document.getElementById("nombre")?.focus();
    });
}

function cerrarSidebar() {
    sidebarVisible.value = false;
}

onMounted(() => {
    ListarDatos();
    listarProductos();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#tablaConsentimientos");
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

@media (max-width: 767px) {
    .right-sidebar {
        width: 100% !important;
        right: -100% !important;
    }
}
</style>
