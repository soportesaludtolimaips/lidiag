<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Lecturas</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Lecturas</a>
                    </li>
                    <li class="breadcrumb-item active">Asignar Lecturas</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info right-side-toggle80"
                    @click="actualizar = false; mostrarRegistro()">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
            <div class="">
                <button class="right-side-toggle btn-info btn btn-circle btn-sm">
                    <i class="fa fa-plus-circle m-r-5"></i>
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
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros para busqueda de estudios
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Nombres de paciente o # de documento
                                            </label>
                                            <input type="text" id="bus_nom_num_docu" name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu" class="form-control"
                                                placeholder="Nombres de paciente o # de documento">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input type="date" id="nom_diagnos" name="nom_diagnos"
                                                v-model="busqueda.fehc_ini" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" id="fecha_fin" name="fecha_fin"
                                                v-model="busqueda.fecha_fin" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button type="button" class="btn btn-success m-r-5" @click="buscarStudy()">
                                        <i class="fa fa-search"></i> Buscar
                                    </button>

                                    <button type="button" class="btn btn-inverse" @click="btnLimpiar()">
                                        <i class="fa fa-refresh"></i> Limpiar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de estudios disponibles</h4>
                        <h6 class="card-subtitle">Asigne los estudios a los médicos para su respectiva lectura.</h6>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>Fecha del Estudio</th>
                                        <th>Accession</th>
                                        <th>Descripción</th>
                                        <th># Documento</th>
                                        <th>Paciente</th>
                                        <th>Sexo</th>
                                        <th>Fec. Nacimiento</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.study_datetime }}</td>
                                        <td>{{ item.accession_no }}</td>
                                        <td>{{ item.study_desc }}</td>
                                        <td>{{ item.pat_id }}</td>
                                        <td>{{ item.pat_name }}</td>
                                        <td>{{ item.pat_sex }}</td>
                                        <td>{{ item.pat_birthdate }}</td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="actualizar = true; mostrarRegistro(item)">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="elimnarRegistro(item.id)">
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
        </div>
        <!-- ============================================================== -->
        <!-- End PAge Content -->
        <!-- ============================================================== -->
        <div class="">
            <button
                class="right-side-toggle waves-effect waves-light btn-inverse btn btn-circle btn-sm pull-right m-l-10">
                <i class="ti-settings text-white"></i>
            </button>
        </div>
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
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-info">
                                        <i class="fa fa-user"></i> DATOS DEL PACIENTE
                                    </p>
                                    <div class="row">
                                        <div class=" col-md-3">
                                            <div class="form-group">
                                                <label class="control-label"># de Documento</label>
                                                <input type="text" id="num_docu" name="num_docu"
                                                    v-model="registro.num_docu" class="form-control"
                                                    placeholder="# de Documento">
                                                <span class="text-danger" v-if="errores.num_docu">{{
                                                    errores.num_docu[0]
                                                }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Nombres</label>
                                                <input type="text" id="nombres" name="nombres"
                                                    v-model="registro.nombres" class="form-control"
                                                    placeholder="Nombres">
                                                <span class="text-danger" v-if="errores.nombres">{{
                                                    errores.nombres[0]
                                                }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Sexo</label>
                                                <input type="text" id="sexo" name="sexo" v-model="registro.sexo"
                                                    class="form-control">
                                                <span class="text-danger" v-if="errores.sexo">{{
                                                    errores.sexo[0]
                                                }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Fec. Nacimi</label>
                                                <input type="date" id="fec_naci" name="fec_naci"
                                                    v-model="registro.fec_naci" class="form-control">
                                                <span class="text-danger" v-if="errores.fec_naci">{{
                                                    errores.fec_naci[0]
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class=" col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Dirección</label>
                                                <input type="text" id="direccion" name="direccion"
                                                    v-model="registro.direccion" class="form-control">
                                                <span class="text-danger" v-if="errores.direccion">{{
                                                    errores.direccion[0]
                                                }}</span>
                                            </div>
                                        </div>
                                        <div class=" col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Télefono</label>
                                                <input type="number" id="telefono" name="telefono"
                                                    v-model="registro.telefono" class="form-control">
                                                <span class="text-danger" v-if="errores.telefono">{{
                                                    errores.telefono[0]
                                                }}</span>
                                            </div>
                                        </div>
                                        <div class=" col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">E - Mail</label>
                                                <input type="email" id="email" name="email" v-model="registro.email"
                                                    class="form-control">
                                                <span class="text-danger" v-if="errores.email">{{
                                                    errores.email[0]
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <p class="text-info">
                                        <i class="fa fa-user"></i> DATOS DEL ESTUDIO
                                    </p>

                                    <input type="hidden" id="study_pk" name="study_pk" v-model="registro.study_pk">
                                    <input type="hidden" id="study_iuid" name="study_iuid"
                                        v-model="registro.study_iuid">
                                    <input type="hidden" id="study_id" name="study_id" v-model="registro.study_id">

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Descripción</label>
                                                <input type="text" id="study_desc" name="study_desc"
                                                    v-model="registro.study_desc" class="form-control">
                                                <span class="text-danger" v-if="errores.study_desc">{{
                                                    errores.study_desc[0]
                                                }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Detalle</label>
                                                <input type="text" id="accession_no" name="accession_no"
                                                    v-model="registro.accession_no" class="form-control">
                                                <span class="text-danger" v-if="errores.accession_no">{{
                                                    errores.accession_no[0]
                                                }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Fecha</label>
                                                <input type="text" id="study_datetime" name="study_datetime"
                                                    v-model="registro.study_datetime" class="form-control">
                                                <span class="text-danger" v-if="errores.study_datetime">{{
                                                    errores.study_datetime[0]
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Observaciones</label>
                                                <input type="text" id="observaciones" name="observaciones"
                                                    v-model="registro.observaciones" class="form-control"
                                                    placeholder="Ingrese aqi las observaciones del estudio">
                                                <span class="text-danger" v-if="errores.observaciones">{{
                                                    errores.observaciones[0]
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-info">
                                        <i class="fa fa-user"></i> DATOS DEL AGENDAMIENTO
                                    </p>
                                    <div class="row">
                                        <div class=" col-md-6">
                                            <div class="form-group">
                                                <label>Médico</label>
                                                <select v-model="registro.medico_id" name="medico_id" id="medico_id"
                                                    class="form-control custom-select">
                                                    <option v-for="(ItemMedico, index) in medicos" :key="index"
                                                        :value="ItemMedico.id">{{ ItemMedico.name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class=" col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">Prioridad</label>
                                                <select v-model="registro.prioridad_id" name="prioridad_id"
                                                    id="prioridad_id" class="form-control custom-select">
                                                    <option>--Seleccione la prioridad--</option>
                                                    <option v-for="(ItemPrioridad, index) in prioridades" :key="index"
                                                        :value="ItemPrioridad.id">{{ ItemPrioridad.nom_priori }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-info">
                                        <i class="fa fa-user"></i> PRODUCTOS
                                        <button type="button"
                                            class="btn waves-effect waves-light btn-xs btn-info float-right"
                                            data-toggle="modal" data-target="#exampleModal">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </p>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <!-- select2 -->
                                            <select class=" form-control custom-select"
                                                @change="llevarProductoSelecciondo()" style="width: 100%; height:36px;"
                                                v-model="productoSelecciondo">
                                                <option v-for="(ItemProducto, index ) in productos" :key="index"
                                                    :value="ItemProducto.id">
                                                    {{ ItemProducto.nom_produc }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class=" col-md-12">
                                            <table id="example23"
                                                class="display nowrap table table-hover table-striped table-bordered"
                                                cellspacing="0" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th width="150px">Producto</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="item in productosEstudio" :key="item.id">
                                                        <td>{{ item.cod_cups }}</td>
                                                        <td>{{ item.nom_produc }}</td>
                                                        <td class="text-nowrap">
                                                            <button type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="quitarProductoEstudio(item.id)">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <p class="text-info">
                                        <i class="fa fa-user"></i> DIAGNOSTICOS
                                        <button type="button"
                                            class="btn waves-effect waves-light btn-xs btn-info float-right">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </p>
                                    <div class="row">
                                        <div class=" col-md-12">
                                            <table id="example23"
                                                class="display nowrap table table-hover table-striped table-bordered"
                                                cellspacing="0" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th width="150px">Diagnostico</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="item in diagnosticosEstudio" :key="item.id">
                                                        <td>{{ item.pk }}</td>
                                                        <td>{{ item.pk }}</td>
                                                        <td class="text-nowrap">
                                                            <button type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                                @click="actualizar = true; mostrarRegistro(item)">
                                                                <i class="fa fa-pencil"></i>
                                                            </button>
                                                            <button type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="elimnarRegistro(item.id)">
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

                                <button type="button" class="btn btn-inverse" @click="btnCerralModalForm()">
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
export default {
    mounted() {
        this.listarMedicos();
        this.listarPrioridades();
        this.listarProductos();
    },
    data() {
        return {
            id: 0,
            registros: [],
            productosEstudio: [],
            diagnosticosEstudio: [],
            tituloModal: 'Nuevo registro',
            registro: { study_pk: '', study_iuid: '', study_datetime: '', study_id: '', accession_no: '', study_desc: '', observaciones: '', medico_id: '', prioridad_id: 0, num_docu: '', nombres: '', sexo: '', fec_naci: '', email: '', direccion: '', telefono: '' },
            busqueda: { bus_nom_num_docu: '5860691', fehc_ini: '', fecha_fin: '' },
            errores: {},
            medicos: [],
            prioridades: [],
            productos: [],

            productoSelecciondo: ''

        };
    },
    methods: {
        async buscarStudy() {
            const res = await axios.post('api/study.listarEstudios', this.busqueda);

            $('#example23').DataTable().destroy();

            this.registros = res.data;

            this.$nextTick(() => {
                $('#example23').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });
            });
        },
        async guardarRegistro() {
            try {

                const res = await axios.post('api/lecturas', this.registro);

                if (res.status == 200) {

                    $.toast({
                        heading: 'Ok!!!',
                        text: res.data.message,
                        position: 'top-right',
                        loaderBg: '#ff6849',
                        icon: 'success',
                        hideAfter: 3500,
                        stack: 6
                    });

                    $('#btnCerralModalForm').click();
                }

            } catch (error) {
                console.log(error);
                this.errores = error.response.data.errors;
            }
        },
        mostrarRegistro(data = {}) {
            this.tituloModal = "Agendar al paciente: " + data.nombres;
            this.id = data.id;
            this.registro.study_pk = data.study_pk;
            this.registro.study_iuid = data.study_iuid;
            this.registro.study_id = data.study_id;
            this.registro.study_datetime = data.study_datetime;
            this.registro.accession_no = data.accession_no;
            this.registro.study_desc = data.study_desc;

            this.registro.num_docu = data.pat_id;
            this.registro.nombres = data.pat_name;
            this.registro.sexo = data.pat_sex;
            this.registro.fec_naci = data.pat_birthdate;
            $('#btnCerralModalForm').click();

        },
        btnCerralModalForm() {
            $('#btnCerralModalForm').click();
        },
        async listarMedicos() {
            const res = await axios.get('api/user.listarUsuarios/Medico');
            this.medicos = res.data;
        },
        async listarPrioridades() {
            const res = await axios.get('api/config-prioridades');
            this.prioridades = res.data;
        },
        async listarProductos() {
            const res = await axios.get('api/config-productos');
            this.productos = res.data;
        },
        async llevarProductoSelecciondo() {
            const res = await axios.get('api/config-productos/' + this.productoSelecciondo);

            let itemProducto = {}
            itemProducto.id = res.data.id;
            itemProducto.cod_cups = res.data.cod_cups;
            itemProducto.nom_produc = res.data.nom_produc;
            this.productosEstudio.push(itemProducto)

            console.log(this.productosEstudio);
        },
        quitarProductoEstudio(idEliminar) {
            console.log(idEliminar)
            let nuevosProductos = this.productosEstudio.filter(id => id != idEliminar);
            console.log('Productos del arreglo');
            console.log(this.productosEstudio);
            console.log('Nuevos productos');
            console.log(nuevosProductos);

        }
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