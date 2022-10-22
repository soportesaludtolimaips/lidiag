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
                    <li class="breadcrumb-item active">sucursales</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info btnDesplegarRigthSidebar"
                    @click="actualizar=false;mostrarRegistro()">
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
                        <h4 class="card-title">Listado de sucursales</h4>
                        <h6 class="card-subtitle">Administración de sucursales para la distribucion y asignacion de
                            estudios
                            y agenda de lecturas de imagenes díagnosticas.</h6>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>Sucursal</th>
                                        <th>Ip Pacs</th>
                                        <th>BD Pacs</th>
                                        <th>Usuario Pacs</th>
                                        <th>Pasword Pacs</th>
                                        <th>Usuario Oviyam</th>
                                        <th>Password Oviyam</th>
                                        <th>Url Oviyam</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.sucursal }}</td>
                                        <td>{{ item.ip_dcm }}</td>
                                        <td>{{ item.bd_dcm }}</td>
                                        <td>{{ item.usuario_dcm }}</td>
                                        <td>{{ item.password_dcm }}</td>
                                        <td>{{ item.usuario_oviyam }}</td>
                                        <td>{{ item.password_oviyam }}</td>
                                        <td>{{ item.url_oviyam }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado">Activo</span>
                                            <span class="label label-info" v-if="!item.estado">Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="actualizar=true; mostrarRegistro(item)">
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

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{tituloModal}}
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
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Sucursal</label>
                                        <input type="text" id="sucursal" name="sucursal" v-model="registro.sucursal"
                                            class="form-control" placeholder="Ingrese aqui la razón social de la sede">
                                        <span class="text-danger" v-if="errores.sucursal">{{errores.sucursal[0]}}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Ip BD Dcm4chee</label>
                                        <input type="text" id="ip_dcm" name="ip_dcm" v-model="registro.ip_dcm"
                                            class="form-control" placeholder="Ip del Dcm4chee de la sucursal">
                                        <span class="text-danger" v-if="errores.ip_dcm">{{errores.ip_dcm[0]}}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">BD</label>
                                        <input type="text" id="bd_dcm" name="bd_dcm" v-model="registro.bd_dcm"
                                            class="form-control" placeholder="Ingrese aquí el nombre de la BD">
                                        <span class="text-danger" v-if="errores.bd_dcm">{{errores.ip_dcm[0]}}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Usuario Bd</label>
                                        <input type="text" id="usuario_dcm" name="usuario_dcm"
                                            v-model="registro.usuario_dcm" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger"
                                            v-if="errores.usuario_dcm">{{errores.usuario_dcm[0]}}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Password Bd</label>
                                        <input type="text" id="password" name="password" v-model="registro.password"
                                            class="form-control" placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger" v-if="errores.password">{{errores.password[0]}}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Usuario Oviyam</label>
                                        <input type="text" id="usuario_oviyam" name="usuario_oviyam"
                                            v-model="registro.usuario_oviyam" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger"
                                            v-if="errores.usuario_oviyam">{{errores.usuario_oviyam[0]}}</span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Password Oviyam</label>
                                        <input type="text" id="passwor_oviyam" name="passwor_oviyam"
                                            v-model="registro.passwor_oviyam" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger"
                                            v-if="errores.passwor_oviyam">{{errores.passwor_oviyam[0]}}</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Url Oviyam</label>
                                        <input type="text" id="url_oviyam" name="url_oviyam"
                                            v-model="registro.url_oviyam" class="form-control"
                                            placeholder="Ingrese aquí la URL del Ovyam de la sede">
                                        <span class="text-danger"
                                            v-if="errores.url_oviyam">{{errores.url_oviyam[0]}}</span>
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
                                    v-if="!actualizar">
                                    <i class="fa fa-check"></i> Guardar
                                </button>

                                <button type="button" class="btn btn-warning m-r-5" v-if="actualizar"
                                    @click="guardarRegistro()">
                                    <i class="fa fa-pencil"></i> Actualizar
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
        this.ListarDatos();
    },
    data() {
        return {
            id: 0,
            registros: [],
            registro: { sucursal: '', ip_dcm: '', bd_dcm: '', usuario_dcm: '', password_dcm: '', usuario_oviyam: '', passwor_oviyam: '', url_oviyam: '', estado: 0 },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
        };
    },
    methods: {
        async ListarDatos() {
            const res = await axios.get("api/config-sucursales");

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
                if (this.actualizar === false) {
                    const res = await axios.post('api/config-sucursales', this.registro);

                    if (res.status == 200) {

                        this.ListarDatos()

                        $.toast({
                            heading: 'Ok!!!',
                            text: res.data.message,
                            position: 'top-right',
                            loaderBg: '#ff6849',
                            icon: 'success',
                            hideAfter: 3500,
                            stack: 6
                        });
                    }
                } else {
                    const res = await axios.put('api/config-sucursales/' + this.id, this.registro);
                    if (res.status == 200) {

                        this.ListarDatos()

                        $.toast({
                            heading: 'Ok!!!',
                            text: res.data.message,
                            position: 'top-right',
                            loaderBg: '#ff6849',
                            icon: 'success',
                            hideAfter: 3500,
                            stack: 6
                        });

                    }
                }
            } catch (error) {
                console.log(error);
                this.errores = error.response.data.errors;
            }
        },
        elimnarRegistro(id) {
            var $this = this;
            swal({
                title: "Estas seguro?",
                text: "No podrás recuperar este registro!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "si, eliminarlo!",
                closeOnConfirm: false
            }, function (isConfirm) {
                if (isConfirm) {
                    axios.delete('api/config-sucursales/' + id).then(response => {
                        swal("Ok!!!!", response.data.message, "success");
                        $this.ListarDatos()
                    }).catch(error => {
                        console.log(error)
                    })
                }
            });
        },
        mostrarRegistro(data = {}) {
            if (this.actualizar == true) {
                this.tituloModal = "Actualizar el registro: " + data.sucursal;
                this.id = data.id;
                this.registro.sucursal = data.sucursal;
                this.registro.ip_dcm = data.ip_dcm;
                this.registro.bd_dcm = data.bd_dcm;
                this.registro.usuario_dcm = data.usuario_dcm;
                this.registro.password_dcm = data.password_dcm;
                this.registro.usuario_oviyam = data.usuario_oviyam;
                this.registro.passwor_oviyam = data.passwor_oviyam;
                this.registro.url_oviyam = data.url_oviyam;
                this.registro.estado;
                $('#btnCerralModalForm').click();
            } else {
                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.id = 0;
                this.registro.sucursal = "";
                this.registro.ip_dcm = "";
                this.registro.bd_dcm = "";
                this.registro.usuario_dcm = "";
                this.registro.password_dcm = "";
                this.registro.usuario_oviyam = "";
                this.registro.passwor_oviyam = "";
                this.registro.url_oviyam = "";
                this.registro.estado = 1;
                $('#btnCerralModalForm').click();
            }
        },
        btnCerralModalForm() {
            $('#btnCerralModalForm').click();
        }
    },
};
</script>