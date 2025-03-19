<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Ecografias</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Ecografias</a>
                    </li>
                    <li class="breadcrumb-item active">Admisionar Paciente</li>
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
                            <i class="fa fa-filter"></i> Filtros para busqueda de atención
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">
                                                # de atención, # de documento ó Nombres de paciente
                                            </label>
                                            <input type="text" id="atencion_id" name="atencion_id"
                                                v-model="busqueda.atencion_id" class="form-control"
                                                placeholder="Nombres de paciente o # de documento"
                                                @keyup.enter="buscarStudy()" />
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
                        <h6 class="card-subtitle text-info">Asigne los estudios a los médicos para su respectiva
                            lectura.
                        </h6>
                        <div class="table-responsive">
                            <table id="example23" class="table table-bordered table-striped" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th with='5%'>Fecha del Estudio</th>
                                        <th with='5%'># Documento</th>
                                        <th with='30%'>Paciente</th>
                                        <th>Accession</th>
                                        <th>Descripción</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in registros" :key="item.id" :id="item.study_pk"
                                        :class="{ 'text-info': item.agendado !== 0 }">
                                        <td>{{ item.study_datetime }}</td>
                                        <td>{{ item.pat_id }}</td>
                                        <td>{{ item.pat_name }}</td>
                                        <td>{{ item.accession_no }}</td>
                                        <td>{{ item.study_desc }}</td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="actualizar = true;
                                                mostrarRegistro(item, index);" title="Asignar estudio">
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
                                                <input type="text" id="num_docu" name="num_docu"
                                                    v-model="registro.num_docu" class="form-control"
                                                    placeholder="# de Documento" />
                                                <!-- <span class="text-danger" v-if="errores.num_docu">
                                                    {{ errores.num_docu[0] }}
                                                </span> -->
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Nombres</label>
                                                <input type="text" id="nombres" name="nombres"
                                                    v-model="registro.nombres" class="form-control"
                                                    placeholder="Nombres" />
                                                <!-- <span class="text-danger" v-if="errores.nombres">
                                                    {{ errores.nombres[0] }}
                                                </span> -->
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
                                                <input type="date" id="fec_naci" name="fec_naci"
                                                    v-model="registro.fec_naci" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Dirección</label>
                                                <input type="text" id="direccion" name="direccion"
                                                    v-model="registro.direccion" class="form-control" />
                                                <!-- <span class="text-danger" v-if="errores.direccion">
                                                    {{ errores.direccion[0] }}
                                                </span> -->
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Télefono</label>
                                                <input type="number" id="telefono" name="telefono"
                                                    v-model="registro.telefono" class="form-control" />
                                                <!-- <span class="text-danger" v-if="errores.telefono">
                                                    {{ errores.telefono[0] }}
                                                </span> -->
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">E - Mail</label>
                                                <input type="email" id="email" name="email" v-model="registro.email"
                                                    class="form-control" />
                                                <!-- <span class="text-danger" v-if="errores.email">
                                                    {{ errores.email[0] }}</span> -->
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Datos del Estudio
                                    </h5>
                                    <hr>

                                    <input type="hidden" id="study_pk" name="study_pk" v-model="registro.study_pk" />
                                    <input type="hidden" id="study_iuid" name="study_iuid"
                                        v-model="registro.study_iuid" />
                                    <input type="hidden" id="study_id" name="study_id" v-model="registro.study_id" />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Descripción</label>
                                                <input type="text" id="study_desc" name="study_desc"
                                                    v-model="registro.study_desc" class="form-control" />
                                                <!-- <span class="text-danger" v-if="errores.study_desc">
                                                    {{ errores.study_desc[0] }}
                                                </span> -->
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Detalle</label>
                                                <input type="text" id="accession_no" name="accession_no"
                                                    v-model="registro.accession_no" class="form-control" />
                                                <!-- <span class="text-danger" v-if="errores.accession_no">
                                                    {{ errores.accession_no[0] }}
                                                </span> -->
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Fecha</label>
                                                <input type="text" id="study_datetime" name="study_datetime"
                                                    v-model="registro.study_datetime" class="form-control" />
                                                <!-- <span class="text-danger" v-if="errores.study_datetime">
                                                    {{ errores.study_datetime[0] }}
                                                </span> -->
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
                                                <label class="control-label">Atención</label>
                                                <select v-model="interfaceAtencionSeleccionada"
                                                    name="interfaceAtencionSeleccionada"
                                                    id="interfaceAtencionSeleccionada"
                                                    class="form-control custom-select"
                                                    @change="interfaceListarProductosDiagnosticos()">
                                                    <option v-for="(ItemAtencion, index) in interfaceAtenciones"
                                                        :key="index" :value="ItemAtencion.IdAtencion">
                                                        {{ ItemAtencion.IdAtencion }} - {{ ItemAtencion.FecIngreso }}
                                                    </option>
                                                </select>
                                                <!-- <span class="text-danger" v-if="errores.prioridad_id">
                                                    {{ errores.prioridad_id[0] }}
                                                </span> -->
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> PRODUCTOS
                                        <hr>
                                    </h5>

                                    <span class="text-danger" v-if="errores.productosEstudio">
                                        {{ errores.productosEstudio[0] }}
                                    </span>
                                    <!-- <button type="button" class="btn waves-effect waves-light btn-xs btn-info float-right"
                                        data-toggle="modal" data-target="#exampleModal">
                                        <i class="fa fa-search"></i>
                                    </button> -->

                                    <div class="row">
                                        <div class="col-md-12" v-if="sedeInterface === false">
                                            <select class="select form-control" style="width: 100%; height:36px;"
                                                @change="guardarProductosEstudio(10)" v-model="productoSelecciondo">
                                                <option v-for="ItemProducto in productos" v-bind:key="ItemProducto"
                                                    v-bind:value="ItemProducto.id">
                                                    {{ ItemProducto.nom_produc }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 table-responsive"
                                            style="overflow-y: scroll; height: 250px;">
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
                                                    <tr v-for="(item, index) in registro.productosEstudio"
                                                        :key="item.id">
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

                                    <span class="text-danger" v-if="errores.diagnosticosEstudio">
                                        {{ errores.diagnosticosEstudio[0] }}
                                    </span>
                                    <!-- <button type="button" class="btn waves-effect waves-light btn-xs btn-info float-right">
                                        <i class="fa fa-search"></i>
                                    </button> -->

                                    <div class="row">
                                        <div class="col-md-12" v-if="sedeInterface === false">
                                            <select class="form-control custom-select"
                                                @change="guardarDiagnosticosEstudio(10)"
                                                style="width: 100%; height: 36px" v-model="diagnosticoSelecciondo">
                                                <option v-for="ItemDiagnostico in diagnosticos"
                                                    v-bind:key="ItemDiagnostico" v-bind:value="ItemDiagnostico.id">
                                                    {{ ItemDiagnostico.nom_diagnos }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 table-responsive"
                                            style="overflow-y: scroll; height: 250px;">
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

                                <button type="button" class="btn btn-inverse"
                                    @click="btnCerralModalForm()">Cancel</button>
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
    mounted() {
    },
    data() {
        return {
        }
    },
    methods: {
    }
}
</script>