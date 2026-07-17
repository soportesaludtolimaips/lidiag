<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Estudios En Otras Plataformas</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Estudios</a>
                    </li>
                    <li class="breadcrumb-item active">Asignar Estudios</li>
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
                            <i class="fa fa-filter"></i> Filtros para busqueda de estudios
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label"
                                                >Nombres de paciente o # de documento
                                            </label>
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder="Nombres de paciente o # de documento"
                                                @keyup.enter="buscarStudy()"
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
                                            <!-- <span class="text-danger" v-if="errores.bus_fech_ini">
                                                {{ erroresBusqueda.bus_fech_ini[0] }}
                                            </span> -->
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
                                            <!-- <span class="text-danger" v-if="errores.bus_fecha_fin">
                                                {{ erroresBusqueda.bus_fecha_fin[0] }}
                                            </span> -->
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-success m-r-5"
                                        @click="buscarStudy()"
                                        :disabled="loadingBuscar"
                                    >
                                        <i
                                            class="fa"
                                            :class="
                                                loadingBuscar ? 'fa-spinner fa-spin' : 'fa-search'
                                            "
                                        ></i>
                                        {{ loadingBuscar ? "Buscando..." : "Buscar" }}
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-inverse"
                                        @click="btnLimpiar()"
                                    >
                                        <i class="fa fa-refresh"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de estudios disponibles</h4>
                        <h6 class="card-subtitle text-info">
                            Asigne los estudios a los médicos para su respectiva lectura.
                        </h6>
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
                                        <th with="5%">Fecha del Estudio</th>
                                        <th with="5%"># Documento</th>
                                        <th with="30%">Paciente</th>
                                        <th>Accession</th>
                                        <th>Descripción</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in registros"
                                        :key="item.id"
                                        :id="item.study_pk"
                                        :class="{ 'text-info': item.agendado !== 0 }"
                                    >
                                        <td>{{ item.study_datetime }}</td>
                                        <td>{{ item.pat_id }}</td>
                                        <td>{{ item.pat_name }}</td>
                                        <td>{{ item.accession_no }}</td>
                                        <td>{{ item.study_desc }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item, index);
                                                "
                                                title="Asignar estudio"
                                            >
                                                <i class="fa fa-child"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="
                                                    verImagen(item.pat_id, item.study_iuid, index)
                                                "
                                                :disabled="loadingVerImagenIndex === index"
                                                title="Ver imagenes"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        loadingVerImagenIndex === index
                                                            ? 'fa-spinner fa-spin'
                                                            : 'fa-file-photo-o'
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
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div
            class="right-sidebar"
            id="right-sidebar"
            style="overflow-x: hidden; overflow-y: scroll; overflow-x: hidden; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i
                            class="ti-close right-side-toggle"
                            id="btnCerralModalForm"
                            @click="cerrarModal()"
                        ></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <form-paciente
                                        :registro="registro"
                                        :errores="errores"
                                        :mostrar-errores="false"
                                    />

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Datos del Estudio
                                    </h5>
                                    <hr />

                                    <input
                                        type="hidden"
                                        id="study_pk"
                                        name="study_pk"
                                        v-model="registro.study_pk"
                                    />
                                    <input
                                        type="hidden"
                                        id="study_iuid"
                                        name="study_iuid"
                                        v-model="registro.study_iuid"
                                    />
                                    <input
                                        type="hidden"
                                        id="study_id"
                                        name="study_id"
                                        v-model="registro.study_id"
                                    />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Descripción</label>
                                                <input
                                                    type="text"
                                                    id="study_desc"
                                                    name="study_desc"
                                                    v-model="registro.study_desc"
                                                    class="form-control"
                                                />
                                                <!-- <span class="text-danger" v-if="errores.study_desc">
                                                    {{ errores.study_desc[0] }}
                                                </span> -->
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Detalle</label>
                                                <input
                                                    type="text"
                                                    id="accession_no"
                                                    name="accession_no"
                                                    v-model="registro.accession_no"
                                                    class="form-control"
                                                />
                                                <!-- <span class="text-danger" v-if="errores.accession_no">
                                                    {{ errores.accession_no[0] }}
                                                </span> -->
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Fecha</label>
                                                <input
                                                    type="text"
                                                    id="study_datetime"
                                                    name="study_datetime"
                                                    v-model="registro.study_datetime"
                                                    class="form-control"
                                                />
                                                <!-- <span class="text-danger" v-if="errores.study_datetime">
                                                    {{ errores.study_datetime[0] }}
                                                </span> -->
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
                                                    type="email"
                                                    id="email_reportar"
                                                    name="email_reportar"
                                                    v-model="registro.email_reportar"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Observaciones</label>
                                                <input
                                                    type="text"
                                                    id="observaciones"
                                                    name="observaciones"
                                                    v-model="registro.observaciones"
                                                    class="form-control"
                                                    placeholder="Ingrese aqi las observaciones del estudio"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Adjunto
                                    </h5>
                                    <hr>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input type="file" class="form-control" id="exampleInputFile1"
                                                    aria-describedby="fileHelp" @change='obtenerArchivo1' />
                                            </div>
                                            <div class="form-group">
                                                <input type="file" class="form-control" id="exampleInputFile2"
                                                    aria-describedby="fileHelp" @change='obtenerArchivo2' />
                                            </div>
                                            <div class="form-group">
                                                <input type="file" class="form-control" id="exampleInputFile3"
                                                    aria-describedby="fileHelp" @change='obtenerArchivo3' />
                                            </div>
                                        </div>
                                    </div> -->
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos de la Asignacion
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <!-- <div class="col-md-8">

                                            <div class="form-group" id="medicos-parent">
                                                <label>Médico</label>
                                                <Select2 :value="registro.medico_id" :options="medicoOptions"
                                                    width="100%" :allow-clear="true" :label-fields="['name']"
                                                    :dropdown-parent-selector="'#medicos-parent'"
                                                    @input="onMedicoSelect" />

                                                <span class="text-danger" v-if="errores.medico_id">
                                                    {{ errores.medico_id[0] }}
                                                </span>
                                            </div>
                                        </div> -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Prioridad</label>
                                                <select
                                                    v-model="registro.prioridad_id"
                                                    name="prioridad_id"
                                                    id="prioridad_id"
                                                    class="form-control custom-select"
                                                >
                                                    <option
                                                        v-for="(
                                                            ItemPrioridad, index
                                                        ) in prioridades"
                                                        :key="index"
                                                        :value="ItemPrioridad.id"
                                                    >
                                                        {{ ItemPrioridad.nom_priori }} -
                                                        {{ ItemPrioridad.tiempo }}
                                                        {{ ItemPrioridad.tipo_tiempo }}
                                                        {{ ItemPrioridad.observacion }}
                                                    </option>
                                                </select>
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.prioridad_id"
                                                >
                                                    {{ errores.prioridad_id[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-4" v-if="sedeInterface === true">
                                            <div class="form-group">
                                                <label class="control-label">Atención</label>
                                                <select
                                                    v-model="interfaceAtencionSeleccionada"
                                                    name="interfaceAtencionSeleccionada"
                                                    id="interfaceAtencionSeleccionada"
                                                    class="form-control custom-select"
                                                    @change="interfaceListarProductosDiagnosticos()"
                                                >
                                                    <option
                                                        v-for="(
                                                            ItemAtencion, index
                                                        ) in interfaceAtenciones"
                                                        :key="index"
                                                        :value="ItemAtencion.IdAtencion"
                                                    >
                                                        {{ ItemAtencion.IdAtencion }} -
                                                        {{ ItemAtencion.FecIngreso }}
                                                    </option>
                                                </select>
                                                <!-- <span class="text-danger" v-if="errores.prioridad_id">
                                                    {{ errores.prioridad_id[0] }}
                                                </span> -->
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-40">
                                        <i class="fa fa-user"></i> PRODUCTOS
                                    </h5>

                                    <hr />

                                    <span class="text-danger" v-if="errores.productosEstudio">
                                        {{ errores.productosEstudio[0] }}
                                    </span>
                                    <div class="row">
                                        <div
                                            class="col-md-12"
                                            v-if="sedeInterface === false"
                                            id="productos-parent"
                                        >
                                            <Select2
                                                :value="productoSelecciondo"
                                                :options="productoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                @input="onProductoSelect"
                                            />
                                        </div>
                                        <div
                                            class="col-md-12 table-responsive"
                                            style="overflow-y: scroll; height: 250px"
                                        >
                                            <table
                                                id="myTable"
                                                class="table table-bordered table-striped"
                                            >
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Código</th>
                                                        <th>Producto</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="(
                                                            item, index
                                                        ) in registro.productosEstudio"
                                                        :key="item.id"
                                                    >
                                                        <td>
                                                            <input
                                                                type="checkbox"
                                                                class="iCheck-helper"
                                                                :id="`producto-check-${index}`"
                                                                data-checkbox="icheckbox_flat-red"
                                                                v-model="item.checked"
                                                            />
                                                            <label
                                                                :for="`producto-check-${index}`"
                                                            ></label>
                                                        </td>
                                                        <td>{{ item.cod_cups }}</td>
                                                        <td>{{ item.nom_produc }}</td>
                                                        <td class="text-nowrap">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="
                                                                    quitarProductoEstudio(index)
                                                                "
                                                            >
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-40">
                                        <i class="fa fa-user"></i> DIAGNOSTICOS
                                    </h5>

                                    <hr />

                                    <span class="text-danger" v-if="errores.diagnosticosEstudio">
                                        {{ errores.diagnosticosEstudio[0] }}
                                    </span>
                                    <!-- <button type="button" class="btn waves-effect waves-light btn-xs btn-info float-right">
                                        <i class="fa fa-search"></i>
                                    </button> -->

                                    <div class="row">
                                        <div
                                            class="col-md-12"
                                            v-if="sedeInterface === false"
                                            id="diagnosticos-parent"
                                        >
                                            <Select2
                                                :value="diagnosticoSelecciondo"
                                                :options="diagnosticoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                :label-fields="['nom_diagnos']"
                                                @input="onDiagnosticoSelect"
                                            />
                                        </div>
                                        <div
                                            class="col-md-12 table-responsive"
                                            style="overflow-y: scroll; height: 250px"
                                        >
                                            <table
                                                id="myTable"
                                                class="table table-bordered table-striped"
                                            >
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Diagnostico</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="(
                                                            item, index
                                                        ) in registro.diagnosticosEstudio"
                                                        :key="item.id"
                                                    >
                                                        <td>{{ item.cod_diagnos }}</td>
                                                        <td>{{ item.nom_diagnos }}</td>
                                                        <td class="text-nowrap">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="
                                                                    quitarDiagnosticoEstudio(index)
                                                                "
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

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    {{ loadingGuardar ? "Guardando..." : "Guardar" }}
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarModal()"
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
const { proxy } = getCurrentInstance();
const emitter = proxy.emitter;
import { computed, nextTick, onBeforeUnmount, onMounted, ref, getCurrentInstance } from "vue";

import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { handleAxiosErrorToast } from "@/helpers/alerts";
import axios from "axios";

const props = defineProps({ usuarioactual: Object });

const dt = ref(null);
const actualizar = ref(false);

const dataIndex = ref(0);
const id = ref(0);
const registros = ref([]);
const tituloModal = ref("Nuevo registro");
const registro = ref({
    study_pk: "",
    study_iuid: "",
    study_datetime: "",
    study_id: "",
    accession_no: "",
    study_desc: "",
    mods_in_study: "",
    observaciones: "",
    medico_id: "",
    prioridad_id: "",
    sede_id: sedeActual,
    quien_registro_id: props.usuarioactual.id,
    num_docu: "",
    nombres: "",
    sexo: "",
    fec_naci: "",
    email: "",
    email_reportar: "",
    direccion: "",
    tel: "",
    productosEstudio: [],
    diagnosticosEstudio: [],
    atencion_id: null,
    destino_estudio: "Teleradiologia",
});
const soportesHC = ref({ archivo1: null, archivo2: null, archivo3: null });
const busqueda = ref({
    sede_id: sedeActual,
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});
const interfaceAtenciones = ref([]);
const interfaceAtencionSeleccionada = ref(null);
const errores = ref({});
const erroresBusqueda = ref({});
const medicos = ref([]);
const prioridades = ref([]);
const productos = ref([]);
const diagnosticos = ref([]);
const productoSelecciondo = ref("");
const diagnosticoSelecciondo = ref("");
const sedeActual = ref(sessionStorage.getItem("ST-sede"));
const productosAtencion = ref([]);
const sedeInterface = ref(false);
const idRow = ref(null);
const selectedRows = ref({});
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingVerImagenIndex = ref(null);

const diagnosticoOptions = computed(() => {
    // Adaptar opciones al Select2 local: usa optionValue='id' y labelFields.
    return (diagnosticos.value || []).map((d) => ({
        id: String(d.id),
        nom_diagnos: d.nom_diagnos,
    }));
});
const productoOptions = computed(() => {
    // Opciones para el Select2 local de productos.
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});
const medicoOptions = computed(() => {
    // Opciones para el Select2 local de medicos.
    return (medicos.value || []).map((p) => ({ id: String(p.id), name: p.name }));
});

async function buscarStudy() {
    safeDestroyDataTable(dt.value || "#example23");

    try {
        if (loadingBuscar.value) return;
        loadingBuscar.value = true;
        if (sessionStorage.getItem("ST-sede") == null) {
            $.toast({
                heading: "Upsss!!!",
                text: "Debes establece la sede en la cual vas a trabajar.",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "warning",
                hideAfter: 3500,
                stack: 6,
            });
        } else if (
            busqueda.value.bus_nom_num_docu == "" &&
            busqueda.value.bus_fech_ini == "" &&
            busqueda.value.bus_fecha_fin == ""
        ) {
            $.toast({
                heading: "Upsss!!!",
                text: "Debes establece la fecha de inicio o finalización para la busqueda.",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "warning",
                hideAfter: 3500,
                stack: 6,
            });
        } else {
            // Validación de rango de fechas
            if (
                (busqueda.value.bus_fech_ini && !busqueda.value.bus_fecha_fin) ||
                (!busqueda.value.bus_fech_ini && busqueda.value.bus_fecha_fin)
            ) {
                $.toast({
                    heading: "Upsss!!!",
                    text: "Debes diligenciar Fecha Inicio y Fecha Fin.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
                return;
            }
            if (
                busqueda.value.bus_fech_ini &&
                busqueda.value.bus_fecha_fin &&
                busqueda.value.bus_fech_ini > busqueda.value.bus_fecha_fin
            ) {
                $.toast({
                    heading: "Upsss!!!",
                    text: "La Fecha Inicio no puede ser mayor que la Fecha Fin.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
                return;
            }
            const res = await axios.post("/api/study.listarEstudios", busqueda.value);

            if (res.status == 200) {
                safeDestroyDataTable(dt.value || "#example23");

                registros.value = res.data.data;
                nextTick(() => {
                    safeInitDataTable(dt.value || "#example23", {
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                });
            } else if (res.status == 500) {
                $.toast({
                    heading: "Upsss!!!",
                    text: res.data.data.message,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "danger",
                    hideAfter: 3500,
                    stack: 6,
                });
            }
        }
    } catch (error) {
        erroresBusqueda.value = error?.response?.data?.errors || {};
        handleAxiosErrorToast(error, "No fue posible listar estudios.");
    } finally {
        loadingBuscar.value = false;
    }
}
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        const formData = new FormData();
        formData.append("registro", JSON.stringify(registro.value));
        if (soportesHC.value.archivo1) formData.append("archivo1", soportesHC.value.archivo1);
        if (soportesHC.value.archivo2) formData.append("archivo2", soportesHC.value.archivo2);
        if (soportesHC.value.archivo3) formData.append("archivo3", soportesHC.value.archivo3);

        const res = await axios.post("/api/estudios", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        if (res.status == 200 || res.status === 201) {
            $.toast({
                heading: "Ok!!!",
                text: res.data.data.message,
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "success",
                hideAfter: 3500,
                stack: 6,
            });

            // Actualiza el valor de `agendado` para que se refleje el cambio de clase
            registros.value[idRow.value].agendado = 1; // O el valor que necesites asignar
            selectedRows.value[idRow.value] = !selectedRows.value[idRow.value];
        }

        /* btnCerralModalForm();
                btnLimpiar(); */
    } catch (error) {
        errores.value = error?.response?.data?.errors || error?.response?.data || {};
        handleAxiosErrorToast(error, "No fue posible guardar el registro.");
    } finally {
        loadingGuardar.value = false;
    }
}
function obtenerArchivo1(e) {
    soportesHC.value.archivo1 = e.target.files[0];
}
function obtenerArchivo2(e) {
    soportesHC.value.archivo2 = e.target.files[0];
}
function obtenerArchivo3(e) {
    soportesHC.value.archivo3 = e.target.files[0];
}
async function mostrarRegistro(data = {}, index) {
    btnLimpiar();

    tituloModal.value = "Agendar al paciente: " + data.pat_name;
    id.value = data.study_pk;
    registro.value.study_pk = data.study_pk;
    registro.value.study_iuid = data.study_iuid;
    registro.value.study_id = data.study_id;
    registro.value.study_datetime = data.study_datetime;
    registro.value.accession_no = data.accession_no;
    registro.value.study_desc = data.study_desc;
    registro.value.mods_in_study = data.mods_in_study;
    registro.value.email = "";
    registro.value.email_reportar = sessionStorage.getItem("ST-email_repor");
    registro.value.num_docu = data.pat_id;
    registro.value.nombres = data.pat_name;
    registro.value.sexo = data.pat_sex;
    registro.value.fec_naci = data.pat_birthdate;
    idRow.value = index;

    //btnCerralModalForm();
    abrirModal();

    /**
     * Verificar si la sede tiene interface, traigo la informacion de las producto y diagnosticos
     */
    const res = await axios.get("/api/config-sedes/" + sedeActual.value + "/buscarPorId");
    if (res.data.data.interface_software != null) {
        const resAtenciones = await axios.get(
            "/api/interface-sahi-listar-atenciones/" + registro.value.num_docu
        );
        interfaceAtenciones.value = resAtenciones.data.data;
    }
}
async function interfaceListarProductosDiagnosticos() {
    /**
     * Listo los productos de una atención
     */
    const resProuctos = await axios.get(
        "/api/interface-sahi-listar-listarProductosDiagnosticos/" +
            interfaceAtencionSeleccionada.value
    );

    const data = resProuctos.data.data;
    registro.value.productosEstudio = [];
    if (data.productos.length > 0) {
        data.productos.forEach((element) => {
            registro.value.productosEstudio.push({
                checked: false,
                id: element.IdProducto,
                cod_cups: element.cod_cups,
                nom_produc: element.nom_produc,
            });
        });
    }

    registro.value.diagnosticosEstudio = [];
    if (data.diagnosticos.length > 0) {
        registro.value.diagnosticosEstudio = data.diagnosticos;
    }

    registro.value.atencion_id = interfaceAtencionSeleccionada.value;
}
async function establecerSede() {
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    sedeActual.value = sessionStorage.getItem("ST-sede");

    try {
        const res = await axios.get(`/config-sedes/${sedeActual.value}/buscarPorId`);
        sedeInterface.value = res?.data?.data?.interface_software != null;
    } catch (error) {
        sedeInterface.value = false;
    }
    registros.value = [];
}
function btnCerralModalForm() {
    $(".right-sidebar").slideDown(50);
    $(".right-sidebar").toggleClass("shw-rside");
}
function abrirModal() {
    $(".right-sidebar").addClass("shw-rside");
}
function cerrarModal() {
    $(".right-sidebar").removeClass("shw-rside");
}
function btnLimpiar() {
    registro.value.study_pk = "";
    registro.value.study_iuid = "";
    registro.value.study_id = "";
    registro.value.study_datetime = "";
    registro.value.accession_no = "";
    registro.value.study_desc = "";
    registro.value.mods_in_study = "";
    registro.value.email_reportar = "";

    registro.value.num_docu = "";
    registro.value.nombres = "";
    registro.value.sexo = "";
    registro.value.email = "";
    registro.value.fec_naci = "";

    registro.value.diagnosticosEstudio.splice(0, registro.value.diagnosticosEstudio.length);
    registro.value.productosEstudio.splice(0, registro.value.productosEstudio.length);

    /* registro.value.medico_id = 0;
            registro.value.prioridad_id = 0; */
    productoSelecciondo.value = 0;
    diagnosticoSelecciondo.value = 0;

    soportesHC.value.archivo1 = null;
    soportesHC.value.archivo2 = null;
    soportesHC.value.archivo3 = null;

    /* listarMedicos();
            listarPrioridades(); */
}
async function listarMedicos() {
    try {
        const pathname = String(window?.location?.pathname || "").toLowerCase();
        const moduloId =
            sessionStorage.getItem("ST-modulo") || (pathname.includes("ecografia") ? "2" : "1");
        const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
        medicos.value = res.data.data;
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar médicos.");
        medicos.value = [];
    }
}
async function listarPrioridades() {
    try {
        const res = await axios.get("/api/config-prioridades");
        prioridades.value = res.data.data;
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar prioridades.");
        prioridades.value = [];
    }
}
async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = res.data.data;
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar productos.");
        productos.value = [];
    }
}
async function listarDiagnosticos() {
    try {
        const res = await axios.get("/api/config-diagnosticos");
        diagnosticos.value = res.data.data;
    } catch (error) {
        handleAxiosErrorToast(error, "No fue posible listar diagnósticos.");
        diagnosticos.value = [];
    }
}
function onMedicoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    registro.value.medico_id = selectedId || "";
}
function onProductoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    if (selectedId != null) {
        productoSelecciondo.value = selectedId;
        guardarProductosEstudio();
    }
}
function onDiagnosticoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    if (selectedId != null) {
        diagnosticoSelecciondo.value = selectedId;
        guardarDiagnosticosEstudio();
    }
}
function guardarProductosEstudio() {
    const indexProductoEstudio = productos.value.findIndex(
        (x) => x.id == productoSelecciondo.value
    );

    if (indexProductoEstudio >= 0) {
        const productoEstudioId = productos.value[indexProductoEstudio].id;
        const productoEstudioCod_Cubs = productos.value[indexProductoEstudio].cod_cups;
        const productoEstudioNomProduc = productos.value[indexProductoEstudio].nom_produc;

        const yaExiste = registro.value.productosEstudio.some((p) => p.id == productoEstudioId);
        if (!yaExiste) {
            registro.value.productosEstudio.push({
                id: productoEstudioId,
                cod_cups: productoEstudioCod_Cubs,
                nom_produc: productoEstudioNomProduc,
                checked: false,
            });
        }

        // Limpiar selección
        productoSelecciondo.value = "";
    }
}
function quitarProductoEstudio(indexEliminar) {
    registro.value.productosEstudio.splice(indexEliminar, 1);
}
function guardarDiagnosticosEstudio() {
    const idx = diagnosticos.value.findIndex(
        (x) => String(x.id) === String(diagnosticoSelecciondo.value)
    );
    if (idx >= 0) {
        const diag = diagnosticos.value[idx];
        const yaExiste = registro.value.diagnosticosEstudio.some(
            (d) => String(d.id) === String(diag.id)
        );
        if (!yaExiste) {
            registro.value.diagnosticosEstudio.push({
                id: diag.id,
                cod_diagnos: diag.cod_diagnos,
                nom_diagnos: diag.nom_diagnos,
            });
        }
        // Limpiar selección
        diagnosticoSelecciondo.value = "";
    }
}
function quitarDiagnosticoEstudio(indexEliminar) {
    registro.value.diagnosticosEstudio.splice(indexEliminar, 1);
}
function verImagen(pat_id, study_iuid, index = null) {
    try {
        if (index != null) loadingVerImagenIndex.value = index;
        const base = sessionStorage.getItem("ST-urlsede") || "";
        const params = new URLSearchParams({
            patientID: String(pat_id ?? ""),
            studyUID: String(study_iuid ?? ""),
            serverName: String(sessionStorage.getItem("ST-servername") || ""),
        });
        const url = `${base}?${params.toString()}`;
        window.open(url, "_blank", "noopener,noreferrer");
    } finally {
        loadingVerImagenIndex.value = null;
    }
}

onMounted(() => {
    registro.value.email_reportar = "";
    listarMedicos();
    listarPrioridades();
    listarProductos();
    listarDiagnosticos();
    establecerSede();
    btnLimpiar();

    emitter.on("sedeSeleccionada", (data) => {
        registro.value.sede_id = data.idSede;
        establecerSede();
        safeDestroyDataTable(dt.value || "#example23");
    });
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
    z-index: 180;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 80%;
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

.audio {
    width: 50px;
    height: 5px;
}
</style>
