<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Seguridad</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a role="button" tabindex="0">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a role="button" tabindex="0">Seguridad</a>
                    </li>
                    <li class="breadcrumb-item active">Gestión de Roles</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
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
                        <h4 class="card-title">Listado de roles</h4>
                        <div v-if="loadingListar" class="text-center p-5">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p class="m-t-10">Cargando roles...</p>
                        </div>
                        <div class="table-responsive m-t-40" v-else>
                            <table
                                id="example23"
                                ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Rol</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!listarRoles.length">
                                        <td colspan="2" class="text-center text-muted">
                                            No se encontraron roles registrados.
                                        </td>
                                    </tr>
                                    <tr v-for="item in listarRoles" :key="item.id">
                                        <td>{{ item.name }}</td>
                                        <td class="text-nowrap">
                                            <!-- <button class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5">
                                                <i class="fa fa-eye"></i>
                                            </button> -->

                                            <button
                                                type="button"
                                                title="Editar rol"
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
                                                title="Desactivar rol"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm"
                                                @click="cambiarEstado(1, item)"
                                                v-if="item.estado == 1"
                                            >
                                                <i class="fa fa-unlock"></i>
                                            </button>

                                            <button
                                                type="button"
                                                title="Activar rol"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="cambiarEstado(0, item)"
                                                v-if="item.estado == 0"
                                            >
                                                <i class="fa fa-lock"></i>
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
        <!-- End PAge Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar" style="overflow-y: auto; height: 100%">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="close"
                        title="Cerrar"
                        aria-label="Cerrar panel"
                        @click="btnCerralModalForm()"
                    >
                        <i class="ti-close right-side-toggle"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Nombre del Rol</label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            v-model="registro.name"
                                            class="form-control"
                                            placeholder="Ingrese aqui el nombre del rol"
                                        />
                                        <span class="text-danger" v-if="errores">{{
                                            errores
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <p class="text-info"><i class="fa fa-unlock"></i> Permisos</p>
                            <span class="text-danger" v-if="erroresRpermisosElegidos">{{
                                erroresRpermisosElegidos
                            }}</span>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="controls">
                                            <div
                                                v-for="itemPermiso in registro.permisosRol"
                                                :key="itemPermiso.id"
                                            >
                                                <div class="custom-control custom-checkbox">
                                                    <input
                                                        type="checkbox"
                                                        class="custom-control-input"
                                                        :id="itemPermiso.name"
                                                        v-model="itemPermiso.checked"
                                                    />
                                                    <label
                                                        class="custom-control-label"
                                                        :for="itemPermiso.name"
                                                    >
                                                        {{ itemPermiso.name }}
                                                    </label>
                                                </div>
                                            </div>
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
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i> Guardar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-warning m-r-5"
                                    v-if="actualizar"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-pencil"></i> Actualizar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="btnCerralModalForm()"
                                >
                                    Cancel
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
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";

import { handleAxiosErrorToast, confirmAction } from "@/helpers/alerts";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import axios from "axios";

const id = ref(0);
const listarRoles = ref([]);
const permisos = ref([]);
const registro = ref({ name: "", permisosRol: [] });
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const errores = ref("");
const erroresRpermisosElegidos = ref("");
const loadingListar = ref(false);
const loadingGuardar = ref(false);

function initDataTable() {
    safeInitDataTable(dt.value || "#example23", {
        dom: "Bfrtip",
        buttons: ["copy", "csv", "excel", "pdf", "print"],
        retrieve: true,
    });
}
function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#example23");
}
async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        const res = await axios.get("/api/seguridad-roles");

        destroyDataTable();

        listarRoles.value = [];
        permisos.value = [];
        registro.value.permisosRol = [];

        listarRoles.value = res.data[0];
        permisos.value = res.data[1];

        listarPermisosRol();

        nextTick(() => {
            initDataTable();
        });
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar roles.");
    } finally {
        loadingListar.value = false;
    }
}
async function guardarRegistro() {
    let totalPermisosElegido = 0;
    registro.value.permisosRol.map(function (x, y) {
        if (x.checked == true) {
            totalPermisosElegido++;
        }
    });

    if (totalPermisosElegido == 0) {
        erroresRpermisosElegidos.value = "Debes elegir al menos un permiso para el rol";
        return false;
    }

    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (actualizar.value === false) {
            const res = await axios.post("/api/seguridad-roles", registro.value);

            if (res.status == 200) {
                $.toast({
                    heading: "Ok!!!",
                    text: res.data.message,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });

                ListarDatos();
                limpiarFormulario();
                btnCerralModalForm();
            }
        } else {
            const res = await axios.put("/api/seguridad-roles/" + id.value, {
                registro: registro.value,
            });
            if (res.status == 200) {
                $.toast({
                    heading: "Ok!!!",
                    text: res.data.message,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });

                ListarDatos();
                limpiarFormulario();
                btnCerralModalForm();
            }
        }
    } catch (error) {
        errores.value = error?.response?.data?.message || "";
        handleAxiosErrorToast(error, "No fue posible guardar el rol.");
    } finally {
        loadingGuardar.value = false;
    }
}
function listarPermisosRol() {
    let me = this;

    me.permisos.map(function (x, y) {
        me.registro.permisosRol.push({
            id: x.id,
            name: x.name,
            checked: false,
        });
    });
}
function mostrarRegistro(data = {}) {
    if (actualizar.value == true) {
        tituloModal.value = "Actualizar el registro: " + data.name;
        id.value = data.id;
        registro.value.name = data.name;

        axios
            .get("/api/seguridad-roles/" + id.value)
            .then((res) => {
                registro.value.permisos = [];
                const permisosDelRol = Object.keys(res.data.permisosRol);
                let me = this;

                me.permisos.forEach(function (permisos, index) {
                    me.registro.permisosRol[index].checked = false;
                });

                permisosDelRol.forEach(function (permisosDelRol) {
                    let permisoActivo = me.permisos.findIndex((x) => x.id == permisosDelRol);
                    me.registro.permisosRol[permisoActivo].checked = true;
                });
            })
            .catch((error) => {
                handleAxiosErrorToast(error, "No fue posible cargar los permisos del rol.");
            });
    } else {
        actualizar.value = false;
        tituloModal.value = "Nuevo registro";
        id.value = 0;
        registro.value.name = "";
        registro.value.permisos = [];
    }

    btnCerralModalForm();
}
async function cambiarEstado(estado, data = {}) {
    const ok = await confirmAction({
        title: "Confirmación",
        text:
            "¿Estás seguro de que deseas " +
            (estado == 0 ? "activar" : "desactivar") +
            " el rol " +
            data.name +
            "?",
        icon: "warning",
    });
    if (!ok) return;
    try {
        await axios.put("/api/seguridad-roles/estado/" + data.id, {
            id: data.id,
            estado: estado == 0 ? 1 : 0,
        });
        $.toast({
            heading: "Ok!!!",
            text:
                "El rol " +
                data.name +
                " se " +
                (estado == 0 ? "activó" : "desactivó") +
                " correctamente",
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "success",
            hideAfter: 3500,
            stack: 6,
        });
        ListarDatos();
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible actualizar el estado del rol.");
    }
}
function btnCerralModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").toggleClass("shw-rside");
}
function limpiarFormulario() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    registro.value.name = "";
    errores.value = "";

    let me = this;
    me.permisos.forEach(function (permisos, index) {
        me.registro.permisosRol[index].checked = false;
    });
}

onMounted(() => {
    ListarDatos();
});

onBeforeUnmount(() => {
    destroyDataTable();
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -35%;
    width: 35%;
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
    width: 35%;
    display: block;
}
</style>
