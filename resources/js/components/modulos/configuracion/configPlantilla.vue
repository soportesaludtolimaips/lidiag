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
                    <li class="breadcrumb-item active">Plantillas</li>
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
                        <h4 class="card-title">Listado de plantillas</h4>
                        <h6 class="card-subtitle">Administración de las tipos de plantillas.</h6>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th width="150px">Tipo de Plantilla</th>
                                        <th>Plantilla</th>
                                        <th>Medico</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.plantilla_tipo.nom_tipo }}</td>
                                        <td>{{ item.nom_plantilla }}</td>
                                        <td>{{ item.medico.name }}</td>
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
                                                @click="elimnarRegistro(item.id, item.nom_plantilla)">
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
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Nombre de la plantilla</label>
                                        <input type="text" id="nom_plantilla" name="nom_plantilla"
                                            v-model="registro.nom_plantilla" class="form-control"
                                            placeholder="Nombre de la plantilla">
                                        <span class="text-danger" v-if="errores.nom_plantilla">{{
                                            errores.nom_plantilla[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Medico</label>
                                        <select class="form-control custom-select" style="width: 100%; height: 36px"
                                            v-model="registro.medico_id" name="medico_id">
                                            <option>Seleccione el médico</option>
                                            <option v-for="ItemMedico in medicos" v-bind:key="ItemMedico"
                                                v-bind:value="ItemMedico.id">
                                                {{ ItemMedico.name }}
                                            </option>
                                            <span class="text-danger" v-if="errores.nom_plantilla">{{
                                                errores.medico_id[0]
                                            }}</span>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Tipo de plantilla</label>
                                        <select class="form-control custom-select" style="width: 100%; height: 36px"
                                            v-model="registro.plantilla_tipo_id" name="plantilla_tipo_id">
                                            <option>Seleccione el médico</option>
                                            <option v-for="ItemTipoPlantilla in tiposPlantillas"
                                                v-bind:key="ItemTipoPlantilla" v-bind:value="ItemTipoPlantilla.id">
                                                {{ ItemTipoPlantilla.nom_tipo }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Plantillas</label>
                                        <textarea id="plantilla" name="plantilla" v-model="registro.plantilla"
                                            class="form-control" placeholder="Descripción de la plantilla"
                                            rows="9"></textarea>
                                        <span class="text-danger" v-if="errores.plantilla">{{
                                            errores.plantilla[0]
                                        }}</span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Opinion</label>
                                        <textarea id="opinion" name="opinion" v-model="registro.opinion"
                                            class="form-control" placeholder="Opinion" rows="5"></textarea>
                                        <span class="text-danger" v-if="errores.opinion">{{
                                            errores.opinion[0]
                                        }}</span>
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
        this.listarMedicos(),
            this.listarTiposPlantillas();
    },
    data() {
        return {
            id: 0,
            registros: [],
            registro: { plantilla_tipo_id: '', medico_id: '', nom_plantilla: '', plantilla: '', opinion: '', estado: 1 },
            tituloModal: 'Nuevo registro',
            actualizar: false,
            errores: {},
            medicos: [],
            tiposPlantillas: []
        };
    },
    methods: {
        async ListarDatos() {
            const res = await axios.get("/config-plantillas")

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
            console.log('actualizar', this.actualizar)
            try {
                if (this.actualizar === false) {

                    const res = await axios.post('/config-plantillas', this.registro);

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

                        this.ListarDatos()
                        this.limpiarFormulario();
                        this.btnCerralModalForm();
                    }
                } else {
                    const res = await axios.put('/config-plantillas/' + this.id, this.registro);
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

                        this.ListarDatos()
                        this.limpiarFormulario();
                        this.btnCerralModalForm();
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
                title: "Estas seguro de eliminar el tipo de plantillas " + nom + "?",
                text: "No podrás recuperar este registro!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "si, eliminarlo!",
                closeOnConfirm: false
            }, function (isConfirm) {
                if (isConfirm) {
                    axios.delete('/config-plantillas/' + id).then(response => {
                        swal("Ok!!!!", response.data.message, "success");
                        $this.ListarDatos()
                    }).catch(error => {
                        console.log(error)
                    })
                }
            });
        },
        async listarMedicos() {
            try {
                const res = await axios.get("/user.listarUsuarios/Medico/2");
                this.medicos = res.data;
            } catch (error) {
                console.log(error);
            }
        },
        async listarTiposPlantillas() {
            try {
                const res = await axios.get("/config-tipos-plantillas/listar/activos");
                this.tiposPlantillas = res.data;
            } catch (error) {
                console.log(error);
            }
        },
        mostrarRegistro(data = {}) {
            if (this.actualizar == true) {
                this.tituloModal = "Actualizar el registro: " + data.nom_plantilla;
                this.id = data.id;
                this.registro.plantilla_tipo_id = data.plantilla_tipo_id;
                this.registro.medico_id = data.medico_id;
                this.registro.nom_plantilla = data.nom_plantilla;
                this.registro.plantilla = data.plantilla;
                this.registro.opinion = data.opinion;
                this.registro.estado = data.estado;
            } else {
                this.actualizar = false;
                this.tituloModal = "Nuevo registro";
                this.id = 0;
                this.registro.plantilla_tipo_id = "";
                this.registro.medico_id = "";
                this.registro.nom_plantilla = "";
                this.registro.plantilla = "";
                this.registro.opinion = "";
                this.registro.estado = 1;
            }

            this.btnCerralModalForm();
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").toggleClass("shw-rside");
        },
        limpiarFormulario() {
            this.errores = {};
            this.registro.plantilla_tipo_id = "";
            this.registro.medico_id = "";
            this.registro.nom_plantilla = "";
            this.registro.plantilla = "";
            this.registro.opinion = "";
            this.registro.estado = 1;
        },
    },
};
</script>

<style scoped>
.right-sidebar {
    position: fixed;
    right: -80%;
    width: 80%;
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
    width: 80%;
    display: block;
}
</style>