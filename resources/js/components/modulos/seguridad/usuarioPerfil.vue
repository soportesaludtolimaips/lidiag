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
                        </center>
                    </div>
                    <div>
                        <hr>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">Email address</small>
                        <h6>{{ registro.email }}</h6>
                        <small class="text-muted p-t-30 db">Teléfono</small>
                        <h6>{{ registro.tel }}</h6>
                        <br />
                        <button class="btn btn-circle btn-secondary"><i class="fa fa-facebook"></i></button>
                        <button class="btn btn-circle btn-secondary"><i class="fa fa-twitter"></i></button>
                        <button class="btn btn-circle btn-secondary"><i class="fa fa-youtube"></i></button>
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
                            <a class="nav-link active" data-toggle="tab" href="#home" role="tab">Linea de tiempo</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#settings" role="tab">Ajustes</a>
                        </li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content">
                        <div class="tab-pane active" id="home" role="tabpanel">
                            <div class="card-body">
                                <div class="profiletimeline">
                                    <div class="sl-item">
                                        <div class="sl-left">
                                            <img src="/admin-wrap/assets/images/users/1.jpg" alt="user"
                                                class="img-circle" />
                                        </div>
                                        <div class="sl-right">
                                            <div>
                                                <a href="#" class="link">John Doe</a>
                                                <span class="sl-date">
                                                    5 minutes ago
                                                </span>
                                                <p>assign a new task <a href="#"> Design weblayout</a></p>
                                                <div class="row">
                                                    <div class="col-lg-3 col-md-6 m-b-20">
                                                        <img src="/admin-wrap/assets/images/big/img1.jpg"
                                                            class="img-responsive radius" />
                                                    </div>
                                                    <div class="col-lg-3 col-md-6 m-b-20">
                                                        <img src="/admin-wrap/assets/images/big/img2.jpg"
                                                            class="img-responsive radius" />
                                                    </div>
                                                    <div class="col-lg-3 col-md-6 m-b-20">
                                                        <img src="/admin-wrap/assets/images/big/img3.jpg"
                                                            class="img-responsive radius" />
                                                    </div>
                                                    <div class="col-lg-3 col-md-6 m-b-20">
                                                        <img src="/admin-wrap/assets/images/big/img4.jpg"
                                                            class="img-responsive radius" />
                                                    </div>
                                                </div>
                                                <div class="like-comm">
                                                    <a href="javascript:void(0)" class="link m-r-10">2
                                                        comment
                                                    </a>
                                                    <a href="javascript:void(0)" class="link m-r-10">
                                                        <i class="fa fa-heart text-danger"></i> 5 Love
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                </div>
                            </div>
                        </div>
                        <!--second tab-->
                        <div class="tab-pane" id="settings" role="tabpanel">
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
                                        <label class="col-md-6">Registro Médico</label>
                                        <div class="col-md-6">
                                            <input type="text" id="reg_medico" name="reg_medico"
                                                v-model="registro.reg_medico"
                                                placeholder="Ingrese aqui el número del registro médico"
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
                                            <input type="password" id="password" name="password"
                                                class="form-control form-control-line">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-12">Teléfono</label>
                                        <div class="col-md-12">
                                            <input type="text" id="tel" tel="tel" v-model="registro.tel"
                                                :placeholder="registro.tel" class="form-control form-control-line">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-12">
                                            <button class="btn btn-success m-r-5">
                                                <i class="fa fa-pencil"></i> Actualizar Perfil
                                            </button>
                                            <button type="button" class="btn btn-inverse">
                                                <i class="fa fa-reply"></i> Cancel
                                            </button>
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
    mounted() {
        this.obtenerRegistro();
    },
    data() {
        return {
            id: this.$attrs.id,
            registro: { num_docu: '', reg_med: '', name: '', email: '', tel: '', facebook: '', twitter: '', youtube: '', tipo_user: '', password: '', estado: 0, imagen_perfil: '' },
            errores: {},
        };
    },
    methods: {
        async obtenerRegistro() {
            try {
                const res = await axios.get("/seguridad-usuarios/", { 'id': this.id });
                console.log("*****************************************************");
                console.log(this.id)
                console.log("*****************************************************");
                console.log(res.data)
                console.log("*****************************************************");
                if (res.status == 200) {
                    this.registro.tipo_user = res.data.tipo_user;
                    this.registro.num_docu = res.data.num_docu;
                    this.registro.reg_med = res.data.reg_med;
                    this.registro.name = res.data.name;
                    this.registro.email = res.data.email;
                    this.registro.tel = res.data.tel;
                    this.registro.facebook = res.data.facebook;
                    this.registro.twitter = res.data.twitter;
                    this.registro.youtube = res.data.youtube;
                    this.registro.estado = res.data.estado;
                }

            } catch (error) {
                console.log(error);
                this.errores = error.response.data.errors;
            }

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