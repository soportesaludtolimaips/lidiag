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
                    <li class="breadcrumb-item active">Administradores de Salud</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-action-clinical m-r-5"
                    @click="ListarDatos"
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
                    @click="
                        actualizar = false;
                        mostrarRegistro();
                    "
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de administradoras de salud</h4>
                        <h6 class="card-subtitle">
                            Administración de las administradoras de salud "EPS".
                        </h6>

                        <div v-if="loadingListar" class="text-center p-4">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Cargando datos...</p>
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
                                        <th width="150px">Razón Social</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.cod_admin_salud }}</td>
                                        <td>{{ item.nom_admin_salud }}</td>
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
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                            >
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                :disabled="loadingEliminarId === item.id"
                                                @click="
                                                    eliminarRegistro(item.id, item.nom_admin_salud)
                                                "
                                            >
                                                <i
                                                    v-if="loadingEliminarId === item.id"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-trash"></i>
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
        <div v-if="sidebarVisible" class="sidebar-backdrop" @click="btnCerrarModalForm()"></div>

        <div class="right-sidebar" :class="{ 'shw-rside': sidebarVisible }" ref="sidebarRef">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" @click="btnCerrarModalForm()"></i>
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
                                            id="cod_admin_salud"
                                            name="cod_admin_salud"
                                            v-model="registro.cod_admin_salud"
                                            class="form-control"
                                            placeholder="Código"
                                        />
                                        <span class="text-danger" v-if="errores.cod_admin_salud">{{
                                            errores.cod_admin_salud[0]
                                        }}</span>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Nit</label>
                                        <input
                                            type="text"
                                            id="nit_admin_salud"
                                            name="nit_admin_salud"
                                            v-model="registro.nit_admin_salud"
                                            class="form-control"
                                            placeholder="Nit"
                                        />
                                        <span class="text-danger" v-if="errores.nit_admin_salud">{{
                                            errores.nit_admin_salud[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Razón Social</label>
                                        <input
                                            type="text"
                                            id="nom_admin_salud"
                                            name="nom_admin_salud"
                                            v-model="registro.nom_admin_salud"
                                            class="form-control"
                                            placeholder="Ingrese la razón social de la administradora de salud"
                                        />
                                        <span class="text-danger" v-if="errores.nom_admin_salud">{{
                                            errores.nom_admin_salud[0]
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
                                    class="btn btn-success m-r-5 btn-action-clinical"
                                    @click="guardarRegistro()"
                                    v-if="!actualizar"
                                    :disabled="loadingGuardar"
                                    title="Guardar administradora (Ctrl+S)"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i> Guardar
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
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                    title="Actualizar administradora (Ctrl+S)"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-pencil"></i> Actualizar
                                    <span
                                        class="badge badge-light text-dark ml-1 d-none d-md-inline"
                                        style="font-size: 10px; opacity: 0.9"
                                        >Ctrl+S</span
                                    >
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse btn-action-clinical"
                                    @click="btnCerrarModalForm()"
                                    title="Cerrar panel (Esc)"
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

const sidebarVisible = ref(false);
const dt = ref(null);
const sidebarRef = ref(null);
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { confirmAction, toastMessage } from "@/helpers/alerts.js";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import axios from "axios";

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
    onEscape: btnCerrarModalForm,
    isActive: () => true,
    isLoading: loadingGuardar,
});

const id = ref(0);
const registros = ref([]);
const registro = ref({
    cod_admin_salud: "",
    nit_admin_salud: "",
    nom_admin_salud: "",
    estado: true,
});
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const errores = ref({});
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        const res = await axios.get("/api/config-admin-salud");

        safeDestroyDataTable(dt.value || "#example23");
        registros.value = res.data?.data || res.data || [];
        nextTick(() => {
            safeInitDataTable(dt.value || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible cargar los datos.",
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
        let res;
        if (actualizar.value === false) {
            res = await axios.post("/api/config-admin-salud", registro.value);
        } else {
            res = await axios.put("/api/config-admin-salud/" + id.value, registro.value);
        }

        if (res.status >= 200 && res.status < 300) {
            toastMessage({
                heading: "Ok!!!",
                text: res.data?.data?.message || res.data?.message || "Operación exitosa.",
                icon: "success",
            });
            btnCerrarModalForm();
            ListarDatos();
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errores.value = error.response.data.errors || {};
        } else {
            toastMessage({
                heading: "Error",
                text: error.response?.data?.message || "No fue posible guardar el registro.",
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

async function eliminarRegistro(idRegistro, nom) {
    const ok = await confirmAction({
        title: `¿Estás seguro de eliminar la administradora de salud ${nom}?`,
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;

    if (loadingEliminarId.value) return;
    loadingEliminarId.value = idRegistro;
    try {
        const response = await axios.delete("/api/config-admin-salud/" + idRegistro);
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

function mostrarRegistro(data = {}) {
    errores.value = {};

    if (actualizar.value) {
        tituloModal.value = "Actualizar el registro: " + data.nom_admin_salud;
        id.value = data.id;
        registro.value.cod_admin_salud = data.cod_admin_salud;
        registro.value.nit_admin_salud = data.nit_admin_salud;
        registro.value.nom_admin_salud = data.nom_admin_salud;
        registro.value.estado = data.estado;
    } else {
        actualizar.value = false;
        tituloModal.value = "Nuevo registro";
        id.value = 0;
        registro.value = {
            cod_admin_salud: "",
            nit_admin_salud: "",
            nom_admin_salud: "",
            estado: true,
        };
    }

    btnAbrirModalForm();
}

function btnAbrirModalForm() {
    sidebarVisible.value = true;
    nextTick(() => {
        document.getElementById("cod_admin_salud")?.focus();
    });
}

function btnCerrarModalForm() {
    sidebarVisible.value = false;
}

onMounted(() => {
    ListarDatos();
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

@media (max-width: 768px) {
    .right-sidebar {
        width: 100% !important;
        right: -100% !important;
    }
}
</style>
