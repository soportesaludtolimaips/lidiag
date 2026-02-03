<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Configuración</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Configuración</a>
                    </li>
                    <li class="breadcrumb-item active">sedes</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info btnDesplegarRigthSidebar"
                    @click="actualizar = false; mostrarRegistro()">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
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
                        <h4 class="card-title">Listado de sedes</h4>
                        <h6 class="card-subtitle">Administración de sedes para la distribucion y asignacion de
                            estudios
                            y agenda de lecturas de imagenes díagnosticas.</h6>
                        <div class="table-responsive m-t-40">
                            <table id="example23" ref="dt"
                                class="display nowrap table table-hover table-striped table-bordered" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th>Sede</th>
                                        <th>Ip Pacs</th>
                                        <th>BD Pacs</th>
                                        <th>Usuario Pacs</th>
                                        <th>Usuario Oviyam</th>
                                        <th>Password Oviyam</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.nom_sede }}</td>
                                        <td>{{ item.ip_dcm }}</td>
                                        <td>{{ item.bd_dcm }}</td>
                                        <td>{{ item.usuario_dcm }}</td>
                                        <td>{{ item.usuario_oviyam }}</td>
                                        <td>{{ item.url_oviyam }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado">Activo</span>
                                            <span class="label label-info" v-if="!item.estado">Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="actualizar = true; mostrarRegistro(item)">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="elimnarRegistro(item.id)"
                                                :disabled="loadingEliminarId === item.id">
                                                <i class="fa"
                                                    :class="loadingEliminarId === item.id ? 'fa-spinner fa-spin' : 'fa-trash'"></i>
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
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row p-t-20">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Sede</label>
                                        <input type="text" id="nom_sede" name="nom_sede" v-model="registro.nom_sede"
                                            class="form-control" placeholder="Ingrese el nombre de la sede">
                                        <span class="text-danger" v-if="errores.nom_sede">{{ errores.nom_sede[0]
                                            }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Email Para Reportar</label>
                                        <input type="text" id="email_repor" name="email_repor"
                                            v-model="registro.email_repor" class="form-control"
                                            placeholder="Ingrese el email para reportar las lecturas">
                                        <span class="text-danger" v-if="errores.email_repor">{{ errores.email_repor[0]
                                            }}</span>
                                    </div>
                                </div>
                            </div>

                            <h5 class="card-title text-info">
                                <i class="fa fa-paperclip"></i> Datos de configuración de DCMCHEE
                            </h5>
                            <hr>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Ip BD Dcm4chee</label>
                                        <input type="text" id="ip_dcm" name="ip_dcm" v-model="registro.ip_dcm"
                                            class="form-control" placeholder="Ip del Dcm4chee de la sede">
                                        <span class="text-danger" v-if="errores.ip_dcm">{{ errores.ip_dcm[0] }}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">BD DCM4CHEE</label>
                                        <input type="text" id="bd_dcm" name="bd_dcm" v-model="registro.bd_dcm"
                                            class="form-control" placeholder="Ingrese aquí el nombre de la BD">
                                        <span class="text-danger" v-if="errores.bd_dcm">{{ errores.bd_dcm[0] }}</span>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Usuario Bd</label>
                                        <input type="text" id="usuario_dcm" name="usuario_dcm"
                                            v-model="registro.usuario_dcm" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger" v-if="errores.usuario_dcm">{{ errores.usuario_dcm[0]
                                            }}</span>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Password</label>
                                        <input type="text" id="password_dcm" name="password_dcm"
                                            v-model="registro.password_dcm" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger" v-if="errores.password_dcm">{{ errores.password_dcm[0]
                                            }}</span>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Puerto</label>
                                        <input type="number" id="puerto_dcm" name="puerto_dcm"
                                            v-model="registro.puerto_dcm" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger" v-if="errores.puerto_dcm">{{ errores.puerto_dcm[0]
                                            }}</span>
                                    </div>
                                </div>
                            </div>

                            <h5 class="card-title text-info">
                                <i class="fa fa-paperclip"></i> Datos de configuración de OVIYAM
                            </h5>
                            <hr>

                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Usuario Oviyam</label>
                                        <input type="text" id="usuario_oviyam" name="usuario_oviyam"
                                            v-model="registro.usuario_oviyam" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger" v-if="errores.usuario_oviyam">{{
                                            errores.usuario_oviyam[0]
                                            }}</span>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Password Oviyam</label>
                                        <input type="text" id="password_oviyam" name="password_oviyam"
                                            v-model="registro.password_oviyam" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger" v-if="errores.password_oviyam">{{
                                            errores.password_oviyam[0]
                                            }}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Url Oviyam</label>
                                        <input type="text" id="url_oviyam" name="url_oviyam"
                                            v-model="registro.url_oviyam" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger" v-if="errores.url_oviyam">{{
                                            errores.url_oviyam[0]
                                            }}</span>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="control-label">Tab Oviyam</label>
                                        <input type="text" id="tap_oviyam" name="tap_oviyam"
                                            v-model="registro.tap_oviyam" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger" v-if="errores.tap_oviyam">{{
                                            errores.tap_oviyam[0]
                                            }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-paperclip"></i> Datos de configuración de la plantilla
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Plantilla para Rx, Tomografía y
                                                    resonancia</label>
                                                <input type="file" class="form-control" id="exampleInputFileRx"
                                                    aria-describedby="fileHelp" @change='obtenerPlantillaRx'
                                                    accept=".doc, .docx">
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label">Plantilla para ecografias</label>
                                                <input type="file" class="form-control" id="exampleInputFileEcografia"
                                                    aria-describedby="fileHelp" @change='obtenerPlantillaEcografia'
                                                    accept=".doc, .docx">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-file-o"></i> Variables disponibles para la plantilla
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row">

                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="text-info">Paciente</label>:
                                                        <code>${nombre_paciente}</code> Nombres del paciente,
                                                        <code>${num_docu}</code> Número de documento del paciente,
                                                        <code>${fec_naci}</code> Fecha de nacimiento del paciente,
                                                        <code>${sexo}</code> Sexo del paciente,
                                                        <code>${edad}</code> Edad del paciente,
                                                        <code>${dir}</code> Dirección de recidencia,
                                                        <code>${tel}</code> Télefono de contacto,
                                                        <code>${cel}</code> Celular de contacto,
                                                        <br /><br />

                                                        <label class="text-info">Estudio</label>:
                                                        <code>${fechor_estudio}</code> Fecha y Hora del estudio,
                                                        <code>${fechor_lectura}</code> Fecha y Hora de lectura,
                                                        <code>${fechor_transcipcion}</code> Fecha y Hora de
                                                        transcripción,
                                                        <code>${atencion}</code> Atención o número de folio,
                                                        <code>${cod_producto}</code> Código del producto,
                                                        <code>${nom_producto}</code> Nombre del producto,
                                                        <code>${lectura}</code>,
                                                        <code>${nom_sede} Sede de la tóma</code>.
                                                        <br /><br />

                                                        <label class="text-info">Ecografias</label>:
                                                        <code>${tipo_estudio}</code> Tipo de estudio,
                                                        <code>${opinion}</code> Opinión des estudio,
                                                        <code>${productos}</code> Productos de la lectura,
                                                        <br /><br />

                                                        <label class="text-info">Administradora de salud</label>:
                                                        <code>${eps}</code> Eps del paciente,
                                                        <code>${contrato}</code> Contrato de vinculación con la Eps.
                                                        <code>${nom_sede}</code> Nombre de la sede
                                                        <br /><br />

                                                        <label class="text-info">Medico</label>:
                                                        <code>${nombres_medico}</code>, <code>${reg_medico}</code>,
                                                        <code>${num_docu_medico}</code>,
                                                        <code>${firma_medico}</code>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input type="checkbox" id="estado" name="estado" v-model="registro.estado"
                                                :checked="registro.estado" value="1">
                                            <label for="estado"> Activo </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()"
                                    v-if="!actualizar" :disabled="loadingGuardar">
                                    <i class="fa" :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                    Guardar
                                </button>

                                <button type="button" class="btn btn-warning m-r-5" v-if="actualizar"
                                    @click="guardarRegistro()" :disabled="loadingGuardar">
                                    <i class="fa" :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-pencil'"></i>
                                    Actualizar
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
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { alertError, alertSuccess, confirmAction, handleAxiosErrorToast } from "@/helpers/alerts.js";

export default {
    mounted() {
        this.ListarDatos();
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dt || "#example23");
    },
    data() {
        return {
            id: 0,
            registros: [],
            registro: {
                id: null, nom_sede: '', email_repor: '', ip_dcm: '', bd_dcm: '', puerto_dcm: '', usuario_dcm: '', password_dcm: '', usuario_oviyam: '', password_oviyam: '', url_oviyam: '', tap_oviyam: '', estado: 0
            },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            plantillaRx: null,
            plantillaEcografia: null,
            errores: {},
            loadingListar: false,
            loadingGuardar: false,
            loadingEliminarId: null,
        };
    },
    methods: {
        async ListarDatos() {
            if (this.loadingListar) return;
            this.loadingListar = true;
            try {
                const res = await axios.get("/api/config-sedes");
                safeDestroyDataTable(this.$refs.dt || "#example23");
                this.registros = res.data;
                this.$nextTick(() => {
                    safeInitDataTable(this.$refs.dt || "#example23", {
                        dom: 'Bfrtip',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                    });
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar sedes.");
                this.registros = [];
            } finally {
                this.loadingListar = false;
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {
                const formData = new FormData();
                formData.append('registro', JSON.stringify(this.registro));
                formData.append('plantilla_rx', this.plantillaRx);
                formData.append('plantilla_ecografia', this.plantillaEcografia);

                if (this.actualizar === false) {

                    const res = await axios.post('/api/config-sedes', formData);

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

                        this.ListarDatos();
                        this.btnCerralModalForm();
                        this.limpiar();

                    }
                } else {
                    const res = await axios.post('/config/sedes/updateWitchPlantillas', formData, {
                        headers: { "Content-Type": "multipart/form-data" }
                    });

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

                        this.ListarDatos();
                        this.btnCerralModalForm();
                        this.limpiar();
                    }
                }
            } catch (error) {
                this.errores = error?.response?.data?.errors || {};
                handleAxiosErrorToast(error, "No fue posible guardar la sede.");
            } finally {
                this.loadingGuardar = false;
            }
        },
        async elimnarRegistro(id) {
            const ok = await confirmAction({
                title: "¿Estas seguro?",
                text: "No podrás recuperar este registro!",
                icon: "warning",
            });
            if (!ok) return;

            try {
                if (this.loadingEliminarId) return;
                this.loadingEliminarId = id;
                const response = await axios.delete('/api/config-sedes/' + id);
                alertSuccess("Ok!", response?.data?.message || "Registro eliminado.", "success");
                this.ListarDatos();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible eliminar el registro.");
                alertError("Error", "No fue posible eliminar el registro.", "error");
            } finally {
                this.loadingEliminarId = null;
            }
        },
        obtenerPlantillaRx(e) {
            this.plantillaRx = e.target.files[0];
        },
        obtenerPlantillaEcografia(e) {
            this.plantillaEcografia = e.target.files[0];
        },
        mostrarRegistro(data = {}) {

            if (this.actualizar == true) {
                this.tituloModal = "Actualizar el registro: " + data.nom_sede;
                this.registro.id = data.id;
                this.registro.nom_sede = data.nom_sede;
                this.registro.email_repor = data.email_repor;
                this.registro.ip_dcm = data.ip_dcm;
                this.registro.bd_dcm = data.bd_dcm;
                this.registro.puerto_dcm = data.puerto_dcm;
                this.registro.usuario_dcm = data.usuario_dcm;
                this.registro.password_dcm = data.password_dcm;
                this.registro.usuario_oviyam = data.usuario_oviyam;
                this.registro.password_oviyam = data.password_oviyam;
                this.registro.url_oviyam = data.url_oviyam;
                this.registro.tap_oviyam = data.tap_oviyam;
                this.registro.plantilla = data.plantilla;
                this.registro.estado = data.estado;

                $('#btnCerralModalForm').click();
                this.errores = [];

            } else {
                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.registro.id = 0;
                this.registro.nom_sede = "";
                this.registro.email_repor = "";
                this.registro.ip_dcm = "";
                this.registro.bd_dcm = "";
                this.registro.puerto_dcm = "";
                this.registro.usuario_dcm = "";
                this.registro.password_dcm = "";
                this.registro.usuario_oviyam = "";
                this.registro.password_oviyam = "";
                this.registro.url_oviyam = "";
                this.registro.tap_oviyam = "";
                this.registro.plantilla = "";
                this.registro.estado = 1;

                $('#btnCerralModalForm').click();
                this.errores = [];
            }
        },
        limpiar() {
            this.actualizar = false;
            this.tituloModal = "Nuevo registro";
            this.registro.id = 0;
            this.registro.nom_sede = "";
            this.registro.email_repor = "";
            this.registro.ip_dcm = "";
            this.registro.bd_dcm = "";
            this.registro.puerto_dcm = "";
            this.registro.usuario_dcm = "";
            this.registro.password_dcm = "";
            this.registro.usuario_oviyam = "";
            this.registro.password_oviyam = "";
            this.registro.url_oviyam = "";
            this.registro.plantilla = "";
            this.registro.estado = 1;

            $('#btnCerralModalForm').click();
            this.errores = [];
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        }
    },
};
</script>
<style scoped>
.right-sidebar {
    position: fixed;
    right: -50%;
    width: 50%;
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
    width: 50%;
    display: block;
}
</style>