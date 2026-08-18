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
                        <h6 class="card-subtitle">Administración de las tipos de plantillas.</h6>
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
                                <tbody></tbody>
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
                        <div class="form-body" style="padding-top: 5px">
                            <div class="row">
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
                                            <option>Seleccione el médico</option>
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
                                            <option>Seleccione el médico</option>
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
                                        <WysiwygEditor
                                            v-model="registro.plantilla"
                                            placeholder="Descripción de la plantilla"
                                            :rows="4"
                                        />
                                        <span class="text-danger" v-if="errores.plantilla">{{
                                            errores.plantilla[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Opinion</label>
                                        <WysiwygEditor
                                            v-model="registro.opinion"
                                            placeholder="Opinion"
                                            :rows="3"
                                        />
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

import { safeDestroyDataTable } from "@/helpers/datatables.js";
import { alertError, alertSuccess, confirmAction } from "@/helpers/alerts.js";
import WysiwygEditor from "@/components/shared/WysiwygEditor.vue";
import axios from "axios";

const id = ref(0);
const registrosData = ref([]);
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
        registrosData.value = res.data.data;

        safeDestroyDataTable(dt.value || "#example23");

        nextTick(() => {
            const table = $(dt.value || "#example23");
            table.DataTable({
                data: registrosData.value,
                columns: [
                    {
                        data: null,
                        render: (d) => d.plantilla_tipo?.nom_tipo || "",
                    },
                    { data: "nom_plantilla" },
                    {
                        data: null,
                        render: (d) => d.medico?.name || "",
                    },
                    {
                        data: "estado",
                        render: (d) =>
                            d
                                ? '<span class="label label-success">Activo</span>'
                                : '<span class="label label-danger">Inactivo</span>',
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: (d, type, row) =>
                            `<button type="button" class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5 dt-edit" data-id="${row.id}"><i class="fa fa-pencil"></i></button>` +
                            `<button type="button" class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm dt-delete" data-id="${row.id}" data-nom="${row.nom_plantilla}"><i class="fa fa-trash"></i></button>`,
                    },
                ],
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                destroy: true,
            });
        });
    } catch (error) {
        registrosData.value = [];
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

            if (res.status >= 200 && res.status < 300) {
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
            if (res.status >= 200 && res.status < 300) {
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
    } finally {
        loadingGuardar.value = false;
    }
}
async function elimnarRegistro(id, nom) {
    const ok = await confirmAction({
        title: `Estas seguro de eliminar el tipo de plantillas ${nom}?`,
        text: "No podrás recuperar este registro!",
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
    } catch (error) {}
}
async function listarTiposPlantillas() {
    try {
        const res = await axios.get("/api/config-tipos-plantillas/listar/activos");
        tiposPlantillas.value = res.data.data;
    } catch (error) {}
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
        registro.value.estado = data.estado;
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
    $(document).on("click", "#example23 .dt-edit", function () {
        const rowId = $(this).data("id");
        const item = registrosData.value.find((r) => r.id === rowId);
        if (item) {
            actualizar.value = true;
            mostrarRegistro(item);
        }
    });

    $(document).on("click", "#example23 .dt-delete", function () {
        const rowId = $(this).data("id");
        const rowNom = $(this).data("nom");
        elimnarRegistro(rowId, rowNom);
    });

    ListarDatos();
    (listarMedicos(), listarTiposPlantillas());
});

onBeforeUnmount(() => {
    $(document).off("click", "#example23 .dt-edit");
    $(document).off("click", "#example23 .dt-delete");
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
    overflow-y: auto;
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

.right-sidebar .form-group {
    margin-bottom: 10px;
}

.right-sidebar .form-body .row .col-md-12,
.right-sidebar .form-body .row .col-md-6 {
    margin-bottom: 0;
}
</style>
