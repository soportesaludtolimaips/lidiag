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
                    <li class="breadcrumb-item active">Gestión de Roles</li>
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
                        <h4 class="card-title">Listado de roles</h4>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Rol</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.name }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado == 1">Activo</span>
                                            <span class="label label-danger" v-if="item.estado == 0">Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5">
                                                <i class="fa fa-eye"></i>
                                            </button>

                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="actualizar = true; mostrarRegistro(item)">
                                                <i class="fa fa-pencil"></i>
                                            </button>

                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                @click="elimnarRegistro(item.id)">
                                                <i class="fa fa-trash"></i>
                                            </button>

                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-success btn-sm"
                                                @click="cambiarEstado(1, item)" v-if="item.estado == 1">
                                                <i class="fa fa-unlock"></i>
                                            </button>

                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="cambiarEstado(0, item)" v-if="item.estado == 0">
                                                <i class="fa fa-lock"></i>
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
                    <span @click="btnCerralModalForm()">
                        <i class="ti-close right-side-toggle"></i>
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
                                        <label class="control-label">Nombre del Rol</label>
                                        <input type="text" id="name" name="name" v-model="registro.name"
                                            class="form-control" placeholder="Ingrese aqui el nombre del rol">
                                        <span class="text-danger" v-if="errores.name">{{
                                            errores.name[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <p class="text-info">
                                <i class="fa fa-unlock"></i> Permisos
                            </p>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-success">
                                            <input type="checkbox" id="estado" name="estado" v-model="registro.permiso">
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
    mounted() {
        this.ListarDatos();
    },
    data() {
        return {
            id: 0,
            registros: [],
            permisos: [],
            registro: { name: '', permisos: [] },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
        };
    },
    methods: {
        async ListarDatos() {

            const res = await axios.get("api/seguridad-roles");

            $('#example23').DataTable().destroy();

            this.registros = res.data[0];
            this.permisos = res.data[1];

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

            var formData = new FormData();

            try {
                if (this.actualizar === false) {

                    const res = await axios.post('api/seguridad-roles', this.registro);

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

                    const res = await axios.put('api/seguridad-roles/' + this.id, { registro: this.registro });
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

            this.limpiarFormulario();
        },
        mostrarRegistro(data = {}) {
            if (this.actualizar == true) {
                this.tituloModal = "Actualizar el registro: " + data.name;
                this.id = data.id;
                this.registro.name = data.name;
            } else {



                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.id = 0;
                this.registro.name = "";
            }

            this.btnCerralModalForm();
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
        limpiarFormulario() {
            this.actualizar = false;
            this.tituloModal = "Nuevo registro";
            this.id = 0;
            this.registro.name = "";
        }
    }
}
</script>

<style scoped>
.right-sidebar {
    position: fixed;
    right: -25%;
    width: 25%;
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
    width: 25%;
    display: block;
}
</style>