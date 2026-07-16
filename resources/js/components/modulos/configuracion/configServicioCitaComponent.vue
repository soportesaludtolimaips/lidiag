<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">ConfiguraciÃ³n</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">ConfiguraciÃ³n</a>
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
                            AdministraciÃ³n de servicios disponibles para agendar citas.
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

                        <div class="table-responsive m-t-40">
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
                                        <td>{{ item.sede?.nom_sede || "â€”" }}</td>
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

        <div class="right-sidebar">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" id="btnCerrarModal"></i>
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
                                <button type="button" class="btn btn-inverse" @click="cerrarModal">
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

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import {
    alertError,
    alertSuccess,
    confirmAction,
    handleAxiosErrorToast,
} from "@/helpers/alerts.js";
import axios from "axios";

const registros = ref([]);
const sedes = ref([]);
const filtroSede = ref("");
const form = ref({ nombre: "", sede_id: "", estado: 1 });
const tituloModal = ref("Nuevo registro");
const editando = ref(false);
const errores = ref({});
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

async function cargarSedes() {
    try {
        const res = await axios.get("/api/config-sedes");
        sedes.value = res.data;
    } catch (e) {
        handleAxiosErrorToast(e, "No fue posible cargar sedes.");
    }
}
async function ListarDatos() {
    try {
        const params = {};
        if (filtroSede.value) params.sede_id = filtroSede.value;
        const res = await axios.get("/api/config-servicio-citas", { params });
        safeDestroyDataTable(dt.value || "#tablaServicios");
        registros.value = res.data;
        nextTick(() => {
            safeInitDataTable(dt.value || "#tablaServicios", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar servicios.");
        registros.value = [];
    }
}
function abrirFormularioNuevo() {
    editando.value = false;
    tituloModal.value = "Nuevo registro";
    form.value = { nombre: "", sede_id: "", estado: 1 };
    errores.value = {};
    $("#btnCerrarModal").click();
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
    $("#btnCerrarModal").click();
}
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (editando.value) {
            const res = await axios.put("/api/config-servicio-citas/" + form.value.id, form.value);
            alertSuccess("Ok!", "Servicio actualizado correctamente.");
        } else {
            const res = await axios.post("/api/config-servicio-citas", form.value);
            alertSuccess("Ok!", "Servicio creado correctamente.");
        }
        ListarDatos();
        cerrarModal();
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
        handleAxiosErrorToast(error, "No fue posible guardar el servicio.");
    } finally {
        loadingGuardar.value = false;
    }
}
async function eliminarRegistro(id) {
    const ok = await confirmAction({
        title: "Â¿Estas seguro?",
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
        handleAxiosErrorToast(error, "No fue posible eliminar el registro.");
    } finally {
        loadingEliminarId.value = null;
    }
}
function cerrarModal() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").toggleClass("shw-rside");
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
