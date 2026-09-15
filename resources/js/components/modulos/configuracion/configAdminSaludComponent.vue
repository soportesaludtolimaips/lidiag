<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
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
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="
                        actualizar = false;
                        mostrarRegistro();
                    "
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
            <!-- <div class="">
                    <button class="right-side-toggle btn-info btn btn-circle btn-sm">
                        <i class="fa fa-plus-circle m-r-5"></i>
                    </button>
                </div> -->
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
                        <h4 class="card-title">Listado de administradoras de salud</h4>
                        <h6 class="card-subtitle">
                            Administración de las administradoras de salud "EPS".
                        </h6>
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
                                                    elimnarRegistro(item.id, item.nom_admin_salud)
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
        <!-- ============================================================== -->
        <!-- End PAge Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
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
                                            placeholder="Ingrese aquí el la razón sicual de la administradora de salud"
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

const dt = ref(null);

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertError, alertSuccess, confirmAction, toastMessage } from "@/helpers/alerts.js";
import axios from "axios";

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

/**
 * Obtiene la lista de administradoras de salud desde el servidor y la carga en la tabla.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        const res = await axios.get("/api/config-admin-salud");

        safeDestroyDataTable(dt.value || "#example23");
        registros.value = res.data.data;
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
/**
 * Crea o actualiza una administradora de salud según el estado de edición del formulario.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (actualizar.value === false) {
            const res = await axios.post("/api/config-admin-salud", registro.value);

            if (res.status == 200) {
                ListarDatos();

                toastMessage({
                    heading: "Ok!!!",
                    text: res.data.data.message,
                    icon: "success",
                });
            }
        } else {
            const res = await axios.put("/api/config-admin-salud/" + id.value, registro.value);
            if (res.status == 200) {
                ListarDatos();

                toastMessage({
                    heading: "Ok!!!",
                    text: res.data.data.message,
                    icon: "success",
                });
            }
        }
    } catch (error) {
        errores.value = error?.response?.data?.errors || {};
    } finally {
        loadingGuardar.value = false;
    }
}
/**
 * Elimina una administradora de salud previa confirmación del usuario.
 *
 * @param {number} id Identificador de la administradora de salud a eliminar.
 * @param {string} nom Nombre de la administradora de salud para el mensaje de confirmación.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
async function elimnarRegistro(id, nom) {
    const ok = await confirmAction({
        title: `Estas seguro de eliminar la admninistradora de salud ${nom}?`,
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;

    try {
        if (loadingEliminarId.value) return;
        loadingEliminarId.value = id;
        const response = await axios.delete("/api/config-admin-salud/" + id);
        alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
        ListarDatos();
    } catch (error) {
        alertError("Error", "No fue posible eliminar el registro.", "error");
    } finally {
        loadingEliminarId.value = null;
    }
}
/**
 * Prepara el formulario lateral para crear o editar una administradora de salud.
 *
 * @param {Object} data Datos de la administradora de salud a editar (vacío para un nuevo registro).
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function mostrarRegistro(data = {}) {
    if (actualizar.value) {
        tituloModal.value = "Actualizar el registro: " + data.nom_admin_salud;
        id.value = data.id;
        registro.value.cod_admin_salud = data.cod_admin_salud;
        registro.value.nit_admin_salud = data.nit_admin_salud;
        registro.value.nom_admin_salud = data.nom_admin_salud;
        registro.value.estado = Boolean(data.estado);
    } else {
        actualizar.value = false;
        tituloModal.value = "Nuevo registro";
        id.value = 0;
        registro.value.cod_admin_salud = "";
        registro.value.nit_admin_salud = "";
        registro.value.nom_admin_salud = "";
        registro.value.estado = true;
    }

    btnCerralModalForm();
}
/**
 * Muestra u oculta el panel lateral del formulario mediante la clase correspondiente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
function btnCerralModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").toggleClass("shw-rside");
}

/**
 * Hook de ciclo de vida que carga los datos al montar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
onMounted(() => {
    ListarDatos();
});

/**
 * Hook de ciclo de vida que destruye la tabla de datos al desmontar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#example23");
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -25%;
    width: 25%;
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
    width: 25%;
    display: block;
}
</style>
