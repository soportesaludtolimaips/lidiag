
<template>
    
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
                                    <td>
                                        <div class="user-img">
                                            <img v-if="!item.imagen_perfil"
                                                src="/admin-wrap/assets/images/users/avatar_default.png" :alt="item.name"
                                                class="img-circle" height="40" width="40">
                                            <img v-else :src="item.imagen_perfil" :alt="item.name" class="img-circle"
                                                height="40" width="40">
                                        </div>
                                    </td>
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label"># Documento</label>
                                    <input type="text" id="num_docu" name="num_docu" v-model="registro.num_docu"
                                        class="form-control" placeholder="Número de documeto del usuario">
                                    <span class="text-danger" v-if="errores.num_docu">{{
                                        errores.num_docu[0]
                                    }}</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Reg. Médico</label>
                                    <input type="text" id="reg_med" name="reg_med" v-model="registro.reg_med"
                                        class="form-control" placeholder="Registro médico en caso de ser médico">
                                    <span class="text-danger" v-if="errores.reg_med">{{
                                        errores.reg_med[0]
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Nombres</label>
                                    <input type="text" id="name" name="name" v-model="registro.name" class="form-control"
                                        placeholder="Ingrese aquí el los nombres del usaurio">
                                    <span class="text-danger" v-if="errores.name">{{
                                        errores.name[0]
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">E - Mail</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="ti-email"></i>
                                            </span>
                                        </div>
                                        <input type="email" id="email" name="email" v-model="registro.email"
                                            class="form-control" placeholder="Ingrese aquí E - Mail del usuario">
                                    </div>

                                    <span class="text-danger" v-if="errores.email">{{
                                        errores.email[0]
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Contraseña</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="ti-lock"></i></span>
                                        </div>
                                        <input type="password" id="password" name="password" v-model="registro.password"
                                            class="form-control" placeholder="Contraseña">
                                    </div>

                                    <span class="text-danger" v-if="errores.password">{{
                                        errores.password[0]
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">configrmar contraseña</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="ti-lock"></i></span>
                                        </div>
                                        <input type="password" id="confirmarPassword" name="confirmarPassword"
                                            v-model="registro.confirmarPassword" class="form-control"
                                            placeholder="Confirmar contraseña">
                                    </div>

                                    <span class="text-danger" v-if="errores.confirmarPassword">{{
                                        errores.confirmarPassword[0]
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Imagen de perfil</label>
                                    <input type="file" class="form-control" @change="obtenerArchivo($event)">
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
            registro: { num_docu: '', reg_med: '', name: '', email: '', tipo_user: '', password: '', estado: 0, imagen_perfil: '' },
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
            var formData = new FormData();

            try {
                if (this.actualizar === false) {
                    formData.append('tipo_user', this.registro.tipo_user);
                    formData.append('num_docu', this.registro.num_docu);
                    formData.append('reg_med', this.registro.reg_med);
                    formData.append('name', this.registro.name);
                    formData.append('email', this.registro.email);
                    formData.append('estado', this.registro.estado);
                    formData.append('password', this.registro.password);
                    formData.append("file", this.registro.imagen_perfil);

                    //const config = { headers: { 'Content-Type': 'multipart/form-data' } }
                    const res = await axios.post('api/seguridad-usuarios', formData);

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

                    formData.append("file", this.registro.imagen_perfil);

                    // const config = { headers: { 'Content-Type': 'multipart/form-data' } }
                    const res = await axios.put('api/seguridad-usuarios/' + this.id, {registro: this.registro, fie: formData});
                    if (res.status == 200) {

                        //this.ListarDatos()

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
        obtenerArchivo(event) {
            this.registro.imagen_perfil = event.target.files[0];
        },
        mostrarRegistro(data = {}) {
            if (this.actualizar == true) {
                this.tituloModal = "Actualizar el registro: " + data.name;
                this.id = data.id;
                this.registro.tipo_user = data.tipo_user;
                this.registro.num_docu = data.num_docu;
                this.registro.reg_med = data.reg_med;
                this.registro.name = data.name;
                this.registro.email = data.email;
                this.registro.estado = data.estado;
            } else {
                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.id = 0;
                this.registro.tipo_user = '';
                this.registro.num_docu = "";
                this.registro.reg_med = "";
                this.registro.name = "";
                this.registro.email = "";
                this.registro.password = "";
                this.registro.estado = 1;
            }

            this.btnCerralModalForm();
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
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