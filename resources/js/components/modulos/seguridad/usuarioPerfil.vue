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
                    <li class="breadcrumb-item active">Perfil</li>
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
            <!-- Column -->
            <div class="col-lg-4 col-xlg-3 col-md-5">
                <div class="card">
                    <div class="card-body">
                        <center class="m-t-30">
                            <img v-if="!registro.imagen_perfil" src="/admin-wrap/assets/images/users/avatar_default.png"
                                :alt="registro.name" class="img-circle" width="150">
                            <img v-else :src="registro.imagen_perfil" :alt="registro.name" class="img-circle" width="150">
                            <h4 class="card-title m-t-10">{{ registro.name }}</h4>
                            <h6 class="card-subtitle">{{ registro.tipo_user }}</h6>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="fileupload btn btn-danger btn-rounded waves-effect waves-light">
                                        <span>
                                            <i class="ion-upload m-r-5"></i>Seleccionar archivo
                                        </span>
                                        <input type="file" class="upload" @change='obtenerArchivo'>
                                    </div>
                                </div>
                            </div>
                        </center>
                    </div>
                    <div>
                        <hr>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">Email address</small>
                        <h6>{{ registro.email }}</h6>
                        <!-- <small class="text-muted p-t-30 db">Teléfono</small>
                        <h6>{{ registro.tel }}</h6>
                        <br />
                        <button class="btn btn-circle btn-secondary"><i class="fa fa-facebook"></i></button>
                        <button class="btn btn-circle btn-secondary"><i class="fa fa-twitter"></i></button>
                        <button class="btn btn-circle btn-secondary"><i class="fa fa-youtube"></i></button> -->
                    </div>
                </div>
            </div>
            <!-- Column -->
            <!-- Column -->
            <div class="col-lg-8 col-xlg-9 col-md-7">
                <div class="card">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs profile-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#settings" role="tab">Ajustes</a>
                        </li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content">
                        <!--second tab -->
                        <div class="tab-pane active" id="settings" role="tabpanel">
                            <div class="card-body">
                                <form class="form-horizontal form-material">
                                    <div class="form-group">
                                        <label class="col-md-6"># Documento</label>
                                        <div class="col-md-6">
                                            <input type="text" id="num_docu" name="num_docu" v-model="registro.num_docu"
                                                placeholder="Ingrese aqui el número de documento"
                                                class="form-control form-control-line">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-12">Nombres</label>
                                        <div class="col-md-12">
                                            <input type="text" id="name" name="name" v-model="registro.name"
                                                placeholder="Ingrese aqui los nombres del usuario"
                                                class="form-control form-control-line">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="example-email" class="col-md-12">Email</label>
                                        <div class="col-md-12">
                                            <input type="email" id="email" email="email" v-model="registro.email"
                                                placeholder="Ingrese aqui el email" class="form-control form-control-line">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-12">Password</label>
                                        <div class="col-md-12">
                                            <input type="password" id="password" name="password" v-model="registro.password"
                                                class="form-control form-control-line">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                            <button type="button" class="btn btn-success m-r-5" @click="actualizarPerfil()">
                                                <i class="fa fa-pencil"></i> Actualizar Perfil
                                            </button>
                                            <!-- <button type="button" class="btn btn-inverse">
                                                <i class="fa fa-reply"></i> Cancel
                                            </button> -->
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Column -->
        </div>
        <!-- ============================================================== -->
        <!-- End PAge Content -->
        <!-- ============================================================== -->
    </div>
</template>

<script>
export default {
    props: ['usuarioactual'],
    mounted() {
        this.obtenerRegistro();
    },
    data() {
        return {
            id: this.usuarioactual.id,
            registro: { id: this.usuarioactual.id, num_docu: '', dependencia_id: this.usuarioactual.dependencia_id, name: '', email: '', password: '', estado: 0, imagen_perfil: null },
            errores: {},
        };
    },
    methods: {
        async obtenerRegistro() {
            try {
                const res = await axios.get("/seguridad-usuarios/" + this.id);

                if (res.status == 200) {
                    this.registro.num_docu = res.data.usuario.num_docu;
                    this.registro.name = res.data.usuario.name;
                    this.registro.email = res.data.usuario.email;
                }
            } catch (error) {
                this.errores = error.response.data.errors;
            }
        },
        async actualizarPerfil() {
            try {

                let registro = new FormData();
                for (let key in this.registro) {
                    registro.append(key, this.registro[key]);
                }

                const res = await axios.put('/seguridad-actualizar-perfil/' + this.id, this.registro);
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
                }
            } catch (error) {
                console.log(error);
                this.errores = error.response.data.errors;
            }
        },
        obtenerArchivo(e) {
            this.registro.imagen_perfil = e.target.files[0];
        },
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