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
                                            <label class="control-label"># de documento </label>
                                            <input type="text" id="bus_nom_num_docu" name="bus_nom_num_docu"
                                                v-model="busqueda.bus_num_docu" class="form-control"
                                                placeholder="# de documento de paciente" />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Nombres del paciente</label>
                                            <input type="text" id="bus_nom_num_docu" name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom" class="form-control"
                                                placeholder="Nombres del paciente" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input type="date" id="fecha_ini" name="fecha_ini"
                                                v-model="busqueda.fehc_ini" class="form-control" />
                                            <span class="text-danger" v-if="errores.fecha_ini">
                                                {{ erroresBusqueda.fecha_ini[0] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" id="fecha_fin" name="fecha_fin"
                                                v-model="busqueda.fecha_fin" class="form-control" />
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

                                    <!-- <button type="button" class="btn btn-inverse" @click="btnTraerImagenes()">Traer
                                        imagenes</button> -->
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
                        <h6 class="card-subtitle">Notifique por correo electrónico el descargue del reporte y de
                            imágenes de
                            un paciente.</h6>
                        <div class="table-responsive">
                            <table id="example23" class="table table-bordered table-striped" cellspacing="0"
                                width="100%">
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
                                        <td>{{ item.nom_pacien }}</td>
                                        <td>{{ item.tel }}</td>
                                        <td>{{ item.email }}</td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                v-if="permisos.includes('estudios.reporteLectura.index')"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="imprimirReporte(item)" title="Imprimir reporte">
                                                <i class="fa fa-file-pdf-o"></i>
                                            </button>
                                            <button type="button" v-if="permisos.includes('estudios.notificar.index')"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="actualizar = true;
                                                mostrarRegistro(item);" title="Notificar por correo electronico">
                                                <i class="fa fa-envelope-o"></i>
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
                                <div class="col-md-12">
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
                                                <span class="text-danger" v-if="errores.num_docu">
                                                    {{ errores.num_docu[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Nombres</label>
                                                <input type="text" id="nombres" name="nombres"
                                                    v-model="registro.nombres" class="form-control"
                                                    placeholder="Nombres" />
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
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input type="text" id="nom_produc" name="nom_produc" class="form-control"
                                                v-model="registro.nom_produc" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button type="button" class="btn btn-success m-r-5" @click="notificar()">
                                    <i class="fa fa-envelope-o"></i> Notificar
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

const API_URL = 'http://10.35.47.3:8080/dcm4chee-web3';
let axiosInstance = axios.create({
    withCredentials: true,  // Esto permite el uso de cookies
});

export default {
    props: ['usuarioactual', 'permisos'],
    mounted() {

    },
    data() {
        return {
            registros: [],
            tituloModal: "Nuevo registro",
            registro: {
                num_docu: "", nombres: "", sexo: "", fec_naci: "", email: "", email_reportar: "", direccion: "", telefono: "", nom_produc: "", id_producto_lectura: null
            },
            estudio: null,
            busqueda: { bus_num_docu: "", bus_nom: '', fehc_ini: "", fecha_fin: "" },
            errores: {},
            erroresBusqueda: {},
            imageUrl: '',
            imageBlob: null,
        };
    },
    methods: {
        async buscarStudy() {
            try {

                if (this.busqueda.bus_num_docu == '' && this.busqueda.bus_fehc_ini == "" && this.busqueda.bus_fecha_fin == "") {
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
        async imprimirReporte(estudio) {
            try {
                const res = await axios.post("/edit-word", estudio);
                window.open(`/descargar.pdf.lectura/${res.data.plantilla}`, '_blank');
            } catch (error) {
                console.log(error)
                /* if (error.response.status == 402) {

                    $.toast({
                        heading: "Ok!!!",
                        text: error.response.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } */

                this.errores = error.response.data;
            }
        },
        async notificar() {
            try {
                $.toast({
                    heading: "Ok!!!",
                    text: 'Generando reporte, por favor espere.',
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "info",
                    hideAfter: 3500,
                    stack: 6,
                });

                this.estudio.email = this.registro.email;
                const res = await axios.post('/notificar.email', this.estudio);

                if (res.status == 200) {
                    $.toast({
                        heading: "Ok!!!",
                        text: 'La notificacion se realizó correctamente, estamos enviando el correo electronico, por favor espere..',
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }

                this.btnCerralModalForm();
                this.limpiar();

            } catch (e) {
                if (e.response.status == 402) {
                    $.toast({
                        heading: "Ok!!!",
                        text: e.response.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
            }
        },
        async btnTraerImagenes() {
            try {
                const username = 'admin ';  // Reemplaza con tu nombre de usuario
                const password = 'admin';  // Reemplaza con tu contraseña
                await this.loginDcm(username, password);  // Realiza el inicio de sesión

                const studyInstanceUID = '1.3.51.0.7.11205627247.29189.59722.44357.20490.54522.12135';
                const seriesInstanceUID = '1.3.51.0.7.1993270954.51192.42060.33917.35145.4208.43461';
                const sopInstanceUID = '1.3.51.0.7.12605602729.42708.61000.39179.45802.51819.19455';

                const response = await this.getImage(studyInstanceUID, seriesInstanceUID, sopInstanceUID);
                this.imageBlob = response.data;
                this.imageUrl = URL.createObjectURL(this.imageBlob);
                this.downloadImage();
            } catch (error) {
                console.error('Error loading image:', error);
            }
        },
        loginDcm(username, password) {
            const API_URL = 'http://10.35.47.3:8080/dcm4chee-web3';
            return axios.post(`${API_URL}/login`, {
                username: username,
                password: password
            }, {
                withCredentials: true  // Esto permite el uso de cookies
            });
        },
        getImage(studyInstanceUID, seriesInstanceUID, sopInstanceUID) {
            const API_URL = 'http://10.35.47.3:8080/dcm4chee-web3';
            return axios.get(`${API_URL}/studies/${studyInstanceUID}/series/${seriesInstanceUID}/instances/${sopInstanceUID}/frames/1`, {
                responseType: 'blob',
                withCredentials: true  // Esto permite el uso de cookies
            });
        },
        downloadImage() {
            if (this.imageBlob) {
                const link = document.createElement('a');
                link.href = this.imageUrl;
                link.download = 'dicom_image.dcm';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        },
        mostrarRegistro(data = {}) {
            this.estudio = data;
            this.tituloModal = "Notificar al paciente: " + data.pat_name;

            this.registro.num_docu = data.num_docu;
            this.registro.nombres = data.nom_pacien;
            this.registro.sexo = data.sexo;
            this.registro.fec_naci = data.fec_naci;
            this.registro.telefono = data.tel;
            this.registro.nom_produc = data.nom_produc;
            this.registro.id_producto_lectura = data.id;

            this.btnCerralModalForm();
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
        limpiar() {
            this.registro.num_docu = '';
            this.registro.nombres = '';
            this.registro.sexo = '';
            this.registro.fec_naci = '';
            this.registro.telefono = '';
            this.registro.nom_produc = '';
            this.registro.id_producto_lectura = '';
        },
    },
};
</script>

<style scoped>
.right-sidebar {
    position: fixed;
    right: -40%;
    width: 40%;
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
    width: 40%;
    display: block;
}
</style>
