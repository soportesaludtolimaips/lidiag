<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Estudios</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Estudios</a>
                    </li>
                    <li class="breadcrumb-item active">Leer Estudios</li>
                </ol>
            </div>

            <div class="">
                <button class="right-side-toggle btn-info btn btn-circle btn-sm">
                    <i class="fa fa-plus-circle m-r-5"></i>
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
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros para busqueda de mis pendientes
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Nombres de paciente o # de documento
                                            </label>
                                            <input type="text" id="bus_nom_num_docu" name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu" class="form-control"
                                                placeholder="Nombres de paciente o # de documento">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input type="date" id="nom_diagnos" name="nom_diagnos"
                                                v-model="busqueda.fehc_ini" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" id="fecha_fin" name="fecha_fin"
                                                v-model="busqueda.fecha_fin" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button type="button" class="btn btn-success m-r-5" @click="buscarStudy()">
                                        <i class="fa fa-search"></i> Buscar
                                    </button>

                                    <button type="button" class="btn btn-inverse" @click="btnLimpiar()">
                                        <i class="fa fa-refresh"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de mis pendientes por leer</h4>
                        <div class="table-responsive m-t-40">
                            <table id="example23" class="display nowrap table table-hover table-striped table-bordered"
                                cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>Fecha del Toma</th>
                                        <th>Quien Asigno</th>
                                        <th>Paciente</th>
                                        <th>Detalle</th>
                                        <th>Producto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in registros" :key="index">
                                        <td>{{ item.fec_estudio }}</td>
                                        <td>{{ item.quien_registro.name }}</td>
                                        <td>{{ item.paciente.num_docu }} <br /> {{ item.paciente.nombres }}</td>
                                        <td>{{ item.study_desc }} <br /> {{ item.accession_no }}</td>
                                        <td>{{ item.productos.nom_produc }}</td>
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
        <div class="">
            <button
                class="right-side-toggle waves-effect waves-light btn-inverse btn btn-circle btn-sm pull-right m-l-10">
                <i class="ti-settings text-white"></i>
            </button>
        </div>
        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->

        <!-- ============================================================== -->
        <!-- End Right sidebar -->
        <!-- ============================================================== -->
    </div>
</template>

<script>
export default {
    mounted() {
        this.listarMisPendientes();
    },
    data() {
        return {
            id: 0,
            registros: [],
            tituloModal: 'Nuevo registro',
            registro: {
                study_pk: '', study_iuid: '', study_datetime: '', study_id: '', accession_no: '', study_desc: '', observaciones: '', medico_id: '', prioridad_id: '', num_docu: '', nombres: '', sexo: '', fec_naci: '', email: '', direccion: '', telefono: '', productosEstudio: [], diagnosticosEstudio: [],
            },
            busqueda: { bus_nom_num_docu: '5860691', fehc_ini: '', fecha_fin: '' },
            errores: {},

        };
    },
    methods: {
        /* async buscarStudy() {
            try {
                const res = await axios.post('api/study.listarEstudios', this.busqueda);

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
            } catch (error) {
                console.log(error);
                this.errores = error.response.data.errors;
            }
        }, */
        async listarMisPendientes() {
            try {
                const res = await axios.get('api/estudio-listarPendientesMedico?id=' + this.usuarioActua.id);
                $('#example23').DataTable().destroy();

                this.registros = res.data;
                console.log(this.registros.fec_estudio);
                this.$nextTick(() => {
                    $('#example23').DataTable({
                        dom: 'Bfrtip',
                        buttons: [
                            'copy', 'csv', 'excel', 'pdf', 'print'
                        ]
                    });
                });
            } catch (error) {
                console.log(error);
            }
        },
        /* mostrarRegistro(data = {}) {
            this.tituloModal = "Agendar al paciente: " + data.pat_name;
            this.id = data.id;
            this.registro.study_pk = data.study_pk;
            this.registro.study_iuid = data.study_iuid;
            this.registro.study_id = data.study_id;
            this.registro.study_datetime = data.study_datetime;
            this.registro.accession_no = data.accession_no;
            this.registro.study_desc = data.study_desc;

            this.registro.num_docu = data.pat_id;
            this.registro.nombres = data.pat_name;
            this.registro.sexo = data.pat_sex;
            this.registro.fec_naci = data.pat_birthdate;
            $('#btnCerralModalForm').click();
            this.errores = [];

        }, */
        btnCerralModalForm() {
            $('#btnCerralModalForm').click();
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