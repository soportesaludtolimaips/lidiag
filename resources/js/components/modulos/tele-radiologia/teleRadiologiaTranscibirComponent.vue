<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Estudios</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Estudios</a>
                    </li>
                    <li class="breadcrumb-item active">Transcribir Estudios Tele radiología</li>
                </ol>
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
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros para búsqueda de mis pendientes por
                            transcribir
                        </h4>
                        <div class="form-body">
                            <div class="row p-t-2">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Quien asignó</label>
                                        <select
                                            v-model="busqueda.quien_asigno_id"
                                            name="quien_asigno_id"
                                            id="quien_asigno_id"
                                            class="form-control custom-select"
                                        >
                                            <option value="">
                                                ...::: Todos los usuarios :::...
                                            </option>
                                            <option
                                                v-for="item in quienes_asignaron"
                                                :key="item.id"
                                                :value="item.id"
                                            >
                                                {{ item.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Nombres o # documento</label>
                                        <input
                                            type="text"
                                            id="bus_nom_num_docu"
                                            name="bus_nom_num_docu"
                                            v-model="busqueda.bus_nom_num_docu"
                                            class="form-control"
                                            placeholder="Nombres de paciente o # de documento"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Fecha Inicio</label>
                                        <input
                                            type="date"
                                            id="bus_fech_ini"
                                            name="bus_fech_ini"
                                            v-model="busqueda.bus_fech_ini"
                                            class="form-control"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Fecha Fin</label>
                                        <input
                                            type="date"
                                            id="bus_fecha_fin"
                                            name="bus_fecha_fin"
                                            v-model="busqueda.bus_fecha_fin"
                                            class="form-control"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    :disabled="loadingBuscar"
                                    @click="listarPendientesTrascribir()"
                                >
                                    <i v-if="loadingBuscar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-search"></i>
                                    Buscar
                                </button>
                                <button type="button" class="btn btn-inverse" @click="btnLimpiar()">
                                    <i class="fa fa-refresh"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h4 class="card-title mb-0">Listado de estudios disponibles</h4>
                                <h6 class="card-subtitle text-info mt-1">
                                    Asigne los estudios a los médicos para su respectiva lectura.
                                </h6>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm btn-rounded"
                                :class="isDarkMode ? 'btn-warning' : 'btn-outline-secondary'"
                                @click="toggleDarkMode"
                                :title="
                                    isDarkMode
                                        ? 'Cambiar a Modo Claro'
                                        : 'Cambiar a Modo Oscuro (Lectura)'
                                "
                            >
                                <i :class="isDarkMode ? 'fa fa-sun-o' : 'fa fa-moon-o'"></i>
                                {{ isDarkMode ? "Modo Claro" : "Modo Oscuro" }}
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table
                                id="example23"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Sede</th>
                                        <th>Toma</th>
                                        <th>Lectura</th>
                                        <th>Asignación</th>
                                        <th>Quien Asignó</th>
                                        <th>Médico</th>
                                        <th>Paciente</th>
                                        <th>Detalle</th>
                                        <th>Producto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in registros"
                                        :key="item.estudio_producto_id"
                                        :id="'row-' + item.estudio_producto_id"
                                    >
                                        <td>
                                            <button
                                                type="button"
                                                v-if="item.priori_id == 1"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-danger"
                                            >
                                                A
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 2"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-warning"
                                            >
                                                M
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 3"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-info"
                                            >
                                                B
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 4"
                                                class="btn waves-effect waves-light btn-rounded btn-sm"
                                            >
                                                S
                                            </button>
                                        </td>
                                        <td>{{ item.nom_sede }}</td>
                                        <td>{{ item.fec_estudio }}</td>
                                        <td>
                                            <span
                                                class="label label-success"
                                                v-if="item.fechor_lectura != null"
                                                >Con Lectura</span
                                            >
                                            <span
                                                class="label label-danger"
                                                v-if="item.fechor_lectura === null"
                                                >Sin Lectura</span
                                            >
                                        </td>
                                        <td>{{ formatDate(item.created_at) }}</td>
                                        <td>{{ item.quien_registro }}</td>
                                        <td>{{ item.nombres_medico }}</td>
                                        <td>
                                            # Docu: {{ item.num_docu }} <br />
                                            Nombres: {{ item.nom_pacien }}
                                        </td>
                                        <td>
                                            Atención: {{ item.atencion }} <br />
                                            Descrip: {{ item.study_desc }} <br />
                                            {{ item.accession_no }}
                                        </td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                :disabled="
                                                    loadingAbrirId === item.estudio_producto_id
                                                "
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingAbrirId === item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                :disabled="
                                                    loadingAnularId === item.estudio_producto_id
                                                "
                                                @click="
                                                    anularAsignacion(
                                                        item.estudio_producto_id,
                                                        item.nom_pacien,
                                                        item.nom_produc
                                                    )
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingAnularId === item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-eraser"></i>
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
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible, 'dark-mode': isDarkMode }"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title d-flex justify-content-between align-items-center">
                    <span>{{ tituloModal }}</span>
                    <div class="d-flex align-items-center">
                        <button
                            type="button"
                            class="btn btn-xs btn-outline-light m-r-10"
                            @click="toggleDarkMode"
                            :title="isDarkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                            style="padding: 2px 8px; font-size: 11px; border-radius: 12px"
                        >
                            <i
                                :class="isDarkMode ? 'fa fa-sun-o text-warning' : 'fa fa-moon-o'"
                            ></i>
                            {{ isDarkMode ? "Claro" : "Oscuro" }}
                        </button>
                        <span @click="btnCerrarModal()" class="cursor-pointer">
                            <i class="ti-close right-side-toggle"></i>
                        </span>
                    </div>
                </div>
                <div class="r-panel-body">
                    <form @submit.prevent>
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <form-paciente :registro="registro" :errores="errores" />

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Estudio
                                    </h5>
                                    <hr />

                                    <input
                                        type="hidden"
                                        id="estudio_producto_id"
                                        name="estudio_producto_id"
                                        v-model="registro.estudio_producto_id"
                                    />
                                    <input
                                        type="hidden"
                                        id="email"
                                        name="email"
                                        v-model="registro.email"
                                    />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Descripción:</dt>
                                                    <dd>{{ registro.study_desc }}</dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Detalle</dt>
                                                    <dd>{{ registro.accession_no }}</dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Fecha del estudio</dt>
                                                    <dd>{{ registro.fec_estudio }}</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label"
                                                    >Reportar al Email</label
                                                >
                                                <input
                                                    type="text"
                                                    id="email_reportar"
                                                    name="email_reportar"
                                                    v-model="registro.email_reportar"
                                                    class="form-control"
                                                    placeholder="Ingrese aquí el email a reportar"
                                                />
                                            </div>

                                            <div class="form-group">
                                                <dl>
                                                    <dt>Observaciones</dt>
                                                    <dd>{{ registro.observaciones }}</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Producto
                                    </h5>
                                    <hr />

                                    <div class="row">
                                        <div class="col-md-12">
                                            <dl>
                                                <dd>{{ registro.nom_produc }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                    <br />
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-9">
                                            <h5 class="card-title text-info">
                                                <i class="fa fa-edit"></i> Lectura
                                            </h5>
                                            <hr />
                                        </div>
                                        <div class="col-3">
                                            <div
                                                class="col-md-7 align-self-center text-right d-none d-md-block"
                                            >
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary btn-xs"
                                                    :disabled="loadingVerImagen"
                                                    @click="verImagen()"
                                                >
                                                    <i
                                                        v-if="loadingVerImagen"
                                                        class="fa fa-spinner fa-spin"
                                                    ></i>
                                                    <i v-else class="fa fa-file-image-o"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-danger" v-if="errores.lectura">{{
                                        errores.lectura[0]
                                    }}</span>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group" id="medicos-parent">
                                                <label>{{ registro.nombres_medico }}</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <textarea
                                                    v-model="registro.lectura"
                                                    class="textarea_editor form-control"
                                                    rows="20"
                                                    id="lectura"
                                                    style="height: 100%"
                                                    placeholder="Ingrese aquí la lectura del estudio ..."
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <!-- Nav tabs -->
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="nav-item">
                                                    <a
                                                        class="nav-link active"
                                                        data-toggle="tab"
                                                        href="#home8"
                                                        role="tab"
                                                    >
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-paperclip"></i> Adjuntos
                                                        </h5>
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a
                                                        class="nav-link"
                                                        data-toggle="tab"
                                                        href="#profile8"
                                                        role="tab"
                                                    >
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-file-audio-o"></i>
                                                            Audios
                                                        </h5>
                                                    </a>
                                                </li>
                                            </ul>
                                            <!-- Tab panes -->
                                            <div class="tab-content tabcontent-border">
                                                <div
                                                    class="tab-pane active p-20"
                                                    id="home8"
                                                    role="tabpanel"
                                                >
                                                    <div
                                                        class="col-md-8 col-lg-9"
                                                        v-for="itemArchivo in soportesHC"
                                                        :key="itemArchivo.archivo_encrip"
                                                    >
                                                        <a
                                                            href="javascript:void(0)"
                                                            :style="{
                                                                pointerEvents:
                                                                    loadingDescargaSoporte ===
                                                                    itemArchivo.archivo_encrip
                                                                        ? 'none'
                                                                        : 'auto',
                                                            }"
                                                            @click="
                                                                descargaSoportte(
                                                                    itemArchivo.archivo_original,
                                                                    itemArchivo.archivo_encrip
                                                                )
                                                            "
                                                        >
                                                            <i
                                                                v-if="
                                                                    loadingDescargaSoporte ===
                                                                    itemArchivo.archivo_encrip
                                                                "
                                                                class="fa fa-spinner fa-spin"
                                                            ></i>
                                                            {{ itemArchivo.archivo_original }}
                                                        </a>
                                                    </div>
                                                    <div
                                                        v-if="soportesHC.length === 0"
                                                        class="text-muted"
                                                    >
                                                        No hay adjuntos para este estudio.
                                                    </div>
                                                </div>
                                                <div
                                                    class="tab-pane p-20"
                                                    id="profile8"
                                                    role="tabpanel"
                                                >
                                                    <template
                                                        v-for="item in audiosDeEstudio"
                                                        :key="item.id"
                                                    >
                                                        <audio
                                                            :src="item.url"
                                                            controls
                                                            style="height: 15px; width: 100%"
                                                        ></audio>
                                                    </template>
                                                    <div
                                                        v-if="audiosDeEstudio.length === 0"
                                                        class="text-muted"
                                                    >
                                                        No hay audios para este estudio.
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
                                    :disabled="loadingGuardar"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i>
                                    {{ actualizar ? "Actualizar" : "Guardar" }}
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="btnCerrarModal()"
                                >
                                    Cancelar
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
import { getCurrentInstance, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import { listarUsuariosYSedes } from "../../../helpers/usuarios";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
dayjs.extend(utc);
dayjs.extend(timezone);
import axios from "axios";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables";
import { confirmAction, getUserSafeError, toastMessage } from "@/helpers/alerts";

const { appContext } = getCurrentInstance();
const emitter = appContext.config.globalProperties.emitter;

const DESTINO_TELE_RADIOLOGIA = "Tele Radiologia";

defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
});

const dt = ref(null);
const actualizar = ref(false);
const sidebarVisible = ref(false);

const isDarkMode = ref(localStorage.getItem("lidiag_dark_mode") === "true");

function toggleDarkMode() {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem("lidiag_dark_mode", isDarkMode.value ? "true" : "false");
}

const soportesHC = ref([]);
const registros = ref([]);
const tituloModal = ref("Transcribir estudio");
const registro = ref({
    id_estudio: 0,
    estudio_producto_id: 0,
    lectura: "",
    fec_lectura: "",
    fec_estudio: "",
    accession_no: "",
    study_desc: "",
    observaciones: "",
    email_reportar: "",
    num_docu: "",
    nom_pacien: "",
    sexo: "",
    fec_naci: "",
    email: "",
    tel: "",
    direccion: "",
    sede_id: "",
    atencion: null,
    servicio: null,
    eps: null,
    contrato: null,
    medico_id: null,
    cod_cups: "",
    nom_produc: "",
    num_docu_medico: "",
    reg_medico: "",
    nombres_medico: "",
    firma_medico: "",
    url_oviyam: null,
    tap_oviyam: null,
    study_iuid: null,
    destino_estudio: DESTINO_TELE_RADIOLOGIA,
});
const busqueda = ref({
    estudioParaTranscibir: "si",
    quien_asigno_id: "",
    medico_id: "",
    sede_id: "",
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
    destino_estudio: DESTINO_TELE_RADIOLOGIA,
});
const datosImagen = ref({ urlOviyam: "", patientId: "", studyUID: "", serverName: "" });
const audiosDeEstudio = ref([]);
const quienes_asignaron = ref([]);
const medicos = ref([]);
const errores = ref({});
const id = ref(null);
const idRow = ref(null);
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingAbrirId = ref(null);
const loadingAnularId = ref(null);
const loadingDescargaSoporte = ref(null);
const loadingVerImagen = ref(false);

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

function btnLimpiar() {
    busqueda.value.quien_asigno_id = "";
    busqueda.value.medico_id = "";
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    errores.value = {};
    destroyDataTable();
    registros.value = [];
    btnCerrarModal();
}

async function listarPendientesTrascribir() {
    if (loadingBuscar.value) return;
    loadingBuscar.value = true;
    try {
        if (!sessionStorage.getItem("ST-sede")) {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes establecer la sede en la cual vas a trabajar.",
                icon: "warning",
            });
            return;
        }

        destroyDataTable();

        const res = await axios.post("/api/estudio-listarPendientesTrascribir", {
            sede_id: sessionStorage.getItem("ST-sede"),
            quien_asigno_id: busqueda.value.quien_asigno_id || undefined,
            medico_id: busqueda.value.medico_id || undefined,
            bus_nom_num_docu: busqueda.value.bus_nom_num_docu || undefined,
            bus_fech_ini: busqueda.value.bus_fech_ini || undefined,
            bus_fecha_fin: busqueda.value.bus_fecha_fin || undefined,
            destino_estudio: DESTINO_TELE_RADIOLOGIA,
            estudioParaTranscibir: busqueda.value.estudioParaTranscibir,
        });

        registros.value = res.data.data;

        nextTick(() => {
            initDataTable();
        });
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: getUserSafeError(error, "No fue posible listar los pendientes."),
            icon: "error",
        });
    } finally {
        loadingBuscar.value = false;
    }
}

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        toastMessage({
            heading: "Ok!!!",
            text: "Enviando lectura, por favor espere",
            icon: "info",
        });

        const payload = {
            estudio_producto_id: registro.value.estudio_producto_id,
            id_estudio: registro.value.id_estudio,
            lectura: registro.value.lectura,
            num_docu: registro.value.num_docu,
            nom_pacien: registro.value.nom_pacien,
            email: registro.value.email || undefined,
            tel: registro.value.tel || undefined,
            fec_naci: registro.value.fec_naci || undefined,
            fec_estudio: registro.value.fec_estudio || undefined,
            fec_lectura: registro.value.fec_lectura || undefined,
            nombres_medico: registro.value.nombres_medico || undefined,
            reg_medico: registro.value.reg_medico || undefined,
            num_docu_medico: registro.value.num_docu_medico || undefined,
            firma_medico: registro.value.firma_medico || undefined,
            sede_id: registro.value.sede_id,
            nom_sede: registro.value.nom_sede || undefined,
            cod_cups: registro.value.cod_cups,
            nom_produc: registro.value.nom_produc,
            medico_id: registro.value.medico_id || undefined,
            url_oviyam: registro.value.url_oviyam || undefined,
            study_iuid: registro.value.study_iuid || undefined,
            tap_oviyam: registro.value.tap_oviyam || undefined,
            destino_estudio: DESTINO_TELE_RADIOLOGIA,
            email_reportar: registro.value.email_reportar || undefined,
            sexo: registro.value.sexo || undefined,
            direccion: registro.value.direccion || undefined,
        };

        const res = await axios.post("/api/estudio-guardarTranscripcion", payload);

        if (res.status == 200) {
            const estudioEliminar = registros.value.findIndex(
                (x) => x.estudio_producto_id == idRow.value
            );
            if (estudioEliminar >= 0) {
                registros.value.splice(estudioEliminar, 1);
                nextTick(() => {
                    destroyDataTable();
                    initDataTable();
                });
            }

            toastMessage({
                heading: "Ok!!!",
                text: res.data.data.message,
                icon: "success",
            });

            btnCerrarModal();
            errores.value = {};
        } else {
            toastMessage({
                heading: "Upss!!!",
                text: res.data.data.message,
                icon: "error",
            });
        }
    } catch (error) {
        const status = error?.response?.status;
        if (status == 400 && error?.response?.data && typeof error.response.data === "object") {
            errores.value = error.response.data;
        }
        if (status == 402) {
            toastMessage({
                heading: "Upss!!!",
                text:
                    error?.response?.data?.message ||
                    "La plantilla no está configurada para esta sede.",
                icon: "error",
            });
        } else if (status !== 400) {
            toastMessage({
                heading: "Upss!!!",
                text: getUserSafeError(error, "No fue posible guardar la transcripción."),
                icon: "error",
            });
        }
    } finally {
        loadingGuardar.value = false;
    }
}

function descargaSoportte(nomArchivoOriginal, nomArchivoEncriptado) {
    if (loadingDescargaSoporte.value) return;
    loadingDescargaSoporte.value = nomArchivoEncriptado;
    const url = `/api/descarga-soportes-hc/${nomArchivoEncriptado}`;
    let blobUrl = null;
    let link = null;
    axios
        .get(url, { responseType: "blob" })
        .then((response) => {
            blobUrl = window.URL.createObjectURL(new Blob([response.data]));
            link = document.createElement("a");
            link.href = blobUrl;
            link.setAttribute("download", nomArchivoOriginal || nomArchivoEncriptado);
            document.body.appendChild(link);
            link.click();
        })
        .catch((error) => {
            toastMessage({
                heading: "Error",
                text: getUserSafeError(error, "No fue posible descargar el archivo."),
                icon: "error",
            });
        })
        .finally(() => {
            if (link && document.body.contains(link)) {
                document.body.removeChild(link);
            }
            if (blobUrl) {
                setTimeout(() => window.URL.revokeObjectURL(blobUrl), 1000);
            }
            loadingDescargaSoporte.value = null;
        });
}

async function anularAsignacion(idProductoEstudio, paciente, producto) {
    const ok = await confirmAction({
        title: `¿Estás seguro de anular la lectura del paciente ${paciente} con el producto ${producto}?`,
        text: "No podrás recuperar este registro!",
        icon: "warning",
    });
    if (!ok) return;
    if (loadingAnularId.value) return;

    loadingAnularId.value = idProductoEstudio;
    try {
        const res = await axios.post("/api/estudio-anular-asignacion", {
            id: idProductoEstudio,
        });
        if (res.status === 200) {
            const idx = registros.value.findIndex(
                (x) => x.estudio_producto_id == idProductoEstudio
            );
            if (idx >= 0) {
                registros.value.splice(idx, 1);
                nextTick(() => {
                    destroyDataTable();
                    initDataTable();
                });
            }
            toastMessage({
                heading: "Anulado!",
                text: "El producto se anuló correctamente.",
                icon: "success",
            });
        } else {
            toastMessage({
                heading: "Ups!",
                text: "No fue posible anular el producto.",
                icon: "error",
            });
        }
    } catch (error) {
        toastMessage({
            heading: "Error",
            text: getUserSafeError(error, "No fue posible anular el producto."),
            icon: "error",
        });
    } finally {
        loadingAnularId.value = null;
    }
}

function verImagen() {
    if (loadingVerImagen.value) return;
    const base = datosImagen.value.urlOviyam || "";
    if (!base) {
        toastMessage({
            heading: "Upsss!!!",
            text: "No hay visor configurado para esta sede.",
            icon: "warning",
        });
        return;
    }
    loadingVerImagen.value = true;
    const params = new URLSearchParams({
        patientID: String(datosImagen.value.patientId ?? ""),
        studyUID: String(datosImagen.value.studyUID ?? ""),
        serverName: String(datosImagen.value.serverName ?? ""),
    });
    try {
        window.open(`${base}?${params.toString()}`, "_blank", "noopener,noreferrer");
    } catch {
        toastMessage({
            heading: "Error",
            text: "No fue posible abrir el visor de imágenes.",
            icon: "error",
        });
    } finally {
        setTimeout(() => {
            loadingVerImagen.value = false;
        }, 500);
    }
}

async function listarAudios() {
    if (!id.value) return;
    try {
        const res = await axios.get("/api/listar-audios-estudio/" + id.value);
        audiosDeEstudio.value = res.data.data;
    } catch {
        audiosDeEstudio.value = [];
    }
}

async function listarMedicos() {
    try {
        const moduloId = sessionStorage.getItem("ST-modulo") || "1";
        const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
        medicos.value = res.data.data;
    } catch {
        medicos.value = [];
    }
}

function establecerSede() {
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    registros.value = [];
}

/**
 * Maneja el evento global sedeSeleccionada actualizando la sede activa
 * y limpiando resultados previos para evitar discrepancias de datos.
 *
 * @param {Object} data - Datos del evento con idSede y nomSede.
 * @returns {void}
 * @author Jhon Javer Lozano Arce
 * @date 2026-10-16
 */
function onSedeSeleccionada(data) {
    const idSede = data?.idSede ?? sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = idSede;
    destroyDataTable();
    registros.value = [];
    btnCerrarModal();
}

function formatDate(timestamp) {
    return dayjs.utc(timestamp).local().format("YYYY-MM-DD HH:mm:ss");
}

async function mostrarRegistro(data = {}) {
    if (loadingAbrirId.value !== null) return;
    loadingAbrirId.value = data.estudio_producto_id;
    const date = new Date();
    const fechaActual =
        ("0" + date.getDate()).slice(-2) +
        "-" +
        ("0" + (date.getMonth() + 1)).slice(-2) +
        "-" +
        date.getFullYear() +
        " " +
        ("0" + date.getHours()).slice(-2) +
        ":" +
        ("0" + date.getMinutes()).slice(-2) +
        ":" +
        ("0" + date.getSeconds()).slice(-2);

    tituloModal.value = "Transcribir la lectura del paciente: " + data.nom_pacien;
    id.value = data.id;
    registro.value.id_estudio = data.id;
    idRow.value = data.estudio_producto_id;
    registro.value.estudio_producto_id = data.estudio_producto_id;
    registro.value.fec_estudio = data.fec_estudio;
    registro.value.fec_lectura = data.fechor_lectura;
    registro.value.accession_no = data.accession_no;
    registro.value.study_desc = data.study_desc;
    registro.value.email_reportar = data.email_reportar;
    registro.value.atencion = data.atencion;
    registro.value.medico_id = data.medico_id;
    registro.value.num_docu = data.num_docu;
    registro.value.nom_pacien = data.nom_pacien;
    registro.value.direccion = data.direccion;
    registro.value.email = data.email;
    registro.value.tel = data.tel;
    registro.value.sexo = data.sexo ?? data.pat_sex;
    registro.value.fec_naci = data.fec_naci ?? data.pat_birthdate;
    registro.value.cod_cups = data.cod_cups;
    registro.value.nom_produc = data.nom_produc;
    registro.value.num_docu_medico = data.num_docu_medico;
    registro.value.reg_medico = data.reg_medico;
    registro.value.nombres_medico = `Médico: ${data.nombres_medico}`;
    registro.value.firma_medico = data.firma_medico;
    registro.value.lectura = `Atención: ${data.atencion}\nFecha y Hora del estudio: ${data.fec_estudio}\nFecha y Hora de transcripción: ${fechaActual}\n\n${data.nom_produc}\n\n${data.lectura}`;

    datosImagen.value.urlOviyam = data.url_oviyam;
    datosImagen.value.patientId = data.num_docu;
    datosImagen.value.studyUID = data.study_iuid;
    datosImagen.value.serverName = data.tap_oviyam;

    registro.value.sede_id = data.sede_id;
    registro.value.nom_sede = data.nom_sede;

    registro.value.url_oviyam = data.url_oviyam;
    registro.value.study_iuid = data.study_iuid;
    registro.value.tap_oviyam = data.tap_oviyam;

    try {
        const resSoportesHC = await axios.get("/api/listar-soportes-hc-estudio?id=" + id.value);
        soportesHC.value = resSoportesHC.data.data;
    } catch {
        soportesHC.value = [];
    }

    audiosDeEstudio.value = [];
    listarAudios();
    errores.value = {};

    sidebarVisible.value = true;
    loadingAbrirId.value = null;
}

function btnCerrarModal() {
    sidebarVisible.value = false;
    audiosDeEstudio.value = [];
    errores.value = {};
}

onMounted(async () => {
    listarMedicos();
    establecerSede();
    emitter.on("sedeSeleccionada", onSedeSeleccionada);
    await listarUsuariosYSedes({
        set quienes_asignaron(v) {
            quienes_asignaron.value = v;
        },
        set medicos(v) {
            medicos.value = v;
        },
    });
});

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
    destroyDataTable();
});
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -95%;
    width: 95%;
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
    width: 95%;
    display: block;
}

/* ============================================================== */
/* Modo Oscuro / Radiología (Dark Mode para entorno PACS)         */
/* ============================================================== */
.right-sidebar.dark-mode {
    background: #181a20 !important;
    color: #e2e8f0 !important;
}

.right-sidebar.dark-mode .rpanel-title {
    background: #12141a !important;
    color: #f1f5f9 !important;
    border-bottom: 1px solid #2d3343 !important;
}

.right-sidebar.dark-mode .r-panel-body {
    background: #181a20 !important;
}

.right-sidebar.dark-mode hr {
    border-top-color: #2d3343 !important;
}

.right-sidebar.dark-mode dt,
.right-sidebar.dark-mode label {
    color: #cbd5e1 !important;
}

.right-sidebar.dark-mode dd,
.right-sidebar.dark-mode p,
.right-sidebar.dark-mode span,
.right-sidebar.dark-mode li {
    color: #e2e8f0;
}

.right-sidebar.dark-mode .form-control {
    background-color: #222632 !important;
    color: #f8fafc !important;
    border-color: #3b4254 !important;
}

.right-sidebar.dark-mode .form-control:focus {
    background-color: #262b38 !important;
    border-color: #00bcd4 !important;
    color: #ffffff !important;
}

.right-sidebar.dark-mode .list-group-item {
    background-color: #222632 !important;
    border-color: #2d3343 !important;
    color: #e2e8f0 !important;
}

.right-sidebar.dark-mode .table {
    color: #e2e8f0 !important;
}

.right-sidebar.dark-mode .table-bordered,
.right-sidebar.dark-mode .table-bordered th,
.right-sidebar.dark-mode .table-bordered td {
    border-color: #2d3343 !important;
}

.right-sidebar.dark-mode :deep(.wysihtml5-toolbar) {
    background: #222632 !important;
    border-color: #3b4254 !important;
    padding: 6px !important;
    border-radius: 4px 4px 0 0 !important;
}

.right-sidebar.dark-mode :deep(.wysihtml5-toolbar .btn) {
    background-color: #2d3343 !important;
    border-color: #3b4254 !important;
    color: #f1f5f9 !important;
}

.right-sidebar.dark-mode :deep(.wysihtml5-toolbar .btn:hover) {
    background-color: #3b4254 !important;
    color: #38bdf8 !important;
}

.right-sidebar.dark-mode :deep(.wysihtml5-sandbox) {
    border-color: #3b4254 !important;
    background-color: #1e222b !important;
}
</style>
