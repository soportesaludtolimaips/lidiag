<template>
    <div>
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Citas</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Citas</a>
                    </li>
                    <li class="breadcrumb-item active">Gestión de citas</li>
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
                        <h4 class="card-title text-themecolor"><i class="fa fa-filter"></i> Filtros para busqueda de
                            estudios</h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input type="date" id="bus_fehc_ini" name="bus_fehc_ini"
                                                v-model="busqueda.bus_fehc_ini" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input type="date" id="bus_fecha_fin" name="bus_fecha_fin"
                                                v-model="busqueda.bus_fecha_fin" class="form-control" />
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
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de disponibilidades</h4>
                        <h6 class="card-subtitle text-info">Gestione las disponibilidades de citas para estudios.
                        </h6>
                        <div class="table-responsive">
                            <table id="example23" class="table table-bordered table-striped" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th with='5%'>Fecha Inicio</th>
                                        <th with='5%'>Fecha Fin</th>
                                        <th with='30%'>Sede</th>
                                        <th>Servicio</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- <tr v-for="(item, index) in registros" :key="item.id" :id="item.study_pk"
                                        :class="{ 'text-info': item.agendado !== 0 }">
                                        <td>{{ item.study_datetime }}</td>
                                        <td>{{ item.pat_id }}</td>
                                        <td>{{ item.pat_name }}</td>
                                        <td>{{ item.accession_no }}</td>
                                        <td>{{ item.study_desc }}</td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="mostrarRegistro(item, index);" title="Ordenar estudio">
                                                <i class=" fa fa-child"></i>
                                            </button>
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="verImagen(item.pat_id, item.study_iuid)" title="Ver imagenes">
                                                <i class="fa fa-file-photo-o"></i>
                                            </button>
                                        </td>
                                    </tr> -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar" id="right-sidebar"
            style="overflow-x: hidden; overflow-y: scroll; overflow-x: hidden; height: 100%;">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" id="btnCerralModalForm" @click="cerrarModal()"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos de la disponibilidad
                                    </h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Total de citas</label>
                                                <input type="number" id="total_citas" name="total_citas"
                                                    v-model="registro.total_citas" class="form-control" />
                                                <!-- <span class="text-danger" v-if="errores.total_citas">
                                                    {{ errores.total_citas[0] }}
                                                </span> -->
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Minutos Por Citas</label>
                                                <input type="number" id="minutos_citas" name="minutos_citas"
                                                    v-model="registro.minutos_citas" class="form-control"
                                                    placeholder="Minutos por cita" />
                                                <!-- <span class="text-danger" v-if="errores.minutos_citas">
                                                    {{ errores.minutos_citas[0] }}
                                                </span> -->
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Servicio</label>
                                                <select v-model="registro.servicio_id" name="servicio_id"
                                                    id="servicio_id" class="form-control custom-select">
                                                    <option v-for="(ItemServicio, index) in servicios" :key="index"
                                                        :value="ItemServicio.id">
                                                        {{ ItemServicio.modulo }}
                                                    </option>
                                                </select>
                                                <span class="text-danger" v-if="errores.servicio_id">
                                                    {{ errores.servicio_id[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Fec. Inicio</label>
                                                <input type="date" id="fec_naci" name="fec_naci"
                                                    v-model="registro.fec_naci" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()">
                                    <i class="fa fa-check"></i> Guardar
                                </button>

                                <button type="button" class="btn btn-inverse" @click="cerrarModal()">Cancel</button>
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
    props: ['usuarioactual'],
    mounted() {
        this.establecerSede();
        this.listarServicios();

        this.emitter.on("sedeSeleccionada", (data) => {
            this.establecerSede();
            $("#example23").DataTable().destroy();
        });
    },
    data() {
        return {
            tituloModal: 'Guardar disponibilidad de citas',
            registro: { sede_id: this.sedeActual, quien_registro_id: this.usuarioactual.id, servicio_id: "" },
            busqueda: { sede_id: this.sedeActual, bus_nom_num_docu: "", bus_fehc_ini: "2025-11-21", bus_fecha_fin: "2025-11-21" },
            sedeActual: sessionStorage.getItem('ST-sede'),
            servicios: [],
            errores: [],
            registros: [],
        };
    },
    computed: {

    },
    methods: {
        async guardarRegistro() {
            try {
                const res = await axios.post('/agenda-disponibilidad', this.registro);

                $.toast({
                    heading: "Bien hecho!!!",
                    text: "La disponibilidad de citas se ha guardado con exito.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 5500,
                    stack: 6,
                });
            } catch (error) {
                this.errores = error.response.data;
                if (error.response.status == 400) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: error.response.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 5500,
                        stack: 6,
                    });
                }
            }
        },
        async listarServicios() {
            const res = await axios.get('/confing-modulos-listar')
            this.servicios = res.data;
        },
        establecerSede() {
            this.registro.sede_id = sessionStorage.getItem('ST-sede');
            this.busqueda.sede_id = sessionStorage.getItem('ST-sede');
            this.sedeActual = sessionStorage.getItem('ST-sede');

            if (this.registro.sede_id == 1) {
                this.sedeInterface = true;
            } else {
                this.sedeInterface = false;
            }
            this.registros = [];
        },
        mostrarRegistro() {
            this.tituloModal = 'Guardar disponibilidad de citas';
            this.registro = { sede_id: this.sedeActual, quien_registro_id: this.usuarioactual.id, servicio_id: "" };
            this.errores = [];
            $('#right-sidebar').addClass('shw-rside');
        },
    },
};
</script>

<style scoped>
.right-sidebar {
    position: fixed;
    right: -40%;
    width: 40%;
    display: none;
    z-index: 180;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 40%;
    -webkit-box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    box-shadow: 5px 1px 40px rgba(0, 0, 0, 0.1);
    -webkit-transition: all 0.3s ease;
    -o-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

.shw-rside {
    right: 0px;
    width: 40%;
    display: block;
}

.audio {
    width: 50px;
    height: 5px;
}
</style>