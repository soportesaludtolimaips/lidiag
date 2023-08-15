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
                    <li class="breadcrumb-item active">Trascribir Estudios</li>
                </ol>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <!-- <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor"><i class="fa fa-filter"></i> Filtros para busqueda de mis
                            pendientes</h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Nombres de paciente o # de documento</label>
                                            <input type="text" id="bus_nom_num_docu" name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu" class="form-control"
                                                placeholder="Nombres de paciente o # de documento" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input type="date" id="nom_diagnos" name="nom_diagnos"
                                                v-model="busqueda.fehc_ini" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" id="fecha_fin" name="fecha_fin"
                                                v-model="busqueda.fecha_fin" class="form-control" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button type="button" class="btn btn-success m-r-5" @click="buscarStudy()"><i
                                            class="fa fa-search"></i> Buscar</button>
                                    <button type="button" class="btn btn-inverse" @click="btnLimpiar()"><i
                                            class="fa fa-refresh"></i> Limpiar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div> -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de estudios pendientes por transcribir</h4>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Sucursal</th>
                                        <th>Fecha del Toma</th>
                                        <th>Quien Asigno</th>
                                        <th>Medico</th>
                                        <th>Paciente</th>
                                        <th>Detalle</th>
                                        <th>Producto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in registros" :key="index">
                                        <td width="2%">
                                            <button type="button" v-if="item.priori_id == 1"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-danger">A</button>
                                            <button type="button" v-else-if="item.priori_id == 2"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-warning">M</button>
                                            <button type="button" v-else-if="item.priori_id == 3"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-info">B</button>
                                            <button type="button" v-else="item.priori_id == 4"
                                                class="btn waves-effect waves-light btn-rounded btn-sm">S</button>
                                        </td>
                                        <td>{{ item.nom_sucur }}</td>
                                        <td>{{ item.fec_estudio }}</td>
                                        <td>{{ item.quien_registro }}</td>
                                        <td>{{ item.medico }}</td>
                                        <td>
                                            {{ item.num_docu }} <br />
                                            {{ item.nom_pacien }}
                                        </td>
                                        <td>
                                            {{ item.study_desc }} <br />
                                            {{ item.accession_no }}
                                        </td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="actualizar = true; mostrarRegistro(item);">
                                                <i class="fa fa-pencil"></i>
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
                                    <p class="text-info">
                                        <i class="fa fa-user"></i> DATOS DEL PACIENTE
                                    </p>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label"># Documento</label>
                                                <input type="text" id="num_docu" name="num_docu" v-model="registro.num_docu"
                                                    class="form-control" placeholder="# de Documento" />
                                                <span class="text-danger" v-if="errores.num_docu">{{
                                                    errores.num_docu[0]
                                                }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Nombres</label>
                                                <input type="text" id="nom_pacien" name="nom_pacien"
                                                    v-model="registro.nom_pacien" class="form-control"
                                                    placeholder="Nombres" />
                                                <span class="text-danger" v-if="errores.nom_pacien">{{
                                                    errores.nom_pacien[0]
                                                }}</span>
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

                                    <p class="text-info">
                                        <i class="fa fa-user"></i> DATOS DEL ESTUDIO
                                    </p>

                                    <input type="hidden" id="id_producto_lectura" name="id_producto_lectura"
                                        v-model="registro.id_producto_lectura" />
                                    <input type="hidden" id="email" name="email" v-model="registro.email" />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Descripción</label>
                                                <input type="text" id="study_desc" name="study_desc"
                                                    v-model="registro.study_desc" class="form-control" />
                                                <span class="text-danger" v-if="errores.study_desc">{{
                                                    errores.study_desc[0]
                                                }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Detalle</label>
                                                <input type="text" id="accession_no" name="accession_no"
                                                    v-model="registro.accession_no" class="form-control" />
                                                <span class="text-danger" v-if="errores.accession_no">{{
                                                    errores.accession_no[0]
                                                }}</span>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Fecha</label>
                                                <input type="text" id="fec_estudio" name="fec_estudio"
                                                    v-model="registro.fec_estudio" class="form-control" />
                                                <span class="text-danger" v-if="errores.fec_estudio">{{
                                                    errores.fec_estudio[0]
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
                                                    placeholder="Ingrese aqi las observaciones del estudio" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p class="text-info">
                                        <i class="fa fa-user"></i> LECTURA
                                        <button type="button" class="btn btn-secondary btn-xs float-right">
                                            <i class="fa fa-file-image-o"></i>
                                        </button>
                                    </p>
                                    <span class="text-danger" v-if="errores.lectura">{{
                                        errores.lectura[0]
                                    }}</span>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <textarea class="textarea_editor form-control" rows="25" name="lectura"
                                                    id="lectura" v-model="registro.lectura"
                                                    placeholder="Ingrese aquí la lectura del estudio"></textarea>
                                            </div>
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
    props: ['usuarioactual'],
    mounted() {
        this.listarPendientesTrascribir();
    },
    data() {
        return {
            registros: [],
            tituloModal: "Transcirbir estudio",
            registro: { id_estudio: 0, id_producto_lectura: 0, lectura: "", fec_estudio: "", accession_no: "", study_desc: "", observaciones: "", num_docu: "", nom_pacien: "", sexo: "", fec_naci: "", email: "", diagnosticosEstudio: [], id_producto_lectura: 0 },
            busqueda: { bus_nom_num_docu: "", fehc_ini: "", fecha_fin: "" },
            errores: {}
        };
    },
    methods: {
        /* async buscarStudy() {
            try {
                const res = await axios.post('/study.listarEstudios', this.busqueda);

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
            } catch (error) {
                console.log(error);
                this.errores = error.response.data.errors;
            }
        }, */
        async listarPendientesTrascribir() {
            try {
                const res = await axios.get("/estudio-listarPendientesTrascribir");
                $("#example23").DataTable().destroy();

                this.registros = res.data;

                this.$nextTick(() => {
                    $("#example23").DataTable({
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                });
            } catch (error) {
                console.log(error);
            }
        },
        async guardarRegistro() {

            /* const res = await axios.get('http://lidiag-reportes.test/api/estudios');
            console.log(res); */

            try {
                const res = await axios.post('/estudio-guardarTranscripcion', this.registro);
                console.log(res);
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


                    /* this.listarPendientesTrascribir()
                    this.btnCerralModalForm();
                    */

                }
            } catch (error) {
                console.log(error);
                this.errores = error.response.data.errors;
            }
        },
        mostrarRegistro(data = {}) {

            this.tituloModal = "Transcribir la lectura del paciente: " + data.nom_pacien;
            this.registro.id_estudio = data.id;
            this.registro.id_producto_lectura = data.id_producto_lectura;
            this.registro.fec_estudio = data.fec_estudio;
            this.registro.accession_no = data.accession_no;
            this.registro.study_desc = data.study_desc;
            this.registro.lectura = data.lectura;

            this.registro.num_docu = data.num_docu;
            this.registro.nom_pacien = data.nom_pacien;
            this.registro.sexo = data.pat_sex;
            this.registro.fec_naci = data.pat_birthdate;
            this.registro.direccion = data.direccion;
            this.registro.tel = data.tel;
            this.registro.email = data.email;

            this.btnCerralModalForm();
            this.errores = [];
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
    },
    computed: {
        estiloPrioridad: function () {
            this.tipoPrioridad;
            console.log("Estilo " + this.registro)
        }
    }
};

/* $(function () {
$('.textarea_editor').wysihtml5();
}); */
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
</style>
