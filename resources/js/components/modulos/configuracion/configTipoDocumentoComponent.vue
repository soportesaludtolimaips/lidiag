<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Configuración</a>
                    </li>
                    <li class="breadcrumb-item active">Tipos de Documentos</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
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
                        <h4 class="card-title">Listado de tipos de documentos</h4>
                        <h6 class="card-subtitle">
                            Administración de tipos de documentos para la prestación del servicio de
                            imágenes diagnósticas.
                        </h6>
                        <div v-if="loadingListar" class="text-center m-t-40">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="text-muted m-t-10">Cargando tipos de documentos...</p>
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
                                        <th>Código</th>
                                        <th width="150px">Tipo de Documento</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.codigo }}</td>
                                        <td>{{ item.tipo_documento }}</td>
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
                                                @click="
                                                    prepararEdicion(item);
                                                    abrirSidebar();
                                                "
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="
                                                    eliminarRegistro(item.id, item.tipo_documento)
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

        <div
            class="right-sidebar"
            ref="sidebarRef"
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
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Código</label>
                                        <input
                                            type="text"
                                            id="codigo"
                                            name="codigo"
                                            v-model="registro.codigo"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.hasError('codigo') }"
                                            placeholder="Código"
                                        />
                                        <span
                                            class="text-danger"
                                            v-if="errores.hasError('codigo')"
                                            >{{ errores.getError("codigo") }}</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Tipo de documento</label>
                                        <input
                                            type="text"
                                            id="tipo_documento"
                                            name="tipo_documento"
                                            v-model="registro.tipo_documento"
                                            class="form-control"
                                            :class="{
                                                'is-invalid': errores.hasError('tipo_documento'),
                                            }"
                                            placeholder="Ingrese aquí el nombre del tipo de documento"
                                        />
                                        <span
                                            class="text-danger"
                                            v-if="errores.hasError('tipo_documento')"
                                            >{{ errores.getError("tipo_documento") }}</span
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
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const dt = ref(null);
const sidebarRef = ref(null);

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { confirmAction, toastMessage } from "@/helpers/alerts.js";
import axios from "axios";

const id = ref(0);
const registros = ref([]);
const registro = ref({ codigo: "", tipo_documento: "", estado: true });
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

const errores = ref({});

function clearErrors() {
    errores.value = {};
}

function setErrors(newErrors) {
    errores.value = newErrors || {};
}

function hasError(field) {
    return !!errores.value[field];
}

function getError(field) {
    return errores.value[field]?.[0];
}

async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    clearErrors();
    try {
        const res = await axios.get("/api/config-tipos-documentos");
        safeDestroyDataTable(dt.value || "#example23");
        registros.value = res.data.data;
        nextTick(() => {
            safeInitDataTable(dt.value || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
        registros.value = [];
    } finally {
        loadingListar.value = false;
    }
}

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    clearErrors();
    loadingGuardar.value = true;
    try {
        let res;
        if (actualizar.value === false) {
            res = await axios.post("/api/config-tipos-documentos", registro.value);
        } else {
            res = await axios.put("/api/config-tipos-documentos/" + id.value, registro.value);
        }

        if (res.status >= 200 && res.status < 300) {
            ListarDatos();
            toastMessage({
                heading: "Ok!!!",
                text: actualizar.value
                    ? "Tipo de documento actualizado correctamente."
                    : "Tipo de documento guardado correctamente.",
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
                text:
                    error.response?.data?.message || "No fue posible guardar el tipo de documento.",
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

async function eliminarRegistro(idEliminar, nom) {
    const ok = await confirmAction({
        title: `¿Estás seguro de eliminar el tipo de documento "${nom}"?`,
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;
    if (loadingEliminarId.value) return;
    loadingEliminarId.value = idEliminar;
    try {
        const response = await axios.delete("/api/config-tipos-documentos/" + idEliminar);
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

function prepararEdicion(item) {
    actualizar.value = true;
    tituloModal.value = "Actualizar el registro: " + (item.tipo_documento || "");
    id.value = item.id;
    registro.value.codigo = item.codigo;
    registro.value.tipo_documento = item.tipo_documento;
    registro.value.estado = Boolean(item.estado);
    clearErrors();
    abrirSidebar();
}

function prepararNuevo() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    registro.value.codigo = "";
    registro.value.tipo_documento = "";
    registro.value.estado = true;
    clearErrors();
    abrirSidebar();
}

function limpiar() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    registro.value.codigo = "";
    registro.value.tipo_documento = "";
    registro.value.estado = true;
    clearErrors();
}

function abrirSidebar() {
    nextTick(() => {
        if (sidebarRef.value) {
            sidebarRef.value.classList.add("shw-rside");
        }
    });
}

function cerrarSidebar() {
    if (sidebarRef.value) {
        sidebarRef.value.classList.remove("shw-rside");
    }
}

onMounted(() => {
    ListarDatos();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#example23");
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -50%;
    width: 50%;
    display: none;
    z-index: 1100;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 100%;
    -webkit-box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    -webkit-transition: all 0.3s ease;
    -o-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

.shw-rside {
    right: 0px;
    width: 50%;
    display: block;
}
</style>
