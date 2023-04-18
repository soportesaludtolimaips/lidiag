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
                                                    src="/admin-wrap/assets/images/users/avatar_default.png"
                                                    :alt="item.name" class="img-circle" height="40" width="40">
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
                                            <router-link
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                :to="{ name: 'seguridad.usuario.perfil', params: { id: item.id } }">
                                                <i class="fa fa-eye"></i>
                                            </router-link>

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
                                        <input type="text" id="name" name="name" v-model="registro.name"
                                            class="form-control" placeholder="Ingrese aquí el los nombres del usaurio">
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
                                        <label class="control-label">confirmar contraseña</label>

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

                            <p class="text-info"><i class="fa fa-unlock"></i> Roles</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="controls">
                                            <div v-for="itemRol in registro.rolesUsuario" :key="itemRol.id">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" :id="itemRol.name"
                                                        v-model="itemRol.checked">
                                                    <label class="custom-control-label" :for="itemRol.name">
                                                        {{ itemRol.name }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 m-b-20">
                                <div class="fileupload btn btn-danger btn-rounded waves-effect waves-light">
                                    <span>
                                        <i class="ion-upload m-r-5"></i>Subir Avatar
                                    </span>
                                    <input type="file" class="upload" @change="obtenerArchivo($event)">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
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
            roles: [],
            registro: { num_docu: '', reg_med: '', name: '', email: '', tipo_user: '', password: '', estado: 0, imagen_perfil: '', rolesUsuario: [] },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
        };
    },
    methods: {
        async ListarDatos() {
            const res = await axios.get("/seguridad-usuarios");
            const resRoles = await axios.get("/seguridad-roles")

            $('#example23').DataTable().destroy();

            this.registros = [];
            this.roles = [];
            this.registro.rolesUsuario = [];

            this.registros = res.data;
            this.roles = resRoles.data[0];
            this.listarRoles();

            this.$nextTick(() => {
                $('#example23').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });
            });
        },
        listarRoles() {
            let me = this;

            me.roles.map(function (x, y) {
                me.registro.rolesUsuario.push({
                    "id": x.id,
                    "name": x.name,
                    "checked": false,
                });
            });
        },
        async guardarRegistro() {

            var formData = new FormData();

            try {
                if (this.actualizar === false) {
                    /* formData.append('tipo_user', this.registro.tipo_user);
                    formData.append('num_docu', this.registro.num_docu);
                    formData.append('reg_med', this.registro.reg_med);
                    formData.append('name', this.registro.name);
                    formData.append('email', this.registro.email);
                    formData.append('estado', this.registro.estado);
                    formData.append('password', this.registro.password);
                    formData.append("file", this.registro.imagen_perfil);
                    formData.append("rolesUsuario", this.registro.rolesUsuario); */

                    //const config = { headers: { 'Content-Type': 'multipart/form-data' } }
                    const res = await axios.post('/seguridad-usuarios', this.registro);

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

                    const config = { headers: { 'Content-Type': 'multipart/form-data' } }
                    const res = await axios.put('/seguridad-usuarios/' + this.id, { registro: this.registro, form_data: formData });
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

            this.ListarDatos();
            this.limpiarFormulario();
            this.btnCerralModalForm();
        },
        async cambiarEstado(estado, data = {}) {
            var $this = this;

            swal({
                title: "Estas seguro de que desea " + ((estado == 0) ? ' activar' : 'desactivar') + ' el usaurio ' + data.name + '?',
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: ((estado == 0) ? ' Si, activar' : 'Si, desactivar'),
                closeOnConfirm: false
            }, function (isConfirm) {
                if (isConfirm) {
                    axios.put('/seguridad-usuarios/estado/' + data.id, { estado: ((estado == 0) ? 1 : 0) }).then(response => {

                        swal("Ok!!!!", 'el usaurio ' + data.name + ' se ' + ((estado == 0) ? ' activo' : 'desactivo') + ' correctamente',
                            "success");

                        $this.ListarDatos()
                    }).catch(error => {
                        console.log(error)
                    })
                }
            });
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

                /**
                 * Listo los los datos del usuario con sus respectivos roles
                 * Recorro los roles que estan en en Frond y los desactivo para
                 * marcar los roles del usuario a editar
                 */
                axios.get('/seguridad-usuarios/' + this.id).then(res => {

                    const RolesDelUsuario = res.data.RolesDeUsuario;
                    let me = this;

                    me.roles.forEach(function (roles, index) {
                        me.registro.rolesUsuario[index].checked = false;
                    });

                    RolesDelUsuario.forEach(function (RolesDelUsuario) {
                        let rolActivo = me.roles.findIndex((x) => x.id == RolesDelUsuario);
                        me.registro.rolesUsuario[rolActivo].checked = true;
                    });
                });
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
        },
        limpiarFormulario() {
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