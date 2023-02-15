<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Seguridad</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Seguridad</a>
                    </li>
                    <li class="breadcrumb-item active">Gestión de Usuarios</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info btnDesplegarRigthSidebar"
                    @click="actualizar = false; mostrarRegistro()">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
            <div class="">
                <button class="right-side-toggle waves-effect waves-light btn-inverse btn btn-circle btn-sm pull-right m-l-10"><i
                        class="ti-settings text-white"></i> </button>
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
                        <h4 class="card-title">Listado de usuarios</h4>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Tipo Usuario</th>
                                        <th># Documento</th>
                                        <th>Reg. Médico</th>
                                        <th>Nombres</th>
                                        <th>E-Mail</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>Imagen</td>
                                        <td>{{ item.tipo_user }}</td>
                                        <td>{{ item.num_docu }}</td>
                                        <td>{{ item.reg_med }}</td>
                                        <td>{{ item.name }}</td>
                                        <td>{{ item.email }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado == 1">Activo</span>
                                            <span class="label label-danger" v-if="item.estado == 0">Inactivo</span>
                                        </td>
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

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar">
            <div class="slimscrollright">
                <div class="rpanel-title"> Service Panel <span><i class="ti-close right-side-toggle"></i></span> </div>
                <div class="r-panel-body">
                    <ul id="themecolors" class="m-t-20">
                        <li><b>With Light sidebar</b></li>
                        <li><a href="javascript:void(0)" data-theme="default" class="default-theme">1</a></li>
                        <li><a href="javascript:void(0)" data-theme="green" class="green-theme">2</a></li>
                        <li><a href="javascript:void(0)" data-theme="red" class="red-theme">3</a></li>
                        <li><a href="javascript:void(0)" data-theme="blue" class="blue-theme">4</a></li>
                        <li><a href="javascript:void(0)" data-theme="purple" class="purple-theme">5</a></li>
                        <li><a href="javascript:void(0)" data-theme="megna" class="megna-theme">6</a></li>
                        <li class="d-block m-t-30"><b>With Dark sidebar</b></li>
                        <li><a href="javascript:void(0)" data-theme="default-dark"
                                class="default-dark-theme working">7</a></li>
                        <li><a href="javascript:void(0)" data-theme="green-dark" class="green-dark-theme">8</a></li>
                        <li><a href="javascript:void(0)" data-theme="red-dark" class="red-dark-theme">9</a></li>
                        <li><a href="javascript:void(0)" data-theme="blue-dark" class="blue-dark-theme">10</a></li>
                        <li><a href="javascript:void(0)" data-theme="purple-dark" class="purple-dark-theme">11</a></li>
                        <li><a href="javascript:void(0)" data-theme="megna-dark" class="megna-dark-theme ">12</a></li>
                    </ul>
                    <ul class="m-t-20 chatonline">
                        <li><b>Chat option</b></li>

                    </ul>
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
            registro: { num_docu: '', reg_med: '', name: '', email: '', tipo_user: '', estado: 0 },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
        };
    },
    methods: {
        async ListarDatos() {
            const res = await axios.get("api/seguridad-usuarios");

            $('#example23').DataTable().destroy();

            this.registros = res.data;
            console.log(this.registros)
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
                    const res = await axios.post('api/config-admin-salud', this.registro);
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
                    const res = await axios.put('api/config-admin-salud/' + this.id, this.registro);
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
        mostrarRegistro(data = {}) {
            if (this.actualizar == true) {
                this.tituloModal = "Actualizar el registro: " + data.nom_admin_salud;
                this.id = data.id;
                this.registro.cod_admin_salud = data.cod_admin_salud;
                this.registro.nit_admin_salud = data.nit_admin_salud;
                this.registro.nom_admin_salud = data.nom_admin_salud;
                this.registro.estado = data.estado;
                $('#btnCerralModalForm').click();
            } else {
                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.id = 0;
                this.registro.cod_admin_salud = "";
                this.registro.nit_admin_salud = "";
                this.registro.nom_admin_salud = "";
                this.registro.estado = 1;
                $('#btnCerralModalForm').click();
            }
        },
        btnCerralModalForm() {
            $('#btnCerralModalForm').click();
        }
    }
}
</script>

<style>

</style>