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
                                    <button type="button" class="btn btn-success m-r-5" @click="buscarStudy()"
                                        :disabled="loadingBuscar">
                                        <i class="fa" :class="loadingBuscar ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                                        {{ loadingBuscar ? 'Buscando...' : 'Buscar' }}
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
                            <table id="example23" ref="dt" class="table table-bordered table-striped" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th with='5%'>Fecha Inicio</th>
                                        <th with='5%'>Fecha Fin</th>
                                        <th with='30%'>Sede</th>
                                        <th>Servicio de Cita</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.id">
                                        <td>{{ item.fecha_hora_ini }}</td>
                                        <td>{{ item.fecha_hora_fin }}</td>
                                        <td>{{ item.sede?.nom_sede || item.sede_id }}</td>
                                        <td>{{ item.servicio_cita?.nombre || '-' }}</td>
                                        <td class="text-nowrap">
                                            <button type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                @click="elimnarRegistro(item.id)"
                                                :disabled="loadingEliminarId === item.id">
                                                <i class="fa"
                                                    :class="loadingEliminarId === item.id ? 'fa-spinner fa-spin' : 'fa-trash'"></i>
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
                                                <label class="control-label">Minutos Por Cita</label>
                                                <input type="number" id="dura_cita" name="dura_cita"
                                                    v-model="registro.dura_cita" class="form-control"
                                                    placeholder="Minutos por cita" />
                                                <!-- <span class="text-danger" v-if="errores.minutos_citas">
                                                    {{ errores.minutos_citas[0] }}
                                                </span> -->
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Servicio de Cita</label>
                                                <select v-model="registro.servicio_cita_id" name="servicio_cita_id"
                                                    id="servicio_cita_id" class="form-control custom-select">
                                                    <option value="">-- Ninguno --</option>
                                                    <option v-for="(sc, index) in serviciosCitas" :key="index"
                                                        :value="sc.id">
                                                        {{ sc.nombre }} — {{ sc.sede?.nom_sede || 'Sin sede' }}
                                                    </option>
                                                </select>
                                                <span class="text-danger" v-if="errores.servicio_cita_id">
                                                    {{ errores.servicio_cita_id[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Fec. Inicio</label>
                                                <input type="date" id="fecha_hora_ini" name="fecha_hora_ini"
                                                    v-model="registro.fecha_hora_ini" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button type="button" class="btn btn-success m-r-5" @click="guardarRegistro()"
                                    :disabled="loadingGuardar">
                                    <i class="fa" :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                    {{ loadingGuardar ? 'Guardando...' : 'Guardar' }}
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
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { handleAxiosErrorToast, confirmAction } from "@/helpers/alerts";

export default {
    props: ['usuarioactual'],
    mounted() {
        this.establecerSede();
        this.listarServiciosCitas();
        this.buscarStudy();

        this.emitter.on("sedeSeleccionada", (data) => {
            this.establecerSede();
            safeDestroyDataTable(this.$refs.dt || "#example23");
        });
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dt || "#example23");
    },
    data() {
        return {
            tituloModal: 'Guardar disponibilidad de citas',
            registro: {
                sede_id: this.sedeActual,
                quien_registro_id: this.usuarioactual.id,
                servicio_cita_id: "",
                total_citas: 1,
                dura_cita: 15,
                fecha_hora_ini: "",
            },
            busqueda: { sede_id: this.sedeActual, bus_fehc_ini: "", bus_fecha_fin: "" },
            sedeActual: sessionStorage.getItem('ST-sede'),
            serviciosCitas: [],
            errores: [],
            registros: [],
            loadingBuscar: false,
            loadingGuardar: false,
            loadingEliminarId: null,
        };
    },
    computed: {

    },
    methods: {
        async buscarStudy() {
            if (this.loadingBuscar) return;
            this.loadingBuscar = true;
            try {
                const params = {
                    sede_id: this.busqueda.sede_id,
                    bus_fehc_ini: this.busqueda.bus_fehc_ini || undefined,
                    bus_fecha_fin: this.busqueda.bus_fecha_fin || undefined,
                };
                const res = await axios.get('/api/agenda-disponibilidad', { params });
                safeDestroyDataTable(this.$refs.dt || "#example23");
                this.registros = res.data || [];
                this.$nextTick(() => {
                    safeInitDataTable(this.$refs.dt || "#example23", {
                        dom: "Bfrtip",
                        buttons: ["copy", "csv", "excel", "pdf", "print"],
                    });
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar disponibilidades.");
                this.registros = [];
            } finally {
                this.loadingBuscar = false;
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {
                if (!this.registro.fecha_hora_ini) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: "Debes seleccionar la fecha inicio.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                    return;
                }
                const payload = {
                    sede_id: this.registro.sede_id,
                    servicio_cita_id: this.registro.servicio_cita_id || null,
                    total_citas: this.registro.total_citas,
                    dura_cita: this.registro.dura_cita,
                    // backend acepta date/datetime
                    fecha_hora_ini: this.registro.fecha_hora_ini,
                };
                const res = await axios.post('/api/agenda-disponibilidad', payload);

                $.toast({
                    heading: "Bien hecho!!!",
                    text: res?.data?.message || "La disponibilidad de citas se ha guardado con exito.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 5500,
                    stack: 6,
                });
                this.cerrarModal();
                await this.buscarStudy();
            } catch (error) {
                this.errores = error?.response?.data?.errors || {};
                handleAxiosErrorToast(error, "No fue posible guardar la disponibilidad.");
            } finally {
                this.loadingGuardar = false;
            }
        },
        async elimnarRegistro(id) {
            const ok = await confirmAction({
                title: "¿Estas seguro?",
                text: "No podrás recuperar este registro!",
                icon: "warning",
            });
            if (!ok) return;
            if (this.loadingEliminarId) return;
            this.loadingEliminarId = id;
            try {
                const res = await axios.delete('/api/agenda-disponibilidad/' + id);
                $.toast({
                    heading: "Ok!!!",
                    text: res?.data?.message || "Registro eliminado.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "success",
                    hideAfter: 3500,
                    stack: 6,
                });
                await this.buscarStudy();
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible eliminar la disponibilidad.");
            } finally {
                this.loadingEliminarId = null;
            }
        },
        async listarServiciosCitas() {
            try {
                const res = await axios.get('/api/config-servicio-citas/mis-servicios', {
                    params: { sede_id: this.sedeActual },
                })
                this.serviciosCitas = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar servicios de cita.");
            }
        },
        establecerSede() {
            this.registro.sede_id = sessionStorage.getItem('ST-sede');
            this.busqueda.sede_id = sessionStorage.getItem('ST-sede');
            this.sedeActual = sessionStorage.getItem('ST-sede');
            this.registros = [];
        },
        btnLimpiar() {
            this.busqueda.bus_fehc_ini = "";
            this.busqueda.bus_fecha_fin = "";
            this.buscarStudy();
        },
        mostrarRegistro() {
            this.tituloModal = 'Guardar disponibilidad de citas';
            this.registro = {
                sede_id: this.sedeActual,
                quien_registro_id: this.usuarioactual.id,
                servicio_cita_id: "",
                total_citas: 1,
                dura_cita: 15,
                fecha_hora_ini: "",
            };
            this.errores = [];
            $('#right-sidebar').addClass('shw-rside');
        },
        cerrarModal() {
            $('#right-sidebar').removeClass('shw-rside');
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