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
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de estudios pendientes por transcribir</h4>
                        <div class="table-responsive">
                            <table id="example23" class="table table-bordered table-striped" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Sucursal</th>
                                        <th>Fecha de la Toma</th>
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
                                        <td>{{ item.nom_sede }}</td>
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
                                        <div class="col-md-6">
                                            <div class="form-group">

                                                <dl>
                                                    <dt>Paciente:</dt>
                                                    <dd>{{ registro.nom_pacien }}</dd>
                                                    <dt># Documento:</dt>
                                                    <dd>{{ registro.num_docu }}</dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Fecha de nacimiento:</dt>
                                                    <dd>{{ registro.fec_naci }}</dd>
                                                    <dt>Sexo:</dt>
                                                    <dd>{{ registro.sexo }}</dd>
                                                </dl>
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
                                        <i class="fa fa-user"></i> Datos del Estudio
                                    </h5>
                                    <hr>

                                    <input type="hidden" id="id_producto_lectura" name="id_producto_lectura"
                                        v-model="registro.id_producto_lectura" />
                                    <input type="hidden" id="email" name="email" v-model="registro.email" />

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
                                                <label class="control-label">Reportar al Email</label>
                                                <input type="text" id="email_reportar" name="email_reportar"
                                                    v-model="registro.email_reportar" class="form-control"
                                                    placeholder="Ingrese aqi el email a reportar" />
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
                                    <hr>

                                    <div class="row table-responsive">
                                        <div class="col-md-12">
                                            <dl>
                                                <dd>{{ productoEstudio.nom_produc }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                    <br />

                                    <!-- <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Diagnosticos
                                    </h5>
                                    <hr>

                                    <div class="row table-responsive" style="height:150px; overflow-y: scroll;">
                                        <div class="col-md-12">
                                            <ul v-for="(item, index) in  diagnosticosEstudio " :key="index">
                                                <li>{{ item.nom_diagnos }}</li>

                                            </ul>
                                        </div>
                                    </div> -->
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-9">
                                            <h5 class="card-title text-info">
                                                <i class="fa fa-edit"></i> Lectura
                                            </h5>
                                            <hr>
                                        </div>
                                        <div class="col-3">
                                            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                                                <button type="button" class="btn btn-secondary btn-xs"
                                                    @click="verImagen()">
                                                    <i class="fa fa-file-image-o"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-danger" v-if="errores.lectura">{{ errores.lectura[0] }}</span>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <textarea v-model="registro.lectura"
                                                    class="textarea_editor form-control" rows="20" id="lectura"
                                                    style="height: 100%;"
                                                    placeholder="Ingrese aquí la lectura del estudio ..."></textarea>

                                                <!-- <form method="post">
                                                    <div class="form-group">
                                                        <textarea class="textarea_editor form-control" rows="15"
                                                            placeholder="Enter text ..."
                                                            v-model="registro.lectura"></textarea>
                                                    </div>
                                                </form> -->
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-paperclip"></i> Adjuntos
                                    </h5>
                                    <hr>

                                    <div class="row">
                                        <div class="card card-body">
                                            <div class="row">
                                                <div class="col-md-8 col-lg-9"
                                                    v-for="(itemArchivo, index) in soportesHC" :key="index">
                                                    <a href="javascript:void(0)"
                                                        @click="descargaSoportte(itemArchivo.archivo_encrip)">{{
                                        itemArchivo.archivo_original }}</a>
                                                </div>
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
$(function () {
    $('#textarea_editor').wysihtml5();
});
export default {
    props: ['usuarioactual'],
    mounted() {
        this.listarPendientesTrascribir();
    },
    data() {
        return {
            productoEstudio: { cod_produc: '', nom_produc: '' },
            diagnosticosEstudio: [],
            soportesHC: [],
            registros: [],
            tituloModal: "Transcirbir estudio",
            registro: {
                id_estudio: 0, id_producto_lectura: 0, lectura: "", fec_estudio: "", accession_no: "", study_desc: "", observaciones: "", email_reportar: '', num_docu: "", nom_pacien: "", sexo: "",
                fec_naci: "", email: "", diagnosticosEstudio: [], sede_id: "", atencion: null, medico_id: null, cod_cups: ''
            },
            busqueda: { bus_nom_num_docu: "", bus_fehc_ini: "", bus_fecha_fin: "" },
            datosImagen: { urlOviyam: '', patientId: '', studyUID: '', serverName: '' },
            errores: {},
        };
    },
    methods: {
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

            try {
                const res = await axios.post('/estudio-guardarTranscripcion', this.registro);

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

                    this.listarMisPendientes();
                    this.btnCerralModalForm();
                    this.limpiar();
                }
            } catch (error) {
                console.log(error);
                this.errores = error.response.data.errors;
            }
        },
        descargaSoportte(nomArchivoEncriptado) {
            const url = `/descarga-soportes-hc/${nomArchivoEncriptado}`;

            axios.get(url, { responseType: 'blob' })
                .then(response => {
                    const url = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', nomArchivoEncriptado);
                    document.body.appendChild(link);
                    link.click();
                })
                .catch(error => {
                    console.error(error);
                });
        },
        verImagen() {
            let Url = this.datosImagen.urlOviyam + '?patientID =' + this.datosImagen.patientId + '&studyUID=' + this.datosImagen.studyUID + '&serverName=' + this.datosImagen.serverName
            window.open(Url, '_blank');
        },
        async mostrarRegistro(data = {}) {

            this.tituloModal = "Transcribir la lectura del paciente: " + data.nom_pacien;
            this.id = data.id;
            this.registro.id_producto_lectura = data.id_producto_lectura;
            this.registro.fec_estudio = data.fec_estudio;
            this.registro.accession_no = data.accession_no;
            this.registro.study_desc = data.study_desc;
            this.registro.email_reportar = data.email_reportar;
            this.registro.atencion = data.atencion;
            this.registro.medico_id = data.medico_id;
            this.registro.num_docu = data.num_docu;
            this.registro.nom_pacien = data.nom_pacien;
            this.registro.direccion = data.direccion;
            this.registro.email = data.email;
            this.registro.tel = data.tel;
            this.registro.sexo = data.pat_sex;
            this.registro.fec_naci = data.pat_birthdate;
            this.registro.lectura = `Atención: ${data.atencion}\nFecha y Hora: ${data.fec_estudio}\n\n${data.nom_produc}\n\n${data.lectura}`;
            this.registro.cod_cups = data.cod_cups;

            this.datosImagen.urlOviyam = data.url_oviyam;
            this.datosImagen.patientId = data.num_docu;
            this.datosImagen.studyUID = data.study_iuid;
            this.datosImagen.serverName = data.tap_oviyam;

            this.productoEstudio.cod_produc = data.cod_cups;
            this.productoEstudio.nom_produc = data.nom_produc;

            this.registro.sede_id = data.sede_id;

            const res = await axios.get('/study-listar-diagnostico-por-estudio?id=' + this.id);
            this.diagnosticosEstudio = res.data;

            const resSoportesHC = await axios.get('/listar-soportes-hc-estudio?id=' + this.id);
            this.soportesHC = resSoportesHC.data;

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
