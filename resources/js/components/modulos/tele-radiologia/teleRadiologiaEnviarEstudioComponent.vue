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
                    <li class="breadcrumb-item active">Tele radiologia</li>
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
                                        @click="buscarStudy()"
                                        :disabled="loadingBuscar"
                                    >
                                        <i v-if="loadingBuscar" class="fa fa-spinner fa-spin"></i>
                                        <i v-else class="fa fa-search"></i> Buscar
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
                                        :key="item.study_pk"
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
                                                :disabled="loadingAbrirIndex === index"
                                                @click="mostrarRegistro(item, index)"
                                                title="Ordenar estudio"
                                            >
                                                <i
                                                    v-if="loadingAbrirIndex === index"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-child"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                :disabled="loadingImagenIndex === index"
                                                @click="
                                                    verImagen(item.pat_id, item.study_iuid, index)
                                                "
                                                title="Ver imagenes"
                                            >
                                                <i
                                                    v-if="loadingImagenIndex === index"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-file-photo-o"></i>
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
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Paciente
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label"># Documento</label>
                                                <input
                                                    type="text"
                                                    id="num_docu"
                                                    name="num_docu"
                                                    v-model="orden.paciente.num_docu"
                                                    class="form-control"
                                                    placeholder="# de Documento"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Nombres</label>
                                                <input
                                                    type="text"
                                                    id="nombres"
                                                    name="nombres"
                                                    v-model="orden.paciente.nom_pacien"
                                                    class="form-control"
                                                    placeholder="Nombres"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Sexo</label>
                                                <input
                                                    type="text"
                                                    id="sexo"
                                                    name="sexo"
                                                    v-model="orden.paciente.sexo"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Fec. Nacimi</label>
                                                <input
                                                    type="date"
                                                    id="fec_naci"
                                                    name="fec_naci"
                                                    v-model="orden.paciente.fec_naci"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Dirección</label>
                                                <input
                                                    type="text"
                                                    id="direccion"
                                                    name="direccion"
                                                    v-model="orden.paciente.direccion"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Télefono</label>
                                                <input
                                                    type="number"
                                                    id="telefono"
                                                    name="telefono"
                                                    v-model="orden.paciente.tel"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">E - Mail</label>
                                                <input
                                                    type="email"
                                                    id="email"
                                                    name="email"
                                                    v-model="orden.paciente.email"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Datos del Estudio
                                    </h5>
                                    <hr />

                                    <input
                                        type="hidden"
                                        id="study_pk"
                                        name="study_pk"
                                        v-model="orden.study_pk"
                                    />
                                    <input
                                        type="hidden"
                                        id="study_iuid"
                                        name="study_iuid"
                                        v-model="orden.study_iuid"
                                    />
                                    <input
                                        type="hidden"
                                        id="study_id"
                                        name="study_id"
                                        v-model="orden.study_id"
                                    />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Descripción</label>
                                                <input
                                                    type="text"
                                                    id="study_desc"
                                                    name="study_desc"
                                                    v-model="orden.study_desc"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Detalle</label>
                                                <input
                                                    type="text"
                                                    id="accession_no"
                                                    name="accession_no"
                                                    v-model="orden.accession_no"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Fecha</label>
                                                <input
                                                    type="text"
                                                    id="study_datetime"
                                                    name="study_datetime"
                                                    v-model="orden.fec_estudio"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Observaciones</label>
                                                <input
                                                    type="text"
                                                    id="observaciones"
                                                    name="observaciones"
                                                    v-model="orden.observaciones"
                                                    class="form-control"
                                                    placeholder="Ingrese aqi las observaciones del estudio"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Adjunto
                                    </h5>
                                    <hr />

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    id="exampleInputFile1"
                                                    aria-describedby="fileHelp"
                                                    @change="obtenerArchivo1"
                                                    accept=".pdf,.doc,.docx"
                                                />
                                            </div>
                                            <div class="form-group">
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    id="exampleInputFile2"
                                                    aria-describedby="fileHelp"
                                                    @change="obtenerArchivo2"
                                                    accept=".pdf,.doc,.docx"
                                                />
                                            </div>
                                            <div class="form-group">
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    id="exampleInputFile3"
                                                    aria-describedby="fileHelp"
                                                    @change="obtenerArchivo3"
                                                    accept=".pdf,.doc,.docx"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos de la Asignacion
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Prioridad</label>
                                                <select
                                                    v-model="orden.priori.id"
                                                    name="prioridad_id"
                                                    id="prioridad_id"
                                                    class="form-control custom-select"
                                                    @change="establecerPrioridad"
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
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
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
                                                        v-for="(item, index) in productosInterface"
                                                        :key="item.id"
                                                    >
                                                        <td>
                                                            <input
                                                                type="checkbox"
                                                                class="iCheck-helper"
                                                                :id="`flat-checkbox-producto-interface-${index}`"
                                                                data-checkbox="icheckbox_flat-red"
                                                                :checked="
                                                                    orden.producto.some(
                                                                        (p) =>
                                                                            p.cod_cups ==
                                                                            item.cod_cups
                                                                    )
                                                                "
                                                                @change="
                                                                    guardarProductosEstudio(
                                                                        item.id,
                                                                        item.cod_cups,
                                                                        item.nom_produc
                                                                    )
                                                                "
                                                            />
                                                            <label
                                                                :for="`flat-checkbox-producto-interface-${index}`"
                                                            ></label>
                                                        </td>
                                                        <td>{{ item.cod_cups }}</td>
                                                        <td>{{ item.nom_produc }}</td>
                                                        <td class="text-nowrap">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="
                                                                    quitarProductoEstudio(
                                                                        item.cod_cups
                                                                    )
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

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-pencil"></i> MOTIVO DEL EXAMEN
                                    </h5>

                                    <hr />

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <textarea
                                                    id="motivo_examen"
                                                    name="motivo_examen"
                                                    v-model="orden.motivo_examen"
                                                    class="form-control"
                                                    placeholder="Ingrese aqi las observaciones del estudio"
                                                    rows="11"
                                                ></textarea>
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
                                    <i v-else class="fa fa-check"></i> Guardar
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

import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { handleAxiosErrorToast } from "@/helpers/alerts";
import axios from "axios";

const props = defineProps({ usuarioactual: Object });

const dt = ref(null);

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
    destino_estudio: "Tele Radiologia",
    destino_estudio_id: null,
    motivo_examen: null,
});
const orden = ref({
    study_pk: "",
    study_iuid: "",
    fec_estudio: "",
    accession_no: "",
    study_desc: "",
    mods_in_study: "",
    observaciones: "",
    paciente: {
        tipo_docu: "CC",
        num_docu: "",
        nom_pacien: "",
        fec_naci: "",
        sexo: "",
        tel: "",
        telefono: "",
        email: "",
        direccion: "",
    },
    priori: {
        id: "",
        nom_priori: "",
    },
    sede: {
        id: idSedeActual,
        nom_sede: nomSedeActual,
    },
    producto: [],
    adjuntos: [],
    motivo_examen: null,
});
const soportesHC = ref({ archivo1: null, archivo2: null, archivo3: null });
const busqueda = ref({
    sede_id: sedeActual,
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});
const sedeInterface = ref(false);
const interfaceAtenciones = ref([]);
const interfaceAtencionSeleccionada = ref(null);
const errores = ref({});
const erroresBusqueda = ref({});
const prioridades = ref([]);
const productos = ref([]);
const productosInterface = ref([]);
const productoSelecciondo = ref("");
const productoSelecciondoOrder = ref([]);
const idSedeActual = ref("");
const nomSedeActual = ref("");
const productosAtencion = ref([]);
const idRow = ref(null);
const selectedRows = ref({});
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingAbrirIndex = ref(null);
const loadingImagenIndex = ref(null);

const diagnosticoOptions = computed(() => {
    return (diagnosticos || []).map((d) => ({
        id: String(d.id),
        nom_diagnos: d.nom_diagnos,
    }));
});
const productoOptions = computed(() => {
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});
const medicoOptions = computed(() => {
    return (medicos || []).map((p) => ({ id: String(p.id), name: p.name }));
});

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
async function buscarStudy() {
    if (loadingBuscar.value) return;
    loadingBuscar.value = true;
    destroyDataTable();
    try {
        if (sessionStorage.getItem("ST-sede") == null) {
            $.toast({
                heading: "Upsss!!!",
                text: "Debes seleccionar una sede para realizar la busqueda.",
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
            // En Tele-radiología consultamos estudios vía endpoint del módulo
            const res = await axios.post("/api/tele-radiologia-listar-estudios", busqueda.value);
            if (res.status == 200) {
                destroyDataTable();

                registros.value = res.data.data;
                nextTick(() => {
                    initDataTable();
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
        if (error.response && error.response.status == 500) {
            $.toast({
                heading: "Upss!!!",
                text: error.response.data.message,
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "error",
                hideAfter: 3500,
                stack: 6,
            });
        }
        if (error.response && error.response.data && error.response.data.errors) {
            erroresBusqueda.value = error.response.data.errors;
        }
        handleAxiosErrorToast(error, "No fue posible listar estudios.");
    } finally {
        loadingBuscar.value = false;
    }
}
async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (orden.value.producto.length === 0) {
            $.toast({
                heading: "Upsss!!!",
                text: "Debes seleccional al menos un producto",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "warning",
                hideAfter: 5500,
                stack: 6,
            });

            return;
        }

        if (orden.value.priori.id === "") {
            $.toast({
                heading: "Upsss!!!",
                text: "Debes seleccional la prioridad de la orden",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "warning",
                hideAfter: 5500,
                stack: 6,
            });

            return;
        }

        // Mantener compatibilidad con backend externo: duplicar tel/telefono
        orden.value.paciente.telefono = orden.value.paciente.tel;
        registro.value.tel = orden.value.paciente.tel;
        registro.value.direccion = orden.value.paciente.direccion;
        registro.value.email = orden.value.paciente.email;
        registro.value.motivo_examen = orden.value.motivo_examen;

        const resOrdenTele = await axios.post("/api/tele-radiologia-enviar-orden", {
            orden: orden.value,
        });

        const confirmation =
            resOrdenTele?.data?.confirmation ??
            resOrdenTele?.data?.data?.confirmation ??
            resOrdenTele?.data?.id ??
            null;

        if (resOrdenTele.status == 201 || resOrdenTele.status == 200) {
            //Asigno el id de la orden de tele radiologia al estudio
            registro.value.destino_estudio_id = confirmation;

            $.toast({
                heading: "Ok!!!",
                text: resOrdenTele?.data?.details || "Orden enviada correctamente.",
                position: "top-right",
                loaderBg: "#ff6849",
                icon: "success",
                hideAfter: 3500,
                stack: 6,
            });

            const formData = new FormData();
            formData.append("registro", JSON.stringify(registro.value));
            if (soportesHC.value.archivo1) formData.append("archivo1", soportesHC.value.archivo1);
            if (soportesHC.value.archivo2) formData.append("archivo2", soportesHC.value.archivo2);
            if (soportesHC.value.archivo3) formData.append("archivo3", soportesHC.value.archivo3);

            const resEstudio = await axios.post("/api/estudios", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            if (resEstudio.status == 200) {
                $.toast({
                    heading: "Ok!!!",
                    text: resEstudio.data.message,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });
            }
        }

        btnCerralModalForm();
        btnLimpiar();
    } catch (error) {
        if (error.response) {
            errores.value = error.response.data;
        }
        handleAxiosErrorToast(error, "No fue posible guardar/enviar el estudio.");
    } finally {
        loadingGuardar.value = false;
    }
}
function toBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = (error) => reject(error);
    });
}
function extensionDesdeBase64(base64) {
    const mime = base64.substring(base64.indexOf(":") + 1, base64.indexOf(";"));

    const mapa = {
        "image/jpeg": "jpg",
        "image/png": "png",
        "application/pdf": "pdf",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document": "docx",
        "application/msword": "doc",
    };

    return mapa[mime] ?? null;
}
async function obtenerArchivo1(e) {
    const file = e.target.files[0];
    const fileBase64 = await toBase64(file);
    const extension = extensionDesdeBase64(fileBase64);
    agregarArchivoOrden(fileBase64, extension);
    soportesHC.value.archivo1 = e.target.files[0];
}
async function obtenerArchivo2(e) {
    const file = e.target.files[0];
    const fileBase64 = await toBase64(file);
    const extension = extensionDesdeBase64(fileBase64);
    agregarArchivoOrden(fileBase64, extension);
    soportesHC.value.archivo2 = e.target.files[0];
}
async function obtenerArchivo3(e) {
    const file = e.target.files[0];
    const fileBase64 = await toBase64(file);
    const extension = extensionDesdeBase64(fileBase64);
    agregarArchivoOrden(fileBase64, extension);
    soportesHC.value.archivo3 = e.target.files[0];
}
async function agregarArchivoOrden(file, file_exten) {
    orden.value.adjuntos.push({
        file: file,
        file_exten: file_exten,
    });
}
async function mostrarRegistro(data = {}, index) {
    limpiar();
    tituloModal.value = "Generar orden: " + data.pat_name;
    orden.value.study_pk = data.study_pk;
    orden.value.study_iuid = data.study_iuid;
    orden.value.fec_estudio = data.study_datetime;
    orden.value.accession_no = data.accession_no;
    orden.value.study_desc = data.study_desc;
    orden.value.mods_in_study = data.mods_in_study;
    orden.value.paciente.num_docu = data.pat_id;
    orden.value.paciente.nom_pacien = data.pat_name;
    orden.value.paciente.sexo = data.pat_sex;
    orden.value.paciente.fec_naci = data.pat_birthdate;

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

    abrirModal();

    /**
     * Verificar si la sede tiene interface, traigo la informacion de las producto y diagnosticos
     */
    const res = await axios.get("/api/config-sedes/" + idSedeActual.value + "/buscarPorId");
    sedeInterface.value = res.data.data.interface_software != null;
    if (sedeInterface.value) {
        const resAtenciones = await axios.get(
            "/api/interface-sahi-listar-atenciones/" + orden.value.paciente.num_docu
        );
        interfaceAtenciones.value = resAtenciones.data.data;
    }
}
async function interfaceListarProductosDiagnosticos() {
    const resProuctos = await axios.get(
        "/api/interface-sahi-listar-listarProductosDiagnosticos/" +
            interfaceAtencionSeleccionada.value
    );
    const data = resProuctos.data.data;
    productosInterface.value = data.productos;

    let EnfermedadActualDiagnosticos = "";

    const consultas = data.consultas || [];
    EnfermedadActualDiagnosticos = "Enfermedad Actual: ";
    if (consultas.length > 0) {
        consultas.forEach((c) => {
            EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos}\n ${c.DesEnfermedadActual} \n`;
        });
    }

    const diagnosticos = data.diagnosticos || [];
    EnfermedadActualDiagnosticos += `\nDiagnosticos:`;
    if (diagnosticos.length > 0) {
        diagnosticos.forEach((d) => {
            EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos} \n ${d.cod_diagnos} - ${d.nom_diagnos}`;
        });
    }

    orden.value.motivo_examen = EnfermedadActualDiagnosticos;
    registro.value.motivo_examen = EnfermedadActualDiagnosticos;

    registro.value.diagnosticosEstudio = [];
    if (diagnosticos.length > 0) {
        registro.value.diagnosticosEstudio = diagnosticos;
    }

    registro.value.atencion_id = interfaceAtencionSeleccionada.value;
}
function establecerPrioridad(e) {
    const seleccion = prioridades.value.find((p) => p.id === orden.value.priori.id);

    orden.value.priori.id = seleccion.id;
    registro.value.prioridad_id = seleccion.id;
    orden.value.priori.nom_priori = seleccion.nom_priori;
}
async function establecerSede() {
    orden.value.sede.id = sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    idSedeActual.value = sessionStorage.getItem("ST-sede");
    nomSedeActual.value = sessionStorage.getItem("ST-nomsede");
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    await establecerSede();
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
    orden.value.study_pk = "";
    orden.value.study_iuid = "";
    orden.value.study_id = "";
    orden.value.fec_estudio = "";
    orden.value.accession_no = "";
    orden.value.study_desc = "";
    orden.value.mods_in_study = "";
    orden.value.observaciones = "";

    orden.value.paciente.num_docu = "";
    orden.value.paciente.nom_pacien = "";
    orden.value.paciente.sexo = "";
    orden.value.paciente.fec_naci = "";

    orden.value.priori.id = "";

    productoSelecciondo.value = 0;

    orden.value.producto = [];
    orden.value.adjuntos = [];
    productosInterface.value = [];
    diagnosticosInterface = [];
    interfaceAtenciones.value = [];
    registro.value.diagnosticosEstudio = [];
    registro.value.productosEstudio = [];
    registro.value.atencion_id = null;
    registro.value.destino_estudio_id = null;
    registro.value.destino_estudio = "Tele Radiologia";

    soportesHC.value.archivo1 = null;
    soportesHC.value.archivo2 = null;
    soportesHC.value.archivo3 = null;
}
async function listarPrioridades() {
    try {
        const res = await axios.get("/api/config-prioridades");
        prioridades.value = res.data.data;
    } catch (error) {
        prioridades.value = [];
        handleAxiosErrorToast(error, "No fue posible listar prioridades.");
    }
}
async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = res.data.data;
    } catch (error) {
        productos.value = [];
        handleAxiosErrorToast(error, "No fue posible listar productos.");
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
    orden.value.medico.id = selectedId || "";
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
        const prod = productos.value.find((p) => String(p.id) === selectedId);
        if (prod) {
            guardarProductosEstudio(prod.id, prod.cod_cups, prod.nom_produc);
        }
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
        diagnosticoSelecciondo = selectedId;
        guardarDiagnosticosEstudio();
    }
}
function guardarProductosEstudio(id, cod_cups, nom_produc) {
    const productoEstudioId = id;
    const productoEstudioCod_Cubs = cod_cups;
    const productoEstudioNomProduc = nom_produc;

    const yaExisteIndex = orden.value.producto.findIndex(
        (p) => p.cod_cups == productoEstudioCod_Cubs
    );
    if (yaExisteIndex !== -1) {
        // Si ya existe, quitarlo
        orden.value.producto.splice(yaExisteIndex, 1);
        // También quitarlo de registro.productosEstudio
        const registroIndex = registro.value.productosEstudio.findIndex(
            (p) => p.cod_cups == productoEstudioCod_Cubs
        );
        if (registroIndex !== -1) {
            registro.value.productosEstudio.splice(registroIndex, 1);
        }
    } else {
        // Si no existe, agregarlo
        orden.value.producto.push({
            cod_cups: productoEstudioCod_Cubs,
            nom_produc: productoEstudioNomProduc,
        });

        registro.value.productosEstudio.push({
            checked: true,
            id: productoEstudioId,
            cod_cups: productoEstudioCod_Cubs,
            nom_produc: productoEstudioNomProduc,
        });
    }

    productoSelecciondo.value = "";
}
function quitarProductoEstudio(codCups) {
    const idxOrden = orden.value.producto.findIndex((p) => p.cod_cups === codCups);
    if (idxOrden !== -1) orden.value.producto.splice(idxOrden, 1);
    const idxRegistro = registro.value.productosEstudio.findIndex((p) => p.cod_cups === codCups);
    if (idxRegistro !== -1) registro.value.productosEstudio.splice(idxRegistro, 1);
}
function verImagen(pat_id, study_iuid, index = null) {
    if (index !== null) {
        if (loadingImagenIndex.value !== null) return;
        loadingImagenIndex.value = index;
    }
    const base = sessionStorage.getItem("ST-urlsede") || "";
    const params = new URLSearchParams({
        patientID: String(pat_id ?? ""),
        studyUID: String(study_iuid ?? ""),
        serverName: String(sessionStorage.getItem("ST-servername") ?? ""),
    });
    try {
        window.open(`${base}?${params.toString()}`, "_blank", "noopener,noreferrer");
    } finally {
        setTimeout(() => {
            loadingImagenIndex.value = null;
        }, 500);
    }
}

onMounted(() => {
    listarPrioridades();
    listarProductos();
    establecerSede();
    limpiar();

    emitter.on("sedeSeleccionada", (data) => {
        orden.value.sede.id = data.idSede;
        establecerSede();
        destroyDataTable();
    });
});

onBeforeUnmount(() => {
    destroyDataTable();
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
