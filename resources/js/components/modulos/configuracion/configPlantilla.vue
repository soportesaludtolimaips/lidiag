<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
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
                    <li class="breadcrumb-item active">Plantillas</li>
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
                        <h4 class="card-title">Listado de plantillas</h4>
                        <h6 class="card-subtitle">AdministraciÃ³n de las tipos de plantillas.</h6>
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
                                        <th width="150px">Tipo de Plantilla</th>
                                        <th>Plantilla</th>
                                        <th>Medico</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.plantilla_tipo.nom_tipo }}</td>
                                        <td>{{ item.nom_plantilla }}</td>
                                        <td>{{ item.medico.name }}</td>
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
                                                @click="
                                                    elimnarRegistro(item.id, item.nom_plantilla)
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
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Nombre de la plantilla</label>
                                        <input
                                            type="text"
                                            id="nom_plantilla"
                                            name="nom_plantilla"
                                            v-model="registro.nom_plantilla"
                                            class="form-control"
                                            placeholder="Nombre de la plantilla"
                                        />
                                        <span class="text-danger" v-if="errores.nom_plantilla">{{
                                            errores.nom_plantilla[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Medico</label>
                                        <select
                                            class="form-control custom-select"
                                            style="width: 100%; height: 36px"
                                            v-model="registro.medico_id"
                                            name="medico_id"
                                        >
                                            <option>Seleccione el mÃ©dico</option>
                                            <option
                                                v-for="ItemMedico in medicos"
                                                :key="ItemMedico"
                                                :value="ItemMedico.id"
                                            >
                                                {{ ItemMedico.name }}
                                            </option>
                                            <span
                                                class="text-danger"
                                                v-if="errores.nom_plantilla"
                                                >{{ errores.medico_id[0] }}</span
                                            >
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Tipo de plantilla</label>
                                        <select
                                            class="form-control custom-select"
                                            style="width: 100%; height: 36px"
                                            v-model="registro.plantilla_tipo_id"
                                            name="plantilla_tipo_id"
                                        >
                                            <option>Seleccione el mÃ©dico</option>
                                            <option
                                                v-for="ItemTipoPlantilla in tiposPlantillas"
                                                :key="ItemTipoPlantilla"
                                                :value="ItemTipoPlantilla.id"
                                            >
                                                {{ ItemTipoPlantilla.nom_tipo }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Plantillas</label>
                                        <textarea
                                            id="plantilla"
                                            name="plantilla"
                                            v-model="registro.plantilla"
                                            class="form-control"
                                            placeholder="DescripciÃ³n de la plantilla"
                                            rows="9"
                                        ></textarea>
                                        <span class="text-danger" v-if="errores.plantilla">{{
                                            errores.plantilla[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Opinion</label>
                                        <textarea
                                            id="opinion"
                                            name="opinion"
                                            v-model="registro.opinion"
                                            class="form-control"
                                            placeholder="Opinion"
                                            rows="5"
                                        ></textarea>
                                        <span class="text-danger" v-if="errores.opinion">{{
                                            errores.opinion[0]
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
                                    @click="guardarRegistro()"
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
import {
    alertError,
    alertSuccess,
    confirmAction,
    handleAxiosErrorToast,
} from "@/helpers/alerts.js";
import axios from "axios";

const id = ref(0);
const registros = ref([]);
const registro = ref({
    plantilla_tipo_id: "",
    medico_id: "",
    nom_plantilla: "",
    plantilla: "",
    opinion: "",
    estado: true,
});
const tituloModal = ref("Nuevo registro");
const actualizar = ref(false);
const errores = ref({});
const medicos = ref([]);
const tiposPlantillas = ref([]);
const loadingListar = ref(false);
const loadingGuardar = ref(false);
const loadingEliminarId = ref(null);

async function ListarDatos() {
    if (loadingListar.value) return;
    loadingListar.value = true;
    try {
        const res = await axios.get("/api/config-plantillas");
        safeDestroyDataTable(dt.value || "#example23");
        registros.value = res.data.data;
        nextTick(() => {
            safeInitDataTable(dt.value || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar plantillas.");
        registros.value = [];
    } finally {
        loadingListar.value = false;
    }
}
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (actualizar.value === false) {
            const res = await axios.post("/api/config-plantillas", registro.value);

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
            const res = await axios.put("/api/config-plantillas/" + id.value, registro.value);
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
        errores.value = error?.response?.data?.errors || {};
        handleAxiosErrorToast(error, "No fue posible guardar la plantilla.");
    } finally {
        loadingGuardar.value = false;
    }
}
async function elimnarRegistro(id, nom) {
    const ok = await confirmAction({
        title: `Estas seguro de eliminar el tipo de plantillas ${nom}?`,
        text: "No podrÃ¡s recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;

    try {
        if (loadingEliminarId.value) return;
        loadingEliminarId.value = id;
        const response = await axios.delete("/api/config-plantillas/" + id);
        alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
        ListarDatos();
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible eliminar el registro.");
        alertError("Error", "No fue posible eliminar el registro.", "error");
    } finally {
        loadingEliminarId.value = null;
    }
}
async function listarMedicos() {
    try {
        const pathname = String(window?.location?.pathname || "").toLowerCase();
        const moduloId =
            sessionStorage.getItem("ST-modulo") || (pathname.includes("ecografia") ? "2" : "1");
        const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
        medicos.value = res.data.data;
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar mÃ©dicos.");
    }
}
async function listarTiposPlantillas() {
    try {
        const res = await axios.get("/api/config-tipos-plantillas/listar/activos");
        tiposPlantillas.value = res.data.data;
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar tipos de plantilla.");
    }
}
function mostrarRegistro(data = {}) {
    if (actualizar.value == true) {
        tituloModal.value = "Actualizar el registro: " + data.nom_plantilla;
        id.value = data.id;
        registro.value.plantilla_tipo_id = data.plantilla_tipo_id;
        registro.value.medico_id = data.medico_id;
        registro.value.nom_plantilla = data.nom_plantilla;
        registro.value.plantilla = data.plantilla;
        registro.value.opinion = data.opinion;
        registro.value.estado = Boolean(data.estado);
    } else {
        actualizar.value = false;
        tituloModal.value = "Nuevo registro";
        id.value = 0;
        registro.value.plantilla_tipo_id = "";
        registro.value.medico_id = "";
        registro.value.nom_plantilla = "";
        registro.value.plantilla = "";
        registro.value.opinion = "";
        registro.value.estado = true;
    }

    btnCerralModalForm();
}
function btnCerralModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").toggleClass("shw-rside");
}
function limpiarFormulario() {
    errores.value = {};
    registro.value.plantilla_tipo_id = "";
    registro.value.medico_id = "";
    registro.value.nom_plantilla = "";
    registro.value.plantilla = "";
    registro.value.opinion = "";
    registro.value.estado = 1;
}

onMounted(() => {
    ListarDatos();
    (listarMedicos(), listarTiposPlantillas());
});

onBeforeUnmount(() => {
    safeDestroyDataTable(dt.value || "#example23");
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
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
    width: 80%;
    display: block;
}
</style>
