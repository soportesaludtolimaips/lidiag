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
                    <li class="breadcrumb-item active">Gesti&oacute;n de citas</li>
                </ol>
            </div>
            <div class="col-md-7 align-self-center text-right d-none d-md-block">
                <button
                    type="button"
                    class="btn btn-info btnDesplegarRigthSidebar"
                    @click="
                        editando = false;
                        mostrarRegistro();
                    "
                >
                    <i class="fa fa-plus-circle m-r-5"></i> Nuevo registro
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros de b&uacute;squeda
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Inicio</label>
                                            <input
                                                type="date"
                                                id="bus_fehc_ini"
                                                name="bus_fehc_ini"
                                                v-model="busqueda.bus_fehc_ini"
                                                class="form-control"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Fecha Fin</label>
                                            <input
                                                type="date"
                                                id="bus_fecha_fin"
                                                name="bus_fecha_fin"
                                                v-model="busqueda.bus_fecha_fin"
                                                class="form-control"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-success m-r-5"
                                        @click="buscarStudy()"
                                        :disabled="loadingBuscar"
                                    >
                                        <i
                                            class="fa"
                                            :class="
                                                loadingBuscar ? 'fa-spinner fa-spin' : 'fa-search'
                                            "
                                        ></i>
                                        {{ loadingBuscar ? "Buscando..." : "Buscar" }}
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-inverse"
                                        @click="btnLimpiar()"
                                    >
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
                        <h6 class="card-subtitle text-info">
                            Gestione las disponibilidades de citas para estudios.
                        </h6>

                        <loading-spinner :visible="loadingBuscar" />

                        <div :class="{ 'd-none': loadingBuscar }">
                            <div v-if="!registros.length" class="text-center p-5">
                                <i class="fa fa-calendar fa-3x text-muted"></i>
                                <p class="mt-3 text-muted">No se encontraron disponibilidades.</p>
                                <p class="text-muted">
                                    Presione <strong>"Nuevo registro"</strong> para crear una.
                                </p>
                            </div>

                            <div class="table-responsive" v-if="registros.length">
                                <table
                                    id="tabla-disponibilidad"
                                    ref="dt"
                                    class="table table-bordered table-striped"
                                    cellspacing="0"
                                    width="100%"
                                >
                                    <thead>
                                        <tr>
                                            <th width="5%">Fecha Inicio</th>
                                            <th width="5%">Fecha Fin</th>
                                            <th width="30%">Sede</th>
                                            <th>Servicio de Cita</th>
                                            <th class="sr-only">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in registros" :key="item.id">
                                            <td>{{ formatFecha(item.fecha_hora_ini) }}</td>
                                            <td>{{ formatFecha(item.fecha_hora_fin) }}</td>
                                            <td>{{ item.sede?.nom_sede || item.sede_id }}</td>
                                            <td>{{ item.servicio_cita?.nombre || "-" }}</td>
                                            <td class="text-nowrap">
                                                <button
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm m-r-5"
                                                    title="Editar disponibilidad"
                                                    aria-label="Editar"
                                                    @click="editarRegistro(item)"
                                                >
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                    title="Eliminar disponibilidad"
                                                    aria-label="Eliminar"
                                                    @click="elimnarRegistro(item.id)"
                                                    :disabled="loadingEliminarId === item.id"
                                                >
                                                    <i
                                                        class="fa"
                                                        :class="
                                                            loadingEliminarId === item.id
                                                                ? 'fa-spinner fa-spin'
                                                                : 'fa-trash'
                                                        "
                                                    ></i>
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
        </div>

        <!-- Right sidebar -->
        <div
            class="right-sidebar"
            id="right-sidebar"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="close right-side-toggle"
                        id="btnCerralModalForm"
                        @click="cerrarModal()"
                        aria-label="Cerrar"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos de la disponibilidad
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">
                                                    Total de citas
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="number"
                                                    id="total_citas"
                                                    name="total_citas"
                                                    v-model="registro.total_citas"
                                                    class="form-control"
                                                />
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.total_citas"
                                                >
                                                    {{ errores.total_citas[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">
                                                    Minutos Por Cita
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="number"
                                                    id="dura_cita"
                                                    name="dura_cita"
                                                    v-model="registro.dura_cita"
                                                    class="form-control"
                                                    placeholder="Minutos por cita"
                                                />
                                                <span class="text-danger" v-if="errores.dura_cita">
                                                    {{ errores.dura_cita[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">
                                                    Servicio de Cita
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select
                                                    v-model="registro.servicio_cita_id"
                                                    name="servicio_cita_id"
                                                    id="servicio_cita_id"
                                                    class="form-control custom-select"
                                                >
                                                    <option value="">-- Seleccione --</option>
                                                    <option
                                                        v-for="(sc, index) in serviciosCitas"
                                                        :key="index"
                                                        :value="sc.id"
                                                    >
                                                        {{ sc.nombre }} &mdash;
                                                        {{ sc.sede?.nom_sede || "Sin sede" }}
                                                    </option>
                                                </select>
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.servicio_cita_id"
                                                >
                                                    {{ errores.servicio_cita_id[0] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">
                                                    Fec. Inicio
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="date"
                                                    id="fecha_hora_ini"
                                                    name="fecha_hora_ini"
                                                    v-model="registro.fecha_hora_ini"
                                                    class="form-control"
                                                />
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.fecha_hora_ini"
                                                >
                                                    {{ errores.fecha_hora_ini[0] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="guardarRegistro()"
                                    :disabled="loadingGuardar"
                                >
                                    <i
                                        class="fa"
                                        :class="loadingGuardar ? 'fa-spinner fa-spin' : 'fa-check'"
                                    ></i>
                                    {{
                                        loadingGuardar
                                            ? "Guardando..."
                                            : editando
                                              ? "Actualizar"
                                              : "Guardar"
                                    }}
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="cerrarModal()"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { handleAxiosErrorToast, confirmAction } from "@/helpers/alerts";
import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";

export default {
    components: { LoadingSpinner },
    props: ["usuarioactual"],
    mounted() {
        this.establecerSede();
        this.listarServiciosCitas();
        this.buscarStudy();

        this.emitter.on("sedeSeleccionada", () => {
            this.establecerSede();
            safeDestroyDataTable(this.$refs.dt || "#tabla-disponibilidad");
        });
    },
    beforeUnmount() {
        safeDestroyDataTable(this.$refs.dt || "#tabla-disponibilidad");
    },
    data() {
        return {
            editando: false,
            editandoId: null,
            tituloModal: "Guardar disponibilidad de citas",
            registro: {
                sede_id: this.sedeActual,
                quien_registro_id: this.usuarioactual.id,
                servicio_cita_id: "",
                total_citas: 1,
                dura_cita: 15,
                fecha_hora_ini: "",
            },
            busqueda: { sede_id: this.sedeActual, bus_fehc_ini: "", bus_fecha_fin: "" },
            sedeActual: sessionStorage.getItem("ST-sede"),
            serviciosCitas: [],
            errores: [],
            registros: [],
            loadingBuscar: false,
            loadingGuardar: false,
            loadingEliminarId: null,
        };
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
                const res = await axios.get("/api/agenda-disponibilidad", { params });
                safeDestroyDataTable(this.$refs.dt || "#tabla-disponibilidad");
                this.registros = res.data || [];
                this.$nextTick(() => {
                    if (this.registros.length) {
                        safeInitDataTable(this.$refs.dt || "#tabla-disponibilidad", {
                            dom: "Bfrtip",
                            buttons: ["copy", "csv", "excel", "pdf", "print"],
                        });
                    }
                });
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar disponibilidades.");
                this.registros = [];
            } finally {
                this.loadingBuscar = false;
            }
        },
        validarFormulario() {
            const errores = {};
            if (!this.registro.fecha_hora_ini) {
                errores.fecha_hora_ini = ["Debe seleccionar la fecha de inicio."];
            }
            if (!this.registro.servicio_cita_id) {
                errores.servicio_cita_id = ["Debe seleccionar un servicio de cita."];
            }
            if (!this.registro.total_citas || this.registro.total_citas < 1) {
                errores.total_citas = ["El total de citas debe ser al menos 1."];
            }
            if (!this.registro.dura_cita || this.registro.dura_cita < 1) {
                errores.dura_cita = ["La duraci\u00f3n debe ser al menos 1 minuto."];
            }
            this.errores = errores;
            return Object.keys(errores).length === 0;
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            if (!this.validarFormulario()) return;
            this.loadingGuardar = true;
            try {
                const payload = {
                    sede_id: this.registro.sede_id,
                    servicio_cita_id: this.registro.servicio_cita_id || null,
                    total_citas: this.registro.total_citas,
                    dura_cita: this.registro.dura_cita,
                    fecha_hora_ini: this.registro.fecha_hora_ini,
                };

                let res;
                if (this.editando && this.editandoId) {
                    res = await axios.put("/api/agenda-disponibilidad/" + this.editandoId, payload);
                } else {
                    res = await axios.post("/api/agenda-disponibilidad", payload);
                }

                $.toast({
                    heading: "Bien hecho!!!",
                    text:
                        res?.data?.message ||
                        "La disponibilidad de citas se ha guardado con \u00e9xito.",
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
        async editarRegistro(item) {
            this.editando = true;
            this.editandoId = item.id;
            this.registro = {
                sede_id: item.sede_id,
                quien_registro_id: this.usuarioactual.id,
                servicio_cita_id: item.servicio_cita_id || "",
                total_citas: item.total_citas,
                dura_cita: item.dura_cita,
                fecha_hora_ini: item.fecha_hora_ini ? item.fecha_hora_ini.substring(0, 10) : "",
            };
            this.tituloModal = "Actualizar disponibilidad de citas";
            this.errores = [];
            $("#right-sidebar").addClass("shw-rside");
        },
        async elimnarRegistro(id) {
            const ok = await confirmAction({
                title: "\u00bfEst\u00e1s seguro?",
                text: "No podr\u00e1s recuperar este registro!",
                icon: "warning",
            });
            if (!ok) return;
            if (this.loadingEliminarId) return;
            this.loadingEliminarId = id;
            try {
                const res = await axios.delete("/api/agenda-disponibilidad/" + id);
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
                const res = await axios.get("/api/config-servicio-citas/mis-servicios", {
                    params: { sede_id: this.sedeActual },
                });
                this.serviciosCitas = res.data;
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar servicios de cita.");
            }
        },
        establecerSede() {
            this.registro.sede_id = sessionStorage.getItem("ST-sede");
            this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
            this.sedeActual = sessionStorage.getItem("ST-sede");
            this.registros = [];
        },
        btnLimpiar() {
            this.busqueda.bus_fehc_ini = "";
            this.busqueda.bus_fecha_fin = "";
            this.buscarStudy();
        },
        mostrarRegistro() {
            this.editando = false;
            this.editandoId = null;
            this.tituloModal = "Guardar disponibilidad de citas";
            this.registro = {
                sede_id: this.sedeActual,
                quien_registro_id: this.usuarioactual.id,
                servicio_cita_id: "",
                total_citas: 1,
                dura_cita: 15,
                fecha_hora_ini: "",
            };
            this.errores = [];
            $("#right-sidebar").addClass("shw-rside");
        },
        cerrarModal() {
            $("#right-sidebar").removeClass("shw-rside");
        },
        formatFecha(fecha) {
            if (!fecha) return "-";
            const d = new Date(fecha);
            if (isNaN(d.getTime())) return fecha;
            const dia = String(d.getDate()).padStart(2, "0");
            const mes = String(d.getMonth() + 1).padStart(2, "0");
            const anio = d.getFullYear();
            const horas = String(d.getHours()).padStart(2, "0");
            const minutos = String(d.getMinutes()).padStart(2, "0");
            return `${dia}/${mes}/${anio} ${horas}:${minutos}`;
        },
    },
};
</script>

<style scoped>
.right-sidebar {
    position: fixed;
    right: -50%;
    width: 50%;
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
    width: 50%;
    display: block;
}
</style>
