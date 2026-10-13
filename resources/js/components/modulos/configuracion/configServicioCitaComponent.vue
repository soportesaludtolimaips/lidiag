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
                    <li class="breadcrumb-item active">Servicios de Citas</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="abrirFormularioNuevo()"
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de servicios de citas</h4>
                        <h6 class="card-subtitle">
                            Administración de servicios disponibles para agendar citas.
                        </h6>

                        <div class="row m-t-20 m-b-20">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Filtrar por sede</label>
                                    <select
                                        class="form-control"
                                        v-model="filtroSede"
                                        @change="ListarDatos"
                                    >
                                        <option value="">Todas las sedes</option>
                                        <option v-for="s in sedes" :key="s.id" :value="s.id">
                                            {{ s.nom_sede }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div v-if="loadingListar" class="text-center m-t-40">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="text-muted m-t-10">Cargando servicios...</p>
                        </div>
                        <div v-else class="table-responsive m-t-40">
                            <table
                                id="tablaServicios"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Servicio</th>
                                        <th>Sede</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.nombre }}</td>
                                        <td>{{ item.sede?.nom_sede || "—" }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado"
                                                >Activo</span
                                            >
                                            <span class="label label-info" v-if="!item.estado"
                                                >Inactivo</span
                                            >
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="abrirFormularioEditar(item)"
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="eliminarRegistro(item.id)"
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

        <div class="right-sidebar" ref="sidebarRef">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" @click="cerrarSidebar"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form @submit.prevent="guardarRegistro">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Servicio</label>
                                        <input
                                            type="text"
                                            v-model="form.nombre"
                                            class="form-control"
                                            placeholder="Ingrese el nombre del servicio"
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
                                        <label class="control-label">Sede</label>
                                        <select class="form-control" v-model="form.sede_id">
                                            <option value="">Seleccione una sede</option>
                                            <option v-for="s in sedes" :key="s.id" :value="s.id">
                                                {{ s.nom_sede }}
                                            </option>
                                        </select>
                                        <span class="text-danger" v-if="errores.sede_id">{{
                                            errores.sede_id[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox checkbox-success">
                                        <input
                                            type="checkbox"
                                            id="estado_servicio"
                                            v-model="form.estado"
                                            :true-value="1"
                                            :false-value="0"
                                        />
                                        <label for="estado_servicio"> Activo </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="submit"
                                    class="btn btn-success m-r-5"
                                    v-if="!editando"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    Guardar
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-warning m-r-5"
                                    v-if="editando"
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
import { alertSuccess, confirmAction, toastMessage } from "@/helpers/alerts.js";
import axios from "axios";

const registros = ref([]);
const sedes = ref([]);
const filtroSede = ref("");
const form = ref({ nombre: "", sede_id: "", estado: 1 });
const tituloModal = ref("Nuevo registro");
const editando = ref(false);
const errores = ref({});
const loadingListar = ref(false);
const loadingSedes = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

async function cargarSedes() {
    if (loadingSedes.value) return;
    loadingSedes.value = true;
    try {
        const res = await axios.get("/api/config-sedes");
        sedes.value = res.data.data;
    } catch {
        toastMessage({
            heading: "Error",
            text: "Error al cargar las sedes.",
            icon: "error",
        });
    } finally {
        loadingSedes.value = false;
    }
}

async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        const params = {};
        if (filtroSede.value) params.sede_id = filtroSede.value;
        const res = await axios.get("/api/config-servicio-citas", { params });
        safeDestroyDataTable(dt.value || "#tablaServicios");
        registros.value = res.data.data;
        nextTick(() => {
            safeInitDataTable(dt.value || "#tablaServicios", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch {
        registros.value = [];
    } finally {
        loadingListar.value = false;
    }
}

function abrirFormularioNuevo() {
    editando.value = false;
    tituloModal.value = "Nuevo registro";
    form.value = { nombre: "", sede_id: "", estado: 1 };
    errores.value = {};
    abrirSidebar();
}

function abrirFormularioEditar(item) {
    editando.value = true;
    tituloModal.value = "Actualizar: " + item.nombre;
    form.value = {
        id: item.id,
        nombre: item.nombre,
        sede_id: item.sede_id,
        estado: item.estado ? 1 : 0,
    };
    errores.value = {};
    abrirSidebar();
}

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (editando.value) {
            await axios.put("/api/config-servicio-citas/" + form.value.id, form.value);
            alertSuccess("Ok!", "Servicio actualizado correctamente.");
        } else {
            await axios.post("/api/config-servicio-citas", form.value);
            alertSuccess("Ok!", "Servicio creado correctamente.");
        }
        ListarDatos();
        cerrarSidebar();
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
    } finally {
        loadingGuardar.value = false;
    }
}

async function eliminarRegistro(id) {
    const ok = await confirmAction({
        title: "¿Estas seguro?",
        text: "No podras recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;
    try {
        loadingEliminarId.value = id;
        const response = await axios.delete("/api/config-servicio-citas/" + id);
        alertSuccess("Ok!", response?.data?.message || "Registro eliminado.");
        ListarDatos();
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "Error al eliminar el registro.",
            icon: "error",
        });
    } finally {
        loadingEliminarId.value = null;
    }
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
    cargarSedes();
    ListarDatos();
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#tablaServicios");
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
