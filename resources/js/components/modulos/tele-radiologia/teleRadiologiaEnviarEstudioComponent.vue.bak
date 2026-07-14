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
                                                id="bus_fehc_ini"
                                                name="bus_fehc_ini"
                                                v-model="busqueda.bus_fehc_ini"
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

<script>
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { handleAxiosErrorToast } from "@/helpers/alerts";
import { useSede } from "@/composables/useSede";

export default {
    props: ["usuarioactual"],
    setup() {
        const { sedeInterface, establecerSede } = useSede();
        return { sedeInterface, establecerSede };
    },
    mounted() {
        this.listarPrioridades();
        this.listarProductos();
        this.establecerSede();
        this.limpiar();

        this.emitter.on("sedeSeleccionada", (data) => {
            this.orden.sede.id = data.idSede;
            this.establecerSede();
            this.destroyDataTable();
        });
    },
    beforeUnmount() {
        this.destroyDataTable();
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
                destino_estudio: "Tele Radiologia",
                destino_estudio_id: null,
                motivo_examen: null,
            },
            orden: {
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
                    id: this.idSedeActual,
                    nom_sede: this.nomSedeActual,
                },
                producto: [],
                adjuntos: [],
                motivo_examen: null,
            },
            soportesHC: { archivo1: null, archivo2: null, archivo3: null },
            busqueda: {
                sede_id: this.sedeActual,
                bus_nom_num_docu: "",
                bus_fehc_ini: "",
                bus_fecha_fin: "",
            },
            interfaceAtenciones: [],
            interfaceAtencionSeleccionada: null,
            errores: {},
            erroresBusqueda: {},
            prioridades: [],
            productos: [],
            productosInterface: [], //Productos que se traen de la interface de la sede
            productoSelecciondo: "",
            productoSelecciondoOrder: [], //Productos que se seleccionan para la orden
            idSedeActual: "",
            nomSedeActual: "",
            productosAtencion: [],
            idRow: null,
            selectedRows: {},
            loadingBuscar: false,
            loadingGuardar: false,
            loadingAbrirIndex: null,
            loadingImagenIndex: null,
        };
    },
    computed: {
        diagnosticoOptions() {
            return (this.diagnosticos || []).map((d) => ({
                id: String(d.id),
                nom_diagnos: d.nom_diagnos,
            }));
        },
        productoOptions() {
            return (this.productos || []).map((p) => ({
                id: String(p.id),
                cod_cups: p.cod_cups,
                nom_produc: p.nom_produc,
            }));
        },
        medicoOptions() {
            return (this.medicos || []).map((p) => ({ id: String(p.id), name: p.name }));
        },
    },
    methods: {
        initDataTable() {
            safeInitDataTable(this.$refs.dt || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
                retrieve: true,
            });
        },
        destroyDataTable() {
            safeDestroyDataTable(this.$refs.dt || "#example23");
        },
        async buscarStudy() {
            if (this.loadingBuscar) return;
            this.loadingBuscar = true;
            this.destroyDataTable();
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
                    // En Tele-radiología consultamos estudios vía endpoint del módulo
                    const res = await axios.post(
                        "/api/tele-radiologia-listar-estudios",
                        this.busqueda
                    );
                    if (res.status == 200) {
                        this.destroyDataTable();

                        this.registros = res.data;
                        this.$nextTick(() => {
                            this.initDataTable();
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
                    this.erroresBusqueda = error.response.data.errors;
                }
                handleAxiosErrorToast(error, "No fue posible listar estudios.");
            } finally {
                this.loadingBuscar = false;
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {
                if (this.orden.producto.length === 0) {
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

                if (this.orden.priori.id === "") {
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
                this.orden.paciente.telefono = this.orden.paciente.tel;
                this.registro.tel = this.orden.paciente.tel;
                this.registro.direccion = this.orden.paciente.direccion;
                this.registro.email = this.orden.paciente.email;
                this.registro.motivo_examen = this.orden.motivo_examen;

                const resOrdenTele = await axios.post("/api/tele-radiologia-enviar-orden", {
                    orden: this.orden,
                });

                const confirmation =
                    resOrdenTele?.data?.confirmation ??
                    resOrdenTele?.data?.data?.confirmation ??
                    resOrdenTele?.data?.id ??
                    null;

                if (resOrdenTele.status == 201 || resOrdenTele.status == 200) {
                    //Asigno el id de la orden de tele radiologia al estudio
                    this.registro.destino_estudio_id = confirmation;

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
                    formData.append("registro", JSON.stringify(this.registro));
                    formData.append("archivo1", this.soportesHC.archivo1);
                    formData.append("archivo2", this.soportesHC.archivo2);
                    formData.append("archivo3", this.soportesHC.archivo3);

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

                this.btnCerralModalForm();
                this.limpiar();
            } catch (error) {
                if (error.response) {
                    this.errores = error.response.data;
                }
                handleAxiosErrorToast(error, "No fue posible guardar/enviar el estudio.");
            } finally {
                this.loadingGuardar = false;
            }
        },
        toBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => resolve(reader.result);
                reader.onerror = (error) => reject(error);
            });
        },
        extensionDesdeBase64(base64) {
            const mime = base64.substring(base64.indexOf(":") + 1, base64.indexOf(";"));

            const mapa = {
                "image/jpeg": "jpg",
                "image/png": "png",
                "application/pdf": "pdf",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document": "docx",
                "application/msword": "doc",
            };

            return mapa[mime] ?? null;
        },
        async obtenerArchivo1(e) {
            const file = e.target.files[0];
            const fileBase64 = await this.toBase64(file);
            const extension = this.extensionDesdeBase64(fileBase64);
            this.agregarArchivoOrden(fileBase64, extension);
            this.soportesHC.archivo1 = e.target.files[0];
        },
        async obtenerArchivo2(e) {
            const file = e.target.files[0];
            const fileBase64 = await this.toBase64(file);
            const extension = this.extensionDesdeBase64(fileBase64);
            this.agregarArchivoOrden(fileBase64, extension);
            this.soportesHC.archivo2 = e.target.files[0];
        },
        async obtenerArchivo3(e) {
            const file = e.target.files[0];
            const fileBase64 = await this.toBase64(file);
            const extension = this.extensionDesdeBase64(fileBase64);
            this.agregarArchivoOrden(fileBase64, extension);
            this.soportesHC.archivo3 = e.target.files[0];
        },
        async agregarArchivoOrden(file, file_exten) {
            this.orden.adjuntos.push({
                file: file,
                file_exten: file_exten,
            });
        },
        async mostrarRegistro(data = {}, index) {
            this.limpiar();
            this.tituloModal = "Generar orden: " + data.pat_name;
            this.orden.study_pk = data.study_pk;
            this.orden.study_iuid = data.study_iuid;
            this.orden.fec_estudio = data.study_datetime;
            this.orden.accession_no = data.accession_no;
            this.orden.study_desc = data.study_desc;
            this.orden.mods_in_study = data.mods_in_study;
            this.orden.paciente.num_docu = data.pat_id;
            this.orden.paciente.nom_pacien = data.pat_name;
            this.orden.paciente.sexo = data.pat_sex;
            this.orden.paciente.fec_naci = data.pat_birthdate;

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

            this.abrirModal();

            /**
             * Verificar si la sede tiene interface, traigo la informacion de las producto y diagnosticos
             */
            const res = await axios.get("/api/config-sedes/" + this.idSedeActual + "/buscarPorId");
            if (res.data.interface_software != null) {
                const resAtenciones = await axios.get(
                    "/api/interface-sahi-listar-atenciones/" + this.orden.paciente.num_docu
                );
                this.interfaceAtenciones = resAtenciones.data;
            }
        },
        async interfaceListarProductosDiagnosticos() {
            const resProuctos = await axios.get(
                "/api/interface-sahi-listar-listarProductosDiagnosticos/" +
                    this.interfaceAtencionSeleccionada
            );
            this.productosInterface = resProuctos.data.productos;

            let EnfermedadActualDiagnosticos = "";

            /**
             * Enfermedad actula
             */
            const consultas = resProuctos.data.consultas;
            EnfermedadActualDiagnosticos = "Enfermedad Actual: ";
            if (consultas.length > 0) {
                consultas.map((consultas) => {
                    EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos}\n ${consultas.DesEnfermedadActual} \n`;
                });
            }

            /**
             * Diagnosticos
             */
            const diagnosticos = resProuctos.data.diagnosticos;
            EnfermedadActualDiagnosticos += `\nDiagnosticos:`;
            if (diagnosticos.length > 0) {
                diagnosticos.map((diagnosticos) => {
                    EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos} \n ${diagnosticos.cod_diagnos} - ${diagnosticos.nom_diagnos}`;
                });
            }

            this.orden.motivo_examen = EnfermedadActualDiagnosticos;
            this.registro.motivo_examen = EnfermedadActualDiagnosticos;

            this.registro.diagnosticosEstudio = [];
            if (resProuctos.data.diagnosticos.length > 0) {
                this.registro.diagnosticosEstudio = resProuctos.data.diagnosticos;
            }

            this.registro.atencion_id = this.interfaceAtencionSeleccionada;
        },
        establecerPrioridad(e) {
            const seleccion = this.prioridades.find((p) => p.id === this.orden.priori.id);

            this.orden.priori.id = seleccion.id;
            this.registro.prioridad_id = seleccion.id;
            this.orden.priori.nom_priori = seleccion.nom_priori;
        },
        async establecerSede() {
            this.orden.sede.id = sessionStorage.getItem("ST-sede");
            this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
            this.idSedeActual = sessionStorage.getItem("ST-sede");
            this.nomSedeActual = sessionStorage.getItem("ST-nomsede");
            this.registro.sede_id = sessionStorage.getItem("ST-sede");
            await this.establecerSede();
            this.registros = [];
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
        abrirModal() {
            $(".right-sidebar").addClass("shw-rside");
        },
        cerrarModal() {
            $(".right-sidebar").removeClass("shw-rside");
        },
        limpiar() {
            this.orden.study_pk = "";
            this.orden.study_iuid = "";
            this.orden.study_id = "";
            this.orden.fec_estudio = "";
            this.orden.accession_no = "";
            this.orden.study_desc = "";
            this.orden.mods_in_study = "";
            this.orden.observaciones = "";

            this.orden.paciente.num_docu = "";
            this.orden.paciente.nom_pacien = "";
            this.orden.paciente.sexo = "";
            this.orden.paciente.fec_naci = "";

            this.orden.priori.id = "";

            this.productoSelecciondo = 0;

            this.orden.producto = [];
            this.orden.adjuntos = [];
            this.productosInterface = [];
            this.diagnosticosInterface = [];
            this.interfaceAtenciones = [];
            this.registro.diagnosticosEstudio = [];
            this.registro.productosEstudio = [];
            this.registro.atencion_id = null;
            this.registro.destino_estudio_id = null;
            this.registro.destino_estudio = "Tele Radiologia";

            this.soportesHC.archivo1 = null;
            this.soportesHC.archivo2 = null;
            this.soportesHC.archivo3 = null;
        },
        async listarPrioridades() {
            try {
                const res = await axios.get("/api/config-prioridades");
                this.prioridades = res.data;
            } catch (error) {
                this.prioridades = [];
                handleAxiosErrorToast(error, "No fue posible listar prioridades.");
            }
        },
        async listarProductos() {
            try {
                const res = await axios.get("/api/config-productos");
                this.productos = res.data;
            } catch (error) {
                this.productos = [];
                handleAxiosErrorToast(error, "No fue posible listar productos.");
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
            this.orden.medico.id = selectedId || "";
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
                const prod = this.productos.find((p) => String(p.id) === selectedId);
                if (prod) {
                    this.guardarProductosEstudio(prod.id, prod.cod_cups, prod.nom_produc);
                }
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
        guardarProductosEstudio(id, cod_cups, nom_produc) {
            const productoEstudioId = id;
            const productoEstudioCod_Cubs = cod_cups;
            const productoEstudioNomProduc = nom_produc;

            const yaExisteIndex = this.orden.producto.findIndex(
                (p) => p.cod_cups == productoEstudioCod_Cubs
            );
            if (yaExisteIndex !== -1) {
                // Si ya existe, quitarlo
                this.orden.producto.splice(yaExisteIndex, 1);
                // También quitarlo de registro.productosEstudio
                const registroIndex = this.registro.productosEstudio.findIndex(
                    (p) => p.cod_cups == productoEstudioCod_Cubs
                );
                if (registroIndex !== -1) {
                    this.registro.productosEstudio.splice(registroIndex, 1);
                }
            } else {
                // Si no existe, agregarlo
                this.orden.producto.push({
                    cod_cups: productoEstudioCod_Cubs,
                    nom_produc: productoEstudioNomProduc,
                });

                this.registro.productosEstudio.push({
                    checked: true,
                    id: productoEstudioId,
                    cod_cups: productoEstudioCod_Cubs,
                    nom_produc: productoEstudioNomProduc,
                });
            }

            this.productoSelecciondo = "";
        },
        quitarProductoEstudio(codCups) {
            const idxOrden = this.orden.producto.findIndex((p) => p.cod_cups === codCups);
            if (idxOrden !== -1) this.orden.producto.splice(idxOrden, 1);
            const idxRegistro = this.registro.productosEstudio.findIndex(
                (p) => p.cod_cups === codCups
            );
            if (idxRegistro !== -1) this.registro.productosEstudio.splice(idxRegistro, 1);
        },
        verImagen(pat_id, study_iuid, index = null) {
            if (index !== null) {
                if (this.loadingImagenIndex !== null) return;
                this.loadingImagenIndex = index;
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
                    this.loadingImagenIndex = null;
                }, 500);
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
