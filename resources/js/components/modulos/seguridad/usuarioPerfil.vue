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
                    <li class="breadcrumb-item active">Mi Perfil</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info btnDesplegarRigthSidebar">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Start Page Content -->
        <!-- ============================================================== -->
        <!-- Row -->
        <div class="row">
            <!-- Column -->
            <div class="col-lg-4 col-xlg-3 col-md-5">
                <div class="card">
                    <div class="card-body">
                        <div class="m-t-30">
                            <img src="/admin-wrap/assets/images/users/5.jpg" class="img-circle" width="150" />
                            <h4 class="card-title m-t-10">Hanna Gover</h4>
                            <h6 class="card-subtitle">Accoubts Manager Amix corp</h6>
                            <div class="row text-center justify-content-md-center">
                                <div class="col-4">
                                    <a href="javascript:void(0)" class="link">
                                        <i class="icon-people">

                                        </i>

                                    </a>
                                </div>
                                <div class="col-4">
                                    <a href="javascript:void(0)" class="link">
                                        <i class="icon-picture"></i>

                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <hr>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">Email address </small>
                        <h6>hannagover@gmail.com</h6>
                        <small class="text-muted p-t-30 db">Phone</small>
                        <h6>+91 654 784 547</h6>
                        <small class="text-muted p-t-30 db">Address</small>
                        <h6>71 Pilgrim Avenue Chevy Chase, MD 20815</h6>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <!-- Column -->

            <!-- Column -->
        </div>
        <!-- Row -->
        <!-- ============================================================== -->
        <!-- End PAge Content -->
        <!-- ============================================================== -->
    </div>
</template>

<script>

export default ({
    mounted() {
        this.ListarDatos();

    },
    data() {
        return {
            id: this.$attrs.id,
            registro: { num_docu: '', reg_med: '', name: '', email: '', tipo_user: '', password: '', estado: 0, imagen_perfil: '' },
            errores: {},
        }
    },
    methods: {
        async ListarDatos() {
            const res = await axios.get("api/seguridad-usuarios");
            console.log(res)

        },
    },

})
</script>

