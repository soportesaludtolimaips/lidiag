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
                    <li class="breadcrumb-item active">Tipos de plantillas</li>
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
                        <h4 class="card-title">Listado de tipos de plantillas</h4>
                        <h6 class="card-subtitle">Administración de las tipos de plantillas.</h6>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th width="150px">Tipo de Plantilla</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.nom_admin_salud }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado">Activo</span>
                                            <span class="label label-danger" v-if="!item.estado">Inactivo</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                @click="actualizar = true; mostrarRegistro(item)">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="elimnarRegistro(item.id, item.nom_admin_salud)">
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
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Código</label>
                                        <input type="text" id="nom_tipo" name="nom_tipo" v-model="registro.nom_tipo"
                                            class="form-control" placeholder="Código">
                                        <span class="text-danger" v-if="errores.nom_tipo">{{
                                            errores.nom_tipo[0]
                                        }}</span>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Producto</label>
                                        <select v-model="registro.producto_id" name="producto_id" id="producto_id"
                                            class="form-control custom-select">
                                            <option v-for="(Producto, index) in prioridades" :key="index"
                                                :value="Producto.id"> {{ Producto.nom_produc }}
                                            </option>
                                        </select>
                                        <span class="text-danger" v-if="errores.producto_id">
                                            {{ errores.producto_id[0] }}
                                        </span>
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
        this.listarProductos();
    },
    data() {
        return {
            id: 0,
            registros: [],
            registro: { nom_tipo: '', producto_id: '' },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
            productos: []
        };
    },
    methods: {
        async ListarDatos() {
            const res = await axios.get("/config-tipos-plantillas")

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
                    const res = await axios.post('/config-tipos-plantillas', this.registro);

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
                    const res = await axios.put('/config-tipos-plantillas/' + this.id, this.registro);
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
        elimnarRegistro(id, nom) {
            var $this = this;
            swal({
                title: "Estas seguro de eliminar la admninistradora de salud " + nom + "?",
                text: "No podrás recuperar este registro!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "si, eliminarlo!",
                closeOnConfirm: false
            }, function (isConfirm) {
                if (isConfirm) {
                    axios.delete('/config-tipos-plantillas/' + id).then(response => {
                        swal("Ok!!!!", response.data.message, "success");
                        $this.ListarDatos()
                    }).catch(error => {
                        console.log(error)
                    })
                }
            });
        },
        async listarProductos() {
            try {
                const res = await axios.get("/config-productos");
                this.productos = res.data;
                this.$nextTick(() => {
                    $(this.$refs.select).select2();
                });
            } catch (error) {
                console.log(error);
            }
        },
        mostrarRegistro(data = {}) {
            if (this.actualizar == true) {
                this.tituloModal = "Actualizar el registro: " + data.nom_tipo;
                this.id = data.id;
                this.registro.producto_id = data.producti_id;
                this.registro.nom_tipo = data.nom_tipo;
                this.registro.estado = data.estado;
            } else {
                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.id = 0;
                this.registro.producto_id = "";
                this.registro.nom_tipo = "";
                this.registro.estado = 1;
            }

            this.btnCerralModalForm();
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