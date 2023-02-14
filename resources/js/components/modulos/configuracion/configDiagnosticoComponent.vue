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
                    <li class="breadcrumb-item active">Díagnosticos</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button type="button" class="btn btn-info btnDesplegarRigthSidebar"
                    @click="actualizar=false;mostrarRegistro()">
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
            <!-- <div class="">
                    <button class="right-side-toggle btn-info btn btn-circle btn-sm">
                        <i class="fa fa-plus-circle m-r-5"></i>
                    </button>
                </div> -->
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
                        <h4 class="card-title">Listado de díagnostico</h4>
                        <h6 class="card-subtitle">Administración de díagnostico para la prestación del servicio de
                            imagenes diagnosticas.</h6>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>Código CIE10</th>
                                        <th width="150px">Díagnostico</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.cod_diagnos }}</td>
                                        <td>{{ item.nom_diagnos }}</td>
                                        <td>
                                            <span class="label label-success" v-if="item.estado">Activo</span>
                                            <span class="label label-danger" v-if="!item.estado">Inactivo</span>
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
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Código CEE10</label>
                                        <input type="text" id="cod_diagnos" name="cod_diagnos"
                                            v-model="registro.cod_diagnos" class="form-control"
                                            placeholder="Código CIE10">
                                        <span class="text-danger"
                                            v-if="errores.cod_diagnos">{{errores.cod_diagnos[0]}}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Nombre del díagnostico</label>
                                        <input type="text" id="nom_diagnos" name="nom_diagnos"
                                            v-model="registro.nom_diagnos" class="form-control"
                                            placeholder="Ingrese aquí el nombre del díagnostico">
                                        <span class="text-danger"
                                            v-if="errores.nom_diagnos">{{errores.nom_diagnos[0]}}</span>
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
            registro: { cod_diagnos: '', nom_diagnos: '', estado: 0 },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
        };
    },
    methods: {
        async ListarDatos() {
            const res = await axios.get("api/config-diagnosticos");

            $('#example23').DataTable().destroy();

            this.registros = res.data;
            console.log(res.data)
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
                    const res = await axios.post('api/config-diagnosticos', this.registro);

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
                    const res = await axios.put('api/config-diagnosticos/' + this.id, this.registro);
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
                    axios.delete('api/config-diagnosticos/' + id).then(response => {
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
                this.tituloModal = "Actualizar el registro: " + data.nom_diagnos;
                this.id = data.id;
                this.registro.cod_diagnos = data.cod_diagnos;
                this.registro.nom_diagnos = data.nom_diagnos;
                this.registro.estado = data.estado;
                $('#btnCerralModalForm').click();
            } else {
                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.id = 0;
                this.registro.cod_diagnos = "";
                this.registro.nom_diagnos = "";
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