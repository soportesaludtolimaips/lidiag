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
                    <li class="breadcrumb-item active">Notificar Estudios</li>
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
                                            <input type="date" id="fecha_ini" name="fecha_ini" v-model="busqueda.fehc_ini"
                                                class="form-control" />
                                            <span class="text-danger" v-if="errores.fecha_ini">
                                                {{ erroresBusqueda.fecha_ini[0] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" id="fecha_fin" name="fecha_fin" v-model="busqueda.fecha_fin"
                                                class="form-control" />
                                            <span class="text-danger" v-if="errores.fecha_fin">
                                                {{ erroresBusqueda.fecha_fin[0] }}
                                            </span>
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
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de estudios disponibles para notificar</h4>
                        <h6 class="card-subtitle">Notifique por correo electrónico el descargue del reporte y de imágenes de
                            un paciente.</h6>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>Fecha del Estudio</th>
                                        <th>Fecha de lectura</th>
                                        <th># Documento</th>
                                        <th>Paciente</th>
                                        <th>Telefono</th>
                                        <th>Email</th>
                                        <th>Producto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.fec_estudio }}</td>
                                        <td>{{ item.fechor_lectura }}</td>
                                        <td>{{ item.num_docu }}</td>
                                        <td>{{ item.nombres }}</td>
                                        <td>{{ item.tel }}</td>
                                        <td>{{ item.email }}</td>
                                        <td>{{ item.nom_produc }}</td>
                                        <!-- <td>{{ item.pat_sex }}</td>
                                        <td>{{ item.pat_birthdate }}</td> -->
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="imprimirReporte(item.id_producto_lectura)" title="Imprimir reporte">
                                                <i class="fa fa-file-pdf-o"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="actualizar = true;
                                                notificarEmail(item.id_producto_lectura);"
                                                title="Ntificar por correo electronico">
                                                <i class=" fa fa-envelope-o"></i>
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
    },
    data() {
        return {
            id: 0,
            registros: [],
            tituloModal: "Nuevo registro",
            registro: {
                study_pk: "", study_iuid: "", study_datetime: "", study_id: "", accession_no: "", study_desc: "", observaciones: "", medico_id: "", prioridad_id: "", sede_id: this.sedeActual,
                quien_registro_id: this.usuarioactual.id, num_docu: "", nombres: "", sexo: "", fec_naci: "", email: "", direccion: "", telefono: "", productosEstudio: [], diagnosticosEstudio: []
            },
            busqueda: { sede_id: this.sedeActual, bus_nom_num_docu: "93123187", fehc_ini: "", fecha_fin: "" },
            errores: {},
            erroresBusqueda: {},
        };
    },
    methods: {
        async buscarStudy() {
            try {

                if (this.busqueda.bus_nom_num_docu == '' && this.busqueda.bus_fehc_ini == "" && this.busqueda.bus_fecha_fin == "") {
                    $.toast({
                        heading: "Upsss!!!",
                        text: 'Debe establece la fecha de inicio o finalización para la busqueda.',
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else {
                    const res = await axios.post("/listar.estudios.notificaciones", this.busqueda);
                    if (res.status == 200) {
                        $("#example23").DataTable().destroy();

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
        async imprimirReporte(id) {
            try {

                const res = await axios.get('/generar.pdf.lectura/' + id);
                console.log(res.data)

                const url = `/descargar.pdf.lectura/${res.data}`;

                axios.get(url, { responseType: 'blob' })
                    .then(response => {
                        const url = window.URL.createObjectURL(new Blob([response.data]));
                        const link = document.createElement('a');
                        link.href = url;
                        link.setAttribute('download', res.data);
                        document.body.appendChild(link);
                        link.click();
                    })
                    .catch(error => {
                        console.error(error);
                    });
            } catch (error) {
                this.errores = error.response.data.errors;
            }
        },
        mostrarRegistro(data = {}) {

            this.tituloModal = "Agendar al paciente: " + data.pat_name;
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

            this.btnCerralModalForm();
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

            this.registro.num_docu = '';
            this.registro.nombres = '';
            this.registro.sexo = '';
            this.registro.fec_naci = '';
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
