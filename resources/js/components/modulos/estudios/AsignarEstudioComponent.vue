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
                        <h4 class="card-title text-themecolor"><i class="fa fa-filter"></i> Filtros para busqueda de
                            estudios</h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Nombres de paciente o # de documento </label>
                                            <input type="text" id="bus_nom_num_docu" name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu" class="form-control"
                                                placeholder="Nombres de paciente o # de documento" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input type="date" id="bus_fehc_ini" name="bus_fehc_ini"
                                                v-model="busqueda.bus_fehc_ini" class="form-control" />
                                            <!-- <span class="text-danger" v-if="errores.bus_fehc_ini">
                                                {{ erroresBusqueda.bus_fehc_ini[0] }}
                                            </span> -->
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" id="bus_fecha_fin" name="bus_fecha_fin"
                                                v-model="busqueda.bus_fecha_fin" class="form-control" />
                                            <!-- <span class="text-danger" v-if="errores.bus_fecha_fin">
                                                {{ erroresBusqueda.bus_fecha_fin[0] }}
                                            </span> -->
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button type="button" class="btn btn-success m-r-5" @click="buscarStudy()">
                                        <i class="fa fa-search"></i> Buscar
                                    </button>

                                    <button type="button" class="btn btn-inverse" @click="btnLimpiar()">
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
                        <h6 class="card-subtitle text-info">Asigne los estudios a los médicos para su respectiva lectura.
                        </h6>
                        <div class="table-responsive">
                            <table id="example23" class="table table-bordered table-striped" cellspacing="0" width="100%">

                                <thead>
                                    <tr>
                                        <th>Fecha del Estudio</th>
                                        <th># Documento</th>
                                        <th>Paciente</th>
                                        <th>Accession</th>
                                        <th>Descripción</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.agendado }} - {{ item.study_datetime }}</td>
                                        <td>{{ item.pat_id }}</td>
                                        <td>{{ item.pat_name }}</td>
                                        <td>{{ item.accession_no }}</td>
                                        <td>{{ item.study_desc }}</td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="actualizar = true;
                                                mostrarRegistro(item);" title="Asignar estudio">
                                                <i class=" fa fa-child"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="verImagen(item.pat_id, item.study_iuid)" title="Ver imagenes">
                                                <i class="fa fa-file-photo-o"></i>
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
        <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; overflow-x: hidden; height: 100%;">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm" @click="btnCerralModalForm()"></i>
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
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label"># Documento</label>
                                                <input type="text" id="num_docu" name="num_docu" v-model="registro.num_docu"
                                                    class="form-control" placeholder="# de Documento" />
                                                <span class="text-danger" v-if="errores.num_docu">
                                                    {{ errores.num_docu[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Nombres</label>
                                                <input type="text" id="nombres" name="nombres" v-model="registro.nombres"
                                                    class="form-control" placeholder="Nombres" />
                                                <span class="text-danger" v-if="errores.nombres">
                                                    {{ errores.nombres[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Sexo</label>
                                                <input type="text" id="sexo" name="sexo" v-model="registro.sexo"
                                                    class="form-control" />
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Fec. Nacimi</label>
                                                <input type="date" id="fec_naci" name="fec_naci" v-model="registro.fec_naci"
                                                    class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Dirección</label>
                                                <input type="text" id="direccion" name="direccion"
                                                    v-model="registro.direccion" class="form-control" />
                                                <span class="text-danger" v-if="errores.direccion">
                                                    {{ errores.direccion[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Télefono</label>
                                                <input type="number" id="telefono" name="telefono"
                                                    v-model="registro.telefono" class="form-control" />
                                                <span class="text-danger" v-if="errores.telefono">
                                                    {{ errores.telefono[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">E - Mail</label>
                                                <input type="email" id="email" name="email" v-model="registro.email"
                                                    class="form-control" />
                                                <span class="text-danger" v-if="errores.email">
                                                    {{ errores.email[0] }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Datos del Estudio
                                    </h5>
                                    <hr>

                                    <input type="hidden" id="study_pk" name="study_pk" v-model="registro.study_pk" />
                                    <input type="hidden" id="study_iuid" name="study_iuid" v-model="registro.study_iuid" />
                                    <input type="hidden" id="study_id" name="study_id" v-model="registro.study_id" />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Descripción</label>
                                                <input type="text" id="study_desc" name="study_desc"
                                                    v-model="registro.study_desc" class="form-control" />
                                                <span class="text-danger" v-if="errores.study_desc">
                                                    {{ errores.study_desc[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Detalle</label>
                                                <input type="text" id="accession_no" name="accession_no"
                                                    v-model="registro.accession_no" class="form-control" />
                                                <span class="text-danger" v-if="errores.accession_no">
                                                    {{ errores.accession_no[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Fecha</label>
                                                <input type="text" id="study_datetime" name="study_datetime"
                                                    v-model="registro.study_datetime" class="form-control" />
                                                <span class="text-danger" v-if="errores.study_datetime">
                                                    {{ errores.study_datetime[0] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Reportar al Email</label>
                                                <input type="email" id="email_reportar" name="email_reportar"
                                                    v-model="registro.email_reportar" class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Observaciones</label>
                                                <input type="text" id="observaciones" name="observaciones"
                                                    v-model="registro.observaciones" class="form-control"
                                                    placeholder="Ingrese aqi las observaciones del estudio" />
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Adjunto
                                    </h5>
                                    <hr>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input type="file" class="form-control" id="exampleInputFile"
                                                    aria-describedby="fileHelp" @change='obtenerArchivo1' />
                                            </div>
                                            <div class="form-group">
                                                <input type="file" class="form-control" id="exampleInputFile"
                                                    aria-describedby="fileHelp" @change='obtenerArchivo2' />
                                            </div>
                                            <div class="form-group">
                                                <input type="file" class="form-control" id="exampleInputFile"
                                                    aria-describedby="fileHelp" @change='obtenerArchivo3' />
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="col-md-6">

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos de la Asignacion
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Médico</label>
                                                <select v-model="registro.medico_id" name="medico_id" id="medico_id"
                                                    class="form-control custom-select">
                                                    <option v-for="(ItemMedico, index) in medicos" :key="index"
                                                        :value="ItemMedico.id">
                                                        {{ ItemMedico.name }}
                                                    </option>
                                                </select>
                                                <span class="text-danger" v-if="errores.medico_id">
                                                    {{ errores.medico_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Prioridad</label>
                                                <select v-model="registro.prioridad_id" name="prioridad_id"
                                                    id="prioridad_id" class="form-control custom-select">
                                                    <option v-for="(ItemPrioridad, index) in prioridades" :key="index"
                                                        :value="ItemPrioridad.id">
                                                        {{ ItemPrioridad.nom_priori }} - {{ ItemPrioridad.tiempo }} {{
                                                            ItemPrioridad.tipo_tiempo }} {{ ItemPrioridad.observacion }}
                                                    </option>
                                                </select>
                                                <span class="text-danger" v-if="errores.prioridad_id">
                                                    {{ errores.prioridad_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4" v-if="sedeInterface === true">
                                            <div class="form-group">
                                                <label class="control-label">Atención {{ interfaceAtencionSeleccionada
                                                }}</label>
                                                <select v-model="interfaceAtencionSeleccionada"
                                                    name="interfaceAtencionSeleccionada" id="interfaceAtencionSeleccionada"
                                                    class="form-control custom-select"
                                                    @change="interfaceListarProductosDiagnosticos()">
                                                    <option v-for="(ItemAtencion, index) in interfaceAtenciones"
                                                        :key="index" :value="ItemAtencion.IdAtencion">
                                                        {{ ItemAtencion.IdAtencion }} - {{ ItemAtencion.FecIngreso }}
                                                    </option>
                                                </select>
                                                <span class="text-danger" v-if="errores.prioridad_id">
                                                    {{ errores.prioridad_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> PRODUCTOS
                                        <hr>
                                    </h5>

                                    <span class="text-danger" v-if="errores.prioridad_id">
                                        {{ errores.productosEstudio[0] }}
                                    </span>
                                    <!-- <button type="button" class="btn waves-effect waves-light btn-xs btn-info float-right"
                                        data-toggle="modal" data-target="#exampleModal">
                                        <i class="fa fa-search"></i>
                                    </button> -->

                                    <div class="row">
                                        <!-- <div class="col-md-12">
                                            <select class="form-control custom-select" @change="guardarProductosEstudio(10)"
                                                style="width: 100%; height: 36px" v-model="productoSelecciondo">
                                                <option v-for="ItemProducto in productos" v-bind:key="ItemProducto"
                                                    v-bind:value="ItemProducto.id">
                                                    {{ ItemProducto.nom_produc }}
                                                </option>
                                            </select>
                                        </div> -->
                                        <div class="col-md-12 table-responsive" style="overflow-y: scroll; height: 250px;">
                                            <table id="myTable" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Código</th>
                                                        <th>Producto</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(item, index) in registro.productosEstudio" :key="item.id">
                                                        <td>
                                                            <input type="checkbox" class="iCheck-helper"
                                                                id="flat-checkbox-2" data-checkbox="icheckbox_flat-red"
                                                                v-model="item.checked">
                                                            <label for="flat-checkbox-2"></label>
                                                            <!-- <div class="icheckbox_flat-blue checked"
                                                                style="position: relative;">
                                                                <input type="checkbox"
                                                                    class="check" id="flat-checkbox-2" checked=""
                                                                    data-checkbox="icheckbox_flat-red"
                                                                    style="position: absolute; opacity: 0;"><ins
                                                                    class="iCheck-helper"
                                                                    style="position: absolute; top: 0%; left: 0%; display: block; width: 100%; height: 100%; margin: 0px; padding: 0px; background: rgb(255, 255, 255); border: 0px; opacity: 0;"></ins>
                                                            </div>
                                                            <label for="flat-checkbox-2" class="">Checkbox 2</label> -->

                                                        </td>
                                                        <td>{{ item.cod_cups }}</td>
                                                        <td>{{ item.nom_produc }}</td>
                                                        <td class="text-nowrap">
                                                            <button type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="quitarProductoEstudio(index)">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info m-t-40">
                                        <i class="fa fa-user "></i> DIAGNOSTICOS
                                    </h5>

                                    <hr>

                                    <span class="text-danger" v-if="errores.prioridad_id">
                                        {{ errores.diagnosticosEstudio[0] }}
                                    </span>
                                    <!-- <button type="button" class="btn waves-effect waves-light btn-xs btn-info float-right">
                                        <i class="fa fa-search"></i>
                                    </button> -->

                                    <div class="row">
                                        <!-- <div class="col-md-12">
                                            <select class="form-control custom-select"
                                                @change="guardarDiagnosticosEstudio(10)" style="width: 100%; height: 36px"
                                                v-model="diagnosticoSelecciondo">
                                                <option v-for="ItemDiagnostico in diagnosticos" v-bind:key="ItemDiagnostico"
                                                    v-bind:value="ItemDiagnostico.id">
                                                    {{ ItemDiagnostico.nom_diagnos }}
                                                </option>
                                            </select>
                                        </div> -->
                                        <div class="col-md-12 table-responsive" style="overflow-y: scroll; height: 250px;">
                                            <table id="myTable" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Diagnostico</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="item in registro.diagnosticosEstudio" :key="item.id">
                                                        <td>{{ item.cod_diagnos }}</td>
                                                        <td>{{ item.nom_diagnos }}</td>
                                                        <td class="text-nowrap">
                                                            <button type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="quitarDiagnosticoEstudio(index)">
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
                                <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()">
                                    <i class="fa fa-check"></i> Guardar
                                </button>

                                <button type="button" class="btn btn-inverse" @click="btnCerralModalForm()">Cancel</button>
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
export default {
    props: ['usuarioactual'],
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
            $("#example23").DataTable().destroy();
        });
    },
    data() {
        return {
            dataIndex: 0,
            id: 0,
            registros: [],
            tituloModal: "Nuevo registro",
            registro: {
                study_pk: "", study_iuid: "", study_datetime: "", study_id: "", accession_no: "", study_desc: "", mods_in_study: "", observaciones: "", medico_id: "", prioridad_id: "",
                sede_id: this.sedeActual, quien_registro_id: this.usuarioactual.id, num_docu: "", nombres: "", sexo: "", fec_naci: "", email: "", email_reportar: "", direccion: "", telefono: "",
                productosEstudio: [], diagnosticosEstudio: [], atencion_id: null
            },
            soportesHC: { archivo1: null, archivo2: null, archivo3: null }, //Soporte de historia clinica
            busqueda: { sede_id: this.sedeActual, bus_nom_num_docu: "", bus_fehc_ini: "", bus_fecha_fin: "" },
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
            sedeActual: sessionStorage.getItem('ST-sede'),
            productosAtencion: [],
            sedeInterface: false
        };
    },
    methods: {
        async buscarStudy() {
            try {
                if (sessionStorage.getItem('ST-sede') == null) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: 'Debes establece la sede en la cual vas a trabajar.',
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else if (this.busqueda.bus_nom_num_docu == '' && this.busqueda.bus_fehc_ini == "" && this.busqueda.bus_fecha_fin == "") {
                    $.toast({
                        heading: "Upsss!!!",
                        text: 'Debes establece la fecha de inicio o finalización para la busqueda.',
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else {
                    const res = await axios.post("/study.listarEstudios", this.busqueda);

                    var studiasAgendados = [];
                    /* studiasAgendados = res.data;
                    for (const item in studiasAgendados) {
                        console.log(this.estudioAgendado(studiasAgendados[item].pat_id))
                    } */

                    let me = this;

                    /* res.data.map(function (currentValue, index, array) {

                        //var estaAgendado = me.estudioAgendado(currentValue.pat_id)
                        //console.log(estaAgendado)

                        studiasAgendados.push({
                            'accession_no': currentValue.accession_no,
                            'mods_in_study': currentValue.mods_in_study,
                            'pat_birthdate': currentValue.pat_birthdate,
                            'pat_id': currentValue.pat_id,
                            'pat_name': currentValue.pat_name,
                            'pat_sex': currentValue.pat_sex,
                            'study_datetime': currentValue.study_datetime,
                            'study_desc': currentValue.study_desc,
                            'study_id': currentValue.study_id,
                            'study_iuid': currentValue.study_iuid,
                            'study_pk': currentValue.study_pk,
                            'agendado': estaAgendado,
                        });
                    }); */


                    if (res.status == 200) {
                        $("#example23").DataTable().destroy();

                        //this.registros = studiasAgendados;
                        this.registros = res.data;
                        this.$nextTick(() => {
                            $("#example23").DataTable({
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
                console.log(error)
                if (error.response.status == 500) {
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

                this.erroresBusqueda = error.response.data.errors;
            }
        },
        async guardarRegistro() {

            try {
                const config = {
                    headers: {
                        'content-type': 'multipart/form-data',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                }
                // form data
                let formData = new FormData();

                formData.append('registro', JSON.stringify(this.registro));
                formData.append('archivo1', this.soportesHC.archivo1);
                formData.append('archivo2', this.soportesHC.archivo2);
                formData.append('archivo3', this.soportesHC.archivo3);

                const res = await axios.post('/estudios', formData, {
                    headers: { "Content-Type": "multipart/form-data" }
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
                }

                this.btnCerralModalForm();
                this.limpiar();

            } catch (error) {
                this.errores = error.response.data.errors;
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
        async mostrarRegistro(data = {}) {

            this.limpiar();

            const res = await axios.get('/config-sedes/' + this.sedeActual + '/buscarPorId');
            console.log(res.data.email);
            this.tituloModal = "Agendar al paciente: " + data.pat_name;
            this.id = data.id;
            this.registro.study_pk = data.study_pk;
            this.registro.study_iuid = data.study_iuid;
            this.registro.study_id = data.study_id;
            this.registro.study_datetime = data.study_datetime;
            this.registro.accession_no = data.accession_no;
            this.registro.study_desc = data.study_desc;
            this.registro.mods_in_study = data.mods_in_study;
            this.registro.email = '';
            this.registro.email_reportar = sessionStorage.getItem('ST-email_repor');
            this.registro.num_docu = data.pat_id;
            this.registro.nombres = data.pat_name;
            this.registro.sexo = data.pat_sex;
            this.registro.fec_naci = data.pat_birthdate;

            this.btnCerralModalForm();

            /**
             * Verificar si la sede tiene interface, traigo la informacion de las producto y diagnosticos
             */
            const resAtenciones = await axios.get('/interface-sahi-listar-atenciones/' + this.registro.num_docu);
            this.interfaceAtenciones = resAtenciones.data
        },
        async interfaceListarProductosDiagnosticos() {
            /**
             * Listo los productos de una atención
             */
            const resProuctos = await axios.get('/interface-sahi-listar-listarProductosDiagnosticos/' + this.interfaceAtencionSeleccionada);

            this.registro.productosEstudio = [];
            if (resProuctos.data.productos.length > 0) {
                resProuctos.data.productos.forEach(element => {

                    this.registro.productosEstudio.push(
                        {
                            checked: false,
                            id: element.IdProducto,
                            cod_cups: element.cod_cups,
                            nom_produc: element.nom_produc
                        }
                    );
                });
            }

            this.registro.diagnosticosEstudio = [];
            if (resProuctos.data.diagnosticos.length > 0) {
                this.registro.diagnosticosEstudio = resProuctos.data.diagnosticos;
            }

            this.registro.atencion_id = this.interfaceAtencionSeleccionada;
        },
        /* async estudioAgendado(numDocu) {
            const res = await axios.get('/estudio-buscar-por-numdocu/' + numDocu);
            console.log(res.data.message);
            if (res.data.message === 'Si') {
                return true;
            } else {
                return false;
            }
        }, */
        establecerSede() {
            this.registro.sede_id = sessionStorage.getItem('ST-sede');
            this.busqueda.sede_id = sessionStorage.getItem('ST-sede');
            this.sedeActual = sessionStorage.getItem('ST-sede');
            console.log(this.registro.sede_id)
            if (this.registro.sede_id = 1) {
                this.sedeInterface = true;
            } else {
                this.sedeInterface = false;
            }
            this.registros = [];
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
        limpiar() {
            this.registro.study_pk = '';
            this.registro.study_iuid = '';
            this.registro.study_id = '';
            this.registro.study_datetime = '';
            this.registro.accession_no = '';
            this.registro.study_desc = '';
            this.registro.mods_in_study = "";
            this.registro.email_reportar = "";

            this.registro.num_docu = '';
            this.registro.nombres = '';
            this.registro.sexo = '';
            this.registro.email = '';
            this.registro.fec_naci = '';

            this.registro.diagnosticosEstudio.splice(0, this.registro.diagnosticosEstudio.length);
            this.registro.productosEstudio.splice(0, this.registro.productosEstudio.length);

            this.registro.medico_id = 0;
            this.registro.prioridad_id = 0;
            this.productoSelecciondo = 0;
            this.diagnosticoSelecciondo = 0;

            this.listarMedicos();
            this.listarPrioridades();
        },
        async listarMedicos() {
            try {
                const res = await axios.get("/user.listarUsuarios/Medico");
                this.medicos = res.data;
            } catch (error) {
                console.log(error);
            }
        },
        async listarPrioridades() {
            try {
                const res = await axios.get("/config-prioridades");
                this.prioridades = res.data;
            } catch (error) {
                console.log(error);
            }
        },
        async listarProductos() {
            try {
                const res = await axios.get("/config-productos");
                this.productos = res.data;

            } catch (error) {
                console.log(error);
            }
        },
        async listarDiagnosticos() {
            try {
                const res = await axios.get("/config-diagnosticos");
                this.diagnosticos = res.data;
            } catch (error) {
                console.log(error);
            }
        },
        guardarProductosEstudio() {
            var indexProductoEstudio = this.productos.findIndex((x) => x.id == this.productoSelecciondo);
            if (indexProductoEstudio > 0) {
                var productoEstudioId = this.productos[indexProductoEstudio].id;
                var productoEstudioCod_Cubs = this.productos[indexProductoEstudio].cod_cups;
                var productoEstudioNomProduc = this.productos[indexProductoEstudio].nom_produc;

                this.registro.productosEstudio.push({
                    id: productoEstudioId,
                    cod_cups: productoEstudioCod_Cubs,
                    nom_produc: productoEstudioNomProduc,
                });
            }
        },
        quitarProductoEstudio(indexEliminar) {
            this.registro.productosEstudio.splice(indexEliminar, 1);
        },
        guardarDiagnosticosEstudio() {
            var indexDiagnosticoSeleccionado = this.diagnosticos.findIndex((x) => x.id == this.diagnosticoSelecciondo);
            if (indexDiagnosticoSeleccionado > 0) {
                var diagnosticoEstudioId = this.diagnosticos[indexDiagnosticoSeleccionado].id;
                var diagnosticoEstudioCod = this.diagnosticos[indexDiagnosticoSeleccionado].cod_diagnos;
                var diagnosticoEstudioDiagnos = this.diagnosticos[indexDiagnosticoSeleccionado].nom_diagnos;

                this.registro.diagnosticosEstudio.push({
                    id: diagnosticoEstudioId,
                    cod_diagnos: diagnosticoEstudioCod,
                    nom_diagnos: diagnosticoEstudioDiagnos,
                });
            }
        },
        quitarDiagnosticoEstudio(indexEliminar) {
            this.registro.diagnosticosEstudio.splice(indexEliminar, 1);
        },
        verImagen(pat_id, study_iuid) {

            let Url = sessionStorage.getItem('ST-urlsede') + '?patientID =' + pat_id + '&studyUID=' + study_iuid + '&serverName=' + sessionStorage.getItem('ST-servername')
            console.log(Url);

            window.open(Url, '_blank');
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