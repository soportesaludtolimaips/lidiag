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
                        <div v-if="loadingListar" class="text-center p-3">
                            <i class="fa fa-spinner fa-spin"></i> Cargando...
                        </div>
                        <div class="table-responsive m-t-40">
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

                                            <button
                                                type="button"
                                                title="Eliminar rol"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-l-5"
                                                @click="eliminarRol(item)"
                                            >
                                                <i class="fa fa-trash"></i>
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
                                                v-for="(permisosGrupo, modulo) in permisosAgrupados"
                                                :key="modulo"
                                                class="mb-3"
                                            >
                                                <div
                                                    class="d-flex align-items-center justify-content-between p-2 rounded"
                                                    style="background: #f0f2f5; cursor: pointer"
                                                    @click="toggleGrupo(modulo)"
                                                >
                                                    <div class="d-flex align-items-center">
                                                        <i
                                                            class="fa m-r-5"
                                                            :class="
                                                                gruposAbiertos[modulo]
                                                                    ? 'fa-chevron-down'
                                                                    : 'fa-chevron-right'
                                                            "
                                                        ></i>
                                                        <strong>{{
                                                            modulosTraducidos[modulo] || modulo
                                                        }}</strong>
                                                        <span class="badge badge-secondary ml-2">{{
                                                            permisosGrupo.length
                                                        }}</span>
                                                    </div>
                                                    <div @click.stop>
                                                        <small
                                                            class="text-info mr-2"
                                                            role="button"
                                                            @click="seleccionarGrupo(modulo, true)"
                                                            >Todos</small
                                                        >
                                                        <small
                                                            class="text-muted"
                                                            role="button"
                                                            @click="seleccionarGrupo(modulo, false)"
                                                            >Ninguno</small
                                                        >
                                                    </div>
                                                </div>
                                                <div v-show="gruposAbiertos[modulo]" class="pt-2">
                                                    <div class="row">
                                                        <div
                                                            v-for="itemPermiso in permisosGrupo"
                                                            :key="itemPermiso.id"
                                                            class="col-md-6 col-lg-4 mb-1"
                                                        >
                                                            <div
                                                                class="custom-control custom-checkbox"
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    class="custom-control-input"
                                                                    :id="'perm_' + itemPermiso.id"
                                                                    v-model="itemPermiso.checked"
                                                                />
                                                                <label
                                                                    class="custom-control-label"
                                                                    :for="'perm_' + itemPermiso.id"
                                                                >
                                                                    {{ itemPermiso.name }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const dt = ref(null);

import { confirmAction } from "@/helpers/alerts";
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
const gruposAbiertos = ref({});

const permisosAgrupados = computed(() => {
    const grupos = {};
    registro.value.permisosRol.forEach((permiso) => {
        const partes = permiso.name.split(".");
        const modulo = partes.length > 1 ? partes[0] : "Otros";
        if (!grupos[modulo]) {
            grupos[modulo] = [];
        }
        grupos[modulo].push(permiso);
    });
    return grupos;
});

const modulosTraducidos = {
    seguridad: "Seguridad",
    config: "Configuración",
    estudio: "Estudios",
    estudios: "Estudios",
    reportes: "Reportes",
    agenda: "Agenda",
    agendamiento: "Agendamiento",
    ecografias: "Ecografías",
    consentimientos: "Consentimientos",
    citas: "Citas",
    helpdesk: "Help Desk",
    otrasPlataformas: "Otras Plataformas",
    tele: "Tele-Radiología",
    gestion: "Gestión",
    Otros: "Otros",
};

function toggleGrupo(modulo) {
    gruposAbiertos.value[modulo] = !gruposAbiertos.value[modulo];
}

function seleccionarGrupo(modulo, seleccionar) {
    permisosAgrupados.value[modulo].forEach((permiso) => {
        permiso.checked = seleccionar;
    });
}

function initDataTable() {
    safeInitDataTable(dt.value || "#example23", {
        dom: "Bfrtip",
        buttons: ["copy", "csv", "excel", "pdf", "print"],
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, "asc"]],
        language: {
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron roles",
            info: "Mostrando _START_ a _END_ de _TOTAL_ roles",
            infoEmpty: "No hay roles disponibles",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior",
            },
        },
    });
}
function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#example23");
}
async function ListarDatos() {
    loadingListar.value = true;
    try {
        destroyDataTable();

        const res = await axios.get("/api/seguridad-roles");

        listarRoles.value = res.data.data.roles;
        permisos.value = res.data.data.permissions;

        registro.value.permisosRol = [];
        listarPermisosRol();

        await nextTick();
        initDataTable();
    } catch (error) {
        console.error("Error al listar roles:", error);
        if (error?.response?.status === 401) {
            window.location.href = "/login";
        }
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
                    text: res.data.data.message,
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
                    text: res.data.data.message,
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
    } finally {
        loadingGuardar.value = false;
    }
}
function listarPermisosRol() {
    permisos.value.forEach(function (x) {
        registro.value.permisosRol.push({
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
                const permisosDelRol = Object.keys(res.data.data.permisosRol);

                permisos.value.forEach(function (permiso, index) {
                    registro.value.permisosRol[index].checked = false;
                });

                permisosDelRol.forEach(function (permisoDelRol) {
                    let permisoActivo = permisos.value.findIndex((x) => x.id == permisoDelRol);
                    registro.value.permisosRol[permisoActivo].checked = true;
                });
            })
            .catch((error) => {
                console.error("Error al cargar permisos del rol:", error);
                if (error?.response?.status === 401) {
                    window.location.href = "/login";
                }
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
        console.error("Error al cambiar estado:", error);
        const msg = error?.response?.data?.message || "Error al cambiar estado del rol";
        $.toast({
            heading: "Error",
            text: msg,
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "error",
            hideAfter: 5000,
            stack: 6,
        });
    }
}
async function eliminarRol(data = {}) {
    const ok = await confirmAction({
        title: "Confirmación",
        text: "¿Estás seguro de que deseas eliminar el rol " + data.name + "?",
        icon: "warning",
    });
    if (!ok) return;
    try {
        const res = await axios.delete("/api/seguridad-roles/" + data.id);
        $.toast({
            heading: "Ok!!!",
            text: res.data.data.message,
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "success",
            hideAfter: 3500,
            stack: 6,
        });
        ListarDatos();
    } catch (error) {
        const msg = error?.response?.data?.message || "Error al eliminar el rol";
        $.toast({
            heading: "Error",
            text: msg,
            position: "top-right",
            loaderBg: "#ff6849",
            icon: "error",
            hideAfter: 5000,
            stack: 6,
        });
    }
}
function btnCerralModalForm() {
    if ($(".right-sidebar").hasClass("shw-rside")) {
        $(".right-sidebar").removeClass("shw-rside");
    } else {
        $(".right-sidebar").addClass("shw-rside");
    }
}
function limpiarFormulario() {
    actualizar.value = false;
    tituloModal.value = "Nuevo registro";
    id.value = 0;
    registro.value.name = "";
    errores.value = "";

    permisos.value.forEach(function (permiso, index) {
        registro.value.permisosRol[index].checked = false;
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
