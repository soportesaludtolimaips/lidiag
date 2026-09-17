<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Configuración</a></li>
                    <li class="breadcrumb-item active">Helpdesk - Prioridades</li>
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
                        <h4 class="card-title">Listado de prioridades</h4>
                        <h6 class="card-subtitle">Catálogo de Helpdesk.</h6>
                        <div v-if="loadingListar" class="text-center m-t-40">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="text-muted m-t-10">Cargando prioridades...</p>
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
                                        <th>Orden</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.nombre }}</td>
                                        <td>{{ item.orden }}</td>
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

        <div class="right-sidebar" ref="sidebarRef" style="width: 400px">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span><i class="ti-close right-side-toggle" @click="cerrarSidebar"></i></span>
                </div>
                <div class="r-panel-body">
                    <form action="#" @submit.prevent>
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Nombre</label>
                                        <input
                                            type="text"
                                            v-model="registro.nombre"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.hasError('nombre') }"
                                            maxlength="100"
                                        />
                                        <span
                                            class="text-danger"
                                            v-if="errores.hasError('nombre')"
                                            >{{ errores.getError("nombre") }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Orden</label>
                                        <input
                                            type="number"
                                            v-model="registro.orden"
                                            class="form-control"
                                            :class="{ 'is-invalid': errores.hasError('orden') }"
                                        />
                                        <span
                                            class="text-danger"
                                            v-if="errores.hasError('orden')"
                                            >{{ errores.getError("orden") }}</span
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
                                    v-if="!actualizar"
                                    @click="guardarRegistro"
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
import { toastMessage, confirmAction } from "@/helpers/alerts.js";
import axios from "axios";

const registros = ref([]);
const registro = ref({ id: 0, nombre: "", orden: 0, activo: true });
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

async function listar() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    clearErrors();
    try {
        const res = await axios.get("/api/helpdesk-prioridades");
        safeDestroyDataTable(dt.value || "#example23");
        registros.value = res.data.data || [];
        nextTick(() => {
            safeInitDataTable(dt.value || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible listar las prioridades.",
            icon: "error",
        });
    } finally {
        loadingListar.value = false;
    }
}

function prepararEdicion(item) {
    actualizar.value = true;
    tituloModal.value = "Actualizar registro";
    registro.value = {
        id: item.id,
        nombre: item.nombre,
        orden: item.orden,
        activo: !!item.activo,
    };
    clearErrors();
    abrirSidebar();
}

function prepararNuevo() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    registro.value = { id: 0, nombre: "", orden: 0, activo: true };
    clearErrors();
    abrirSidebar();
}

function limpiar() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    registro.value = { id: 0, nombre: "", orden: 0, activo: true };
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

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    clearErrors();
    try {
        const payload = {
            nombre: registro.value.nombre,
            orden: registro.value.orden,
            activo: registro.value.activo ? 1 : 0,
        };
        if (!actualizar.value) {
            await axios.post("/api/helpdesk-prioridades", payload);
            toastMessage({ heading: "Ok", text: "Prioridad creada.", icon: "success" });
        } else {
            await axios.put(`/api/helpdesk-prioridades/${registro.value.id}`, payload);
            toastMessage({
                heading: "Ok",
                text: "Prioridad actualizada.",
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
                text: "No fue posible guardar la prioridad.",
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
        await axios.delete(`/api/helpdesk-prioridades/${item.id}`);
        toastMessage({ heading: "Ok", text: "Prioridad desactivada.", icon: "success" });
        await listar();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: "No fue posible desactivar la prioridad.",
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
