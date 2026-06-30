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
                                                id="bus_fehc_ini"
                                                name="bus_fehc_ini"
                                                v-model="busqueda.bus_fehc_ini"
                                                class="form-control"
                                            />
                                            <!-- <span class="text-danger" v-if="errores.bus_fehc_ini">
                                                {{ erroresBusqueda.bus_fehc_ini[0] }}
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
                                                :dropdown-parent-selector="'#productos-parent'"
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
                                                :dropdown-parent-selector="'#diagnosticos-parent'"
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

<script>
import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { handleAxiosErrorToast } from "@/helpers/alerts";

export default {
    props: ["usuarioactual"],
    components: {
        FormPaciente,
    },
    mounted() {
        this.registro.email_reportar = "";
        this.listarMedicos();
        this.listarPrioridades();
        this.listarProductos();
        this.listarDiagnosticos();
        this.establecerSede();
        this.limpiar();
        this.emitter.on("sedeSeleccionada", (data) => {
            this.registro.sede_id = data.idSede;
            this.establecerSede();
            safeDestroyDataTable(this.$refs.dt || "#example23");
        });
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dt || "#example23");
    },
    data() {
        return {
            dataIndex: 0,
            id: 0,
            registros: [],
            tituloModal: "Nuevo registro",
            registro: {
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
                sede_id: this.sedeActual,
                quien_registro_id: this.usuarioactual.id,
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
            },
            soportesHC: { archivo1: null, archivo2: null, archivo3: null }, //Soporte de historia clinica
            busqueda: {
                sede_id: this.sedeActual,
                bus_nom_num_docu: "",
                bus_fehc_ini: "",
                bus_fecha_fin: "",
            },
            interfaceAtenciones: [], //Ateniones disponibles del paciente
            interfaceAtencionSeleccionada: null, //Atencion seleccinada para listar los productos y diagnosticos
            errores: {},
            erroresBusqueda: {},
            medicos: [],
            prioridades: [], //Listo las prioridades
            productos: [], //Listo todos los productos
            diagnosticos: [], //Listo todos los diagnosticos
            productoSelecciondo: "",
            diagnosticoSelecciondo: "",
            sedeActual: sessionStorage.getItem("ST-sede"),
            productosAtencion: [],
            sedeInterface: false,
            idRow: null,
            selectedRows: {},
            loadingBuscar: false,
            loadingGuardar: false,
            loadingVerImagenIndex: null,
        };
    },
    computed: {
        diagnosticoOptions() {
            // Adaptar opciones al Select2 local: usa optionValue='id' y labelFields.
            return (this.diagnosticos || []).map((d) => ({
                id: String(d.id),
                nom_diagnos: d.nom_diagnos,
            }));
        },
        productoOptions() {
            // Opciones para el Select2 local de productos.
            return (this.productos || []).map((p) => ({
                id: String(p.id),
                cod_cups: p.cod_cups,
                nom_produc: p.nom_produc,
            }));
        },
        medicoOptions() {
            // Opciones para el Select2 local de medicos.
            return (this.medicos || []).map((p) => ({ id: String(p.id), name: p.name }));
        },
    },
    methods: {
        async buscarStudy() {
            safeDestroyDataTable(this.$refs.dt || "#example23");

            try {
                if (this.loadingBuscar) return;
                this.loadingBuscar = true;
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
                    this.busqueda.bus_nom_num_docu == "" &&
                    this.busqueda.bus_fehc_ini == "" &&
                    this.busqueda.bus_fecha_fin == ""
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
                        (this.busqueda.bus_fehc_ini && !this.busqueda.bus_fecha_fin) ||
                        (!this.busqueda.bus_fehc_ini && this.busqueda.bus_fecha_fin)
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
                        this.busqueda.bus_fehc_ini &&
                        this.busqueda.bus_fecha_fin &&
                        this.busqueda.bus_fehc_ini > this.busqueda.bus_fecha_fin
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
                    const res = await axios.post("/api/study.listarEstudios", this.busqueda);

                    if (res.status == 200) {
                        safeDestroyDataTable(this.$refs.dt || "#example23");

                        this.registros = res.data;
                        this.$nextTick(() => {
                            safeInitDataTable(this.$refs.dt || "#example23", {
                                dom: "Bfrtip",
                                buttons: ["copy", "csv", "excel", "pdf", "print"],
                            });
                        });
                    } else if (res.status == 500) {
                        $.toast({
                            heading: "Upsss!!!",
                            text: res.data.message,
                            position: "top-right",
                            loaderBg: "#ff6849",
                            icon: "danger",
                            hideAfter: 3500,
                            stack: 6,
                        });
                    }
                }
            } catch (error) {
                this.erroresBusqueda = error?.response?.data?.errors || {};
                handleAxiosErrorToast(error, "No fue posible listar estudios.");
            } finally {
                this.loadingBuscar = false;
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {
                const formData = new FormData();
                formData.append("registro", JSON.stringify(this.registro));
                formData.append("archivo1", this.soportesHC.archivo1);
                formData.append("archivo2", this.soportesHC.archivo2);
                formData.append("archivo3", this.soportesHC.archivo3);

                const res = await axios.post("/api/estudios", formData, {
                    headers: { "Content-Type": "multipart/form-data" },
                });

                if (res.status == 200 || res.status === 201) {
                    $.toast({
                        heading: "Ok!!!",
                        text: res.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });

                    // Actualiza el valor de `agendado` para que se refleje el cambio de clase
                    this.registros[this.idRow].agendado = 1; // O el valor que necesites asignar
                    this.selectedRows[this.idRow] = !this.selectedRows[this.idRow];
                }

                /* this.btnCerralModalForm();
                this.limpiar(); */
            } catch (error) {
                this.errores = error?.response?.data?.errors || error?.response?.data || {};
                handleAxiosErrorToast(error, "No fue posible guardar el registro.");
            } finally {
                this.loadingGuardar = false;
            }
        },
        obtenerArchivo1(e) {
            this.soportesHC.archivo1 = e.target.files[0];
        },
        obtenerArchivo2(e) {
            this.soportesHC.archivo2 = e.target.files[0];
        },
        obtenerArchivo3(e) {
            this.soportesHC.archivo3 = e.target.files[0];
        },
        async mostrarRegistro(data = {}, index) {
            this.limpiar();
            this.tituloModal = "Agendar al paciente: " + data.pat_name;
            this.id = data.study_pk;
            this.registro.study_pk = data.study_pk;
            this.registro.study_iuid = data.study_iuid;
            this.registro.study_id = data.study_id;
            this.registro.study_datetime = data.study_datetime;
            this.registro.accession_no = data.accession_no;
            this.registro.study_desc = data.study_desc;
            this.registro.mods_in_study = data.mods_in_study;
            this.registro.email = "";
            this.registro.email_reportar = sessionStorage.getItem("ST-email_repor");
            this.registro.num_docu = data.pat_id;
            this.registro.nombres = data.pat_name;
            this.registro.sexo = data.pat_sex;
            this.registro.fec_naci = data.pat_birthdate;
            this.idRow = index;

            //this.btnCerralModalForm();
            this.abrirModal();

            /**
             * Verificar si la sede tiene interface, traigo la informacion de las producto y diagnosticos
             */
            const res = await axios.get("/api/config-sedes/" + this.sedeActual + "/buscarPorId");
            if (res.data.interface_software != null) {
                const resAtenciones = await axios.get(
                    "/api/interface-sahi-listar-atenciones/" + this.registro.num_docu
                );
                this.interfaceAtenciones = resAtenciones.data;
            }
        },
        async interfaceListarProductosDiagnosticos() {
            /**
             * Listo los productos de una atención
             */
            const resProuctos = await axios.get(
                "/api/interface-sahi-listar-listarProductosDiagnosticos/" +
                    this.interfaceAtencionSeleccionada
            );

            this.registro.productosEstudio = [];
            if (resProuctos.data.productos.length > 0) {
                resProuctos.data.productos.forEach((element) => {
                    this.registro.productosEstudio.push({
                        checked: false,
                        id: element.IdProducto,
                        cod_cups: element.cod_cups,
                        nom_produc: element.nom_produc,
                    });
                });
            }

            this.registro.diagnosticosEstudio = [];
            if (resProuctos.data.diagnosticos.length > 0) {
                this.registro.diagnosticosEstudio = resProuctos.data.diagnosticos;
            }

            this.registro.atencion_id = this.interfaceAtencionSeleccionada;
        },
        async establecerSede() {
            this.registro.sede_id = sessionStorage.getItem("ST-sede");
            this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
            this.sedeActual = sessionStorage.getItem("ST-sede");

            try {
                const res = await axios.get(`/config-sedes/${this.sedeActual}/buscarPorId`);
                this.sedeInterface = res?.data?.interface_software === "SAHI";
            } catch (error) {
                this.sedeInterface = false;
            }
            this.registros = [];
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
        // initProductosSelect2 ya no es necesario con el wrapper Select2 local.
        abrirModal() {
            $(".right-sidebar").addClass("shw-rside");
        },
        cerrarModal() {
            $(".right-sidebar").removeClass("shw-rside");
        },
        limpiar() {
            this.registro.study_pk = "";
            this.registro.study_iuid = "";
            this.registro.study_id = "";
            this.registro.study_datetime = "";
            this.registro.accession_no = "";
            this.registro.study_desc = "";
            this.registro.mods_in_study = "";
            this.registro.email_reportar = "";

            this.registro.num_docu = "";
            this.registro.nombres = "";
            this.registro.sexo = "";
            this.registro.email = "";
            this.registro.fec_naci = "";

            this.registro.diagnosticosEstudio.splice(0, this.registro.diagnosticosEstudio.length);
            this.registro.productosEstudio.splice(0, this.registro.productosEstudio.length);

            /* this.registro.medico_id = 0;
            this.registro.prioridad_id = 0; */
            this.productoSelecciondo = 0;
            this.diagnosticoSelecciondo = 0;

            this.soportesHC.archivo1 = null;
            this.soportesHC.archivo2 = null;
            this.soportesHC.archivo3 = null;

            /* this.listarMedicos();
            this.listarPrioridades(); */
        },
        async listarMedicos() {
            try {
                const pathname = String(window?.location?.pathname || "").toLowerCase();
                const moduloId =
                    sessionStorage.getItem("ST-modulo") ||
                    (pathname.includes("ecografia") ? "2" : "1");
                const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
                this.medicos = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar médicos.");
                this.medicos = [];
            }
        },
        async listarPrioridades() {
            try {
                const res = await axios.get("/api/config-prioridades");
                this.prioridades = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar prioridades.");
                this.prioridades = [];
            }
        },
        async listarProductos() {
            try {
                const res = await axios.get("/api/config-productos");
                this.productos = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar productos.");
                this.productos = [];
            }
        },
        async listarDiagnosticos() {
            try {
                const res = await axios.get("/api/config-diagnosticos");
                this.diagnosticos = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar diagnósticos.");
                this.diagnosticos = [];
            }
        },
        onMedicoSelect(value) {
            let selectedId = null;
            if (Array.isArray(value) && value.length > 0) {
                const v = value[0];
                selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
            } else if (value && typeof value === "object" && value.id != null) {
                selectedId = String(value.id);
            } else if (value != null) {
                selectedId = String(value);
            }
            this.registro.medico_id = selectedId || "";
        },
        onProductoSelect(value) {
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
                this.productoSelecciondo = selectedId;
                this.guardarProductosEstudio();
            }
        },
        onDiagnosticoSelect(value) {
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
                this.diagnosticoSelecciondo = selectedId;
                this.guardarDiagnosticosEstudio();
            }
        },
        guardarProductosEstudio() {
            const indexProductoEstudio = this.productos.findIndex(
                (x) => x.id == this.productoSelecciondo
            );

            if (indexProductoEstudio >= 0) {
                const productoEstudioId = this.productos[indexProductoEstudio].id;
                const productoEstudioCod_Cubs = this.productos[indexProductoEstudio].cod_cups;
                const productoEstudioNomProduc = this.productos[indexProductoEstudio].nom_produc;

                const yaExiste = this.registro.productosEstudio.some(
                    (p) => p.id == productoEstudioId
                );
                if (!yaExiste) {
                    this.registro.productosEstudio.push({
                        id: productoEstudioId,
                        cod_cups: productoEstudioCod_Cubs,
                        nom_produc: productoEstudioNomProduc,
                        checked: false,
                    });
                }

                // Limpiar selección
                this.productoSelecciondo = "";
            }
        },
        quitarProductoEstudio(indexEliminar) {
            this.registro.productosEstudio.splice(indexEliminar, 1);
        },
        guardarDiagnosticosEstudio() {
            const idx = this.diagnosticos.findIndex(
                (x) => String(x.id) === String(this.diagnosticoSelecciondo)
            );
            if (idx >= 0) {
                const diag = this.diagnosticos[idx];
                const yaExiste = this.registro.diagnosticosEstudio.some(
                    (d) => String(d.id) === String(diag.id)
                );
                if (!yaExiste) {
                    this.registro.diagnosticosEstudio.push({
                        id: diag.id,
                        cod_diagnos: diag.cod_diagnos,
                        nom_diagnos: diag.nom_diagnos,
                    });
                }
                // Limpiar selección
                this.diagnosticoSelecciondo = "";
            }
        },
        quitarDiagnosticoEstudio(indexEliminar) {
            this.registro.diagnosticosEstudio.splice(indexEliminar, 1);
        },
        verImagen(pat_id, study_iuid, index = null) {
            try {
                if (index != null) this.loadingVerImagenIndex = index;
                const base = sessionStorage.getItem("ST-urlsede") || "";
                const params = new URLSearchParams({
                    patientID: String(pat_id ?? ""),
                    studyUID: String(study_iuid ?? ""),
                    serverName: String(sessionStorage.getItem("ST-servername") || ""),
                });
                const url = `${base}?${params.toString()}`;
                window.open(url, "_blank", "noopener,noreferrer");
            } finally {
                this.loadingVerImagenIndex = null;
            }
        },
    },
};
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
