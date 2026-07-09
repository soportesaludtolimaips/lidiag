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
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button
                            type="button"
                            class="btn btn-link p-0"
                            style="vertical-align: baseline; text-decoration: none; color: inherit"
                        >
                            Estudios
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Notificar Estudios</li>
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
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title text-themecolor">
                            <i class="fa fa-filter"></i> Filtros para busqueda de estudios
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_num_docu"
                                                ># de documento
                                            </label>
                                            <input
                                                type="text"
                                                id="bus_num_docu"
                                                name="bus_num_docu"
                                                v-model="busqueda.bus_num_docu"
                                                class="form-control"
                                                placeholder="# de documento de paciente"
                                                @keyup.enter="buscarStudy()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_nom"
                                                >Nombres del paciente</label
                                            >
                                            <input
                                                type="text"
                                                id="bus_nom"
                                                name="bus_nom"
                                                v-model="busqueda.bus_nom"
                                                class="form-control"
                                                placeholder="Nombres del paciente"
                                                @keyup.enter="buscarStudy()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label" for="fecha_ini"
                                                >Fecha Inicio</label
                                            >
                                            <input
                                                type="date"
                                                id="fecha_ini"
                                                name="fecha_ini"
                                                v-model="busqueda.bus_fehc_ini"
                                                class="form-control"
                                                @keyup.enter="buscarStudy()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label" for="fecha_fin"
                                                >Fecha Fin</label
                                            >
                                            <input
                                                type="date"
                                                id="fecha_fin"
                                                name="fecha_fin"
                                                v-model="busqueda.bus_fecha_fin"
                                                class="form-control"
                                                @keyup.enter="buscarStudy()"
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
                                        <i v-if="loadingBuscar" class="fa fa-spinner fa-spin"></i>
                                        <i v-else class="fa fa-search"></i>
                                        Buscar
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-inverse"
                                        @click="btnLimpiar()"
                                    >
                                        <i class="fa fa-refresh"></i> Limpiar
                                    </button>

                                    <!-- <button type="button" class="btn btn-inverse" @click="btnTraerImagenes()">Traer
                                        imagenes</button> -->
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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="card-title">
                                    Listado de estudios disponibles para notificar
                                </h4>
                                <h6 class="card-subtitle">
                                    Notifique por correo electrónico el descargue del reporte y de
                                    imágenes de un paciente.
                                </h6>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-primary" v-if="contadorRegistros > 0"
                                    >{{ contadorRegistros }} registro(s)</span
                                >
                                <span class="badge badge-secondary" v-else>Sin resultados</span>
                            </div>
                        </div>
                        <loading-spinner :visible="loadingBuscar" />
                        <div class="table-responsive" :class="{ 'd-none': loadingBuscar }">
                            <table
                                id="example23"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th>Fecha del Estudio</th>
                                        <th>Fecha de lectura</th>
                                        <th># Documento</th>
                                        <th>Paciente</th>
                                        <th>Telefono</th>
                                        <th>Email</th>
                                        <th>Producto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in registros" :key="item.estudio_producto_id">
                                        <td>{{ item.fec_estudio }}</td>
                                        <td>{{ item.fechor_lectura }}</td>
                                        <td>{{ item.num_docu }}</td>
                                        <td>{{ item.nom_pacien }}</td>
                                        <td>{{ item.tel }}</td>
                                        <td>{{ item.email }}</td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                v-if="
                                                    permisos.includes(
                                                        'estudios.reporteLectura.index'
                                                    )
                                                "
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                @click="imprimirReporte(item)"
                                                title="Imprimir reporte"
                                                aria-label="Imprimir reporte"
                                                :disabled="
                                                    loadingImprimirId === item.estudio_producto_id
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingImprimirId ===
                                                        item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-file-pdf-o"></i>
                                            </button>
                                            <button
                                                type="button"
                                                v-if="permisos.includes('estudios.notificar.index')"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                :disabled="
                                                    loadingAbrirId === item.estudio_producto_id
                                                "
                                                @click="mostrarRegistro(item)"
                                                title="Notificar por correo electronico"
                                                aria-label="Notificar por correo electronico"
                                            >
                                                <i
                                                    v-if="
                                                        loadingAbrirId === item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-envelope-o"></i>
                                            </button>
                                            <button
                                                type="button"
                                                v-if="permisos.includes('estudios.notificar.index')"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                :disabled="
                                                    loadingPortalId === item.estudio_producto_id
                                                "
                                                @click="enviarPortal(item)"
                                                title="Enviar al portal de pacientes"
                                                aria-label="Enviar al portal de pacientes"
                                            >
                                                <i
                                                    v-if="
                                                        loadingPortalId === item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-send"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div
                                v-if="registros.length === 0 && !loadingBuscar"
                                class="text-center py-4 text-muted"
                            >
                                <i class="fa fa-inbox fa-3x mb-3"></i>
                                <p>No hay estudios para notificar</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Right sidebar -->
        <!-- ============================================================== -->
        <!-- .right-sidebar -->
        <div class="right-sidebar" style="overflow-x: hidden; overflow-y: scroll; height: 100%">
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="btnCerralModalForm()"
                        aria-label="Cerrar panel"
                    >
                        <i class="ti-close"></i>
                    </button>
                </div>
                <div class="r-panel-body">
                    <!-- ============================================================== -->
                    <!-- Aqui va el contenido de los formularios -->
                    <!-- ============================================================== -->
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <datos-paciente-display :registro="registro" />

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Datos del Estudio
                                    </h5>
                                    <hr />
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input
                                                type="text"
                                                id="nom_produc"
                                                name="nom_produc"
                                                class="form-control"
                                                v-model="registro.nom_produc"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    @click="notificar()"
                                    :disabled="loadingNotificar"
                                >
                                    <i v-if="loadingNotificar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-envelope-o"></i>
                                    Notificar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="btnCerralModalForm()"
                                >
                                    Cancel
                                </button>
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
import LoadingSpinner from "@/components/comunes/LoadingSpinner.vue";
import DatosPacienteDisplay from "@/components/comunes/datosPacienteDisplayComponent.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { getUserSafeError } from "@/helpers/alerts.js";

export default {
    props: ["usuarioactual", "permisos"],
    components: {
        LoadingSpinner,
        DatosPacienteDisplay,
    },
    computed: {
        contadorRegistros() {
            return this.registros.length;
        },
    },
    mounted() {},
    beforeUnmount() {
        this.destroyDataTable();
        this.registros = [];
        this.registro = {};
    },
    data() {
        return {
            registros: [],
            tituloModal: "Nuevo registro",
            registro: {
                num_docu: "",
                nombres: "",
                sexo: "",
                fec_naci: "",
                email: "",
                email_reportar: "",
                direccion: "",
                tel: "",
                nom_produc: "",
                id_producto_lectura: null,
            },
            estudio: null,
            busqueda: { bus_num_docu: "", bus_nom: "", bus_fehc_ini: "", bus_fecha_fin: "" },
            errores: {},
            erroresBusqueda: {},
            loadingBuscar: false,
            loadingNotificar: false,
            loadingImprimirId: null,
            loadingAbrirId: null,
            loadingPortalId: null,
        };
    },
    methods: {
        destroyDataTable() {
            safeDestroyDataTable(this.$refs.dt || "#example23");
        },
        btnLimpiar() {
            this.busqueda.bus_num_docu = "";
            this.busqueda.bus_nom = "";
            this.busqueda.bus_fehc_ini = "";
            this.busqueda.bus_fecha_fin = "";
            this.erroresBusqueda = {};
            this.destroyDataTable();
            this.registros = [];
        },
        async buscarStudy() {
            if (this.loadingBuscar) return;
            this.loadingBuscar = true;
            this.destroyDataTable();
            this.registros = [];
            try {
                if (
                    this.busqueda.bus_num_docu == "" &&
                    this.busqueda.bus_fehc_ini == "" &&
                    this.busqueda.bus_fecha_fin == "" &&
                    this.busqueda.bus_nom == ""
                ) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: "Debe indicar documento, nombre o un rango de fechas para la búsqueda.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else {
                    const res = await axios.post(
                        "/api/listar.estudios.notificaciones",
                        this.busqueda
                    );

                    if (res.status == 200) {
                        this.registros = res.data || [];

                        this.$nextTick(() => {
                            safeInitDataTable(this.$refs.dt || "#example23", {
                                dom: "Bfrtip",
                                buttons: ["copy", "csv", "excel", "pdf", "print"],
                            });
                        });
                    } else if (res.status == 500) {
                        $.toast({
                            heading: "Upsss!!!",
                            text: res.data.message,
                            position: "top-right",
                            loaderBg: "#ff6849",
                            icon: "danger",
                            hideAfter: 3500,
                            stack: 6,
                        });
                    }
                }
            } catch (error) {
                this.registros = [];

                const status = error?.response?.status;
                if (status == 500) {
                    $.toast({
                        heading: "Upss!!!",
                        text: getUserSafeError(error, "Error al listar estudios."),
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }

                this.erroresBusqueda =
                    error?.response?.data?.errors && typeof error.response.data.errors === "object"
                        ? error.response.data.errors
                        : {};
            } finally {
                this.loadingBuscar = false;
            }
        },
        async imprimirReporte(estudio) {
            if (this.loadingImprimirId) return;
            this.loadingImprimirId = estudio.estudio_producto_id;
            try {
                $.toast({
                    heading: "Procesando...",
                    text: "Generando reportes, por favor espere",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "info",
                    hideAfter: 3500,
                    stack: 6,
                });

                const res = await axios.post("/api/edit-word", estudio);

                window.open(
                    `/descargar.pdf.lectura/${res.data.plantilla}`,
                    "_blank",
                    "noopener,noreferrer"
                );
            } catch (error) {
                this.errores =
                    error?.response?.data?.errors && typeof error.response.data.errors === "object"
                        ? error.response.data.errors
                        : {};
            } finally {
                this.loadingImprimirId = null;
            }
        },
        async notificar() {
            if (this.loadingNotificar) return;
            this.loadingNotificar = true;
            try {
                $.toast({
                    heading: "Procesando...",
                    text: "Generando reporte, por favor espere.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "info",
                    hideAfter: 3500,
                    stack: 6,
                });

                this.estudio.email = this.registro.email;
                this.estudio.tel = this.registro.tel;
                const res = await axios.post("/api/notificar.email", this.estudio);

                if (res.status == 200) {
                    $.toast({
                        heading: "Completado",
                        text: "La notificación se realizó correctamente, estamos enviando el correo electronico, por favor espere..",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }

                this.btnCerralModalForm();
                this.limpiar();
            } catch (e) {
                const status = e?.response?.status;
                if (status == 402) {
                    $.toast({
                        heading: "Error",
                        text: getUserSafeError(e, "Saldo insuficiente."),
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else {
                    $.toast({
                        heading: "Upss!!!",
                        text: getUserSafeError(e, "No fue posible notificar."),
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
            } finally {
                this.loadingNotificar = false;
            }
        },
        async enviarPortal(item) {
            if (this.loadingPortalId) return;
            this.loadingPortalId = item.estudio_producto_id;
            try {
                this.setarDatosPortalPacientes(item);
                await this.enviarPortalPacientes();
            } finally {
                this.loadingPortalId = null;
            }
        },
        async enviarPortalPacientes() {
            try {
                $.toast({
                    heading: "Procesando...",
                    text: "Enviando al portal de pacientes, por favor espere.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "info",
                    hideAfter: 3500,
                    stack: 6,
                });

                const res = await axios.post("/api/enviar-portal-pacientes", this.estudio);

                if (res.status == 200) {
                    $.toast({
                        heading: "Completado",
                        text: "Enviado al portal de pacientes.",
                        position: "top-right",
                        loaderBg: "#39cb14",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });

                    this.limpiar();
                }
            } catch (e) {
                if (e.response && e.response.status == 402) {
                    $.toast({
                        heading: "Error",
                        text: getUserSafeError(e, "Saldo insuficiente."),
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
            }
        },
        abrirModal() {
            $(".right-sidebar").slideDown(50);
            $(".right-sidebar").addClass("shw-rside");
        },
        btnCerralModalForm() {
            $(".right-sidebar").slideUp(50);
            $(".right-sidebar").removeClass("shw-rside");
        },
        mostrarRegistro(data = {}) {
            if (this.loadingAbrirId) return;
            this.loadingAbrirId = data.estudio_producto_id;
            this.estudio = data;
            this.tituloModal = "Notificar al paciente: " + data.nom_pacien;

            this.registro.num_docu = data.num_docu;
            this.registro.nombres = data.nom_pacien;
            this.registro.sexo = data.sexo;
            this.registro.fec_naci = data.fec_naci;
            this.registro.tel = data.tel;
            this.registro.email = data.email;
            this.registro.nom_produc = data.nom_produc;
            this.registro.id_producto_lectura = data.id;

            this.abrirModal();
            this.loadingAbrirId = null;
        },
        setarDatosPortalPacientes(data = {}) {
            this.estudio = data;
            this.tituloModal = "Notificar al paciente: " + data.nom_pacien;

            this.registro.num_docu = data.num_docu;
            this.registro.nombres = data.nom_pacien;
            this.registro.sexo = data.sexo;
            this.registro.fec_naci = data.fec_naci;
            this.registro.tel = data.tel;
            this.registro.email = data.email;
            this.registro.nom_produc = data.nom_produc;
            this.registro.id_producto_lectura = data.id;
        },
        limpiar() {
            this.registro.num_docu = "";
            this.registro.nombres = "";
            this.registro.sexo = "";
            this.registro.fec_naci = "";
            this.registro.tel = "";
            this.registro.nom_produc = "";
            this.registro.id_producto_lectura = "";
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

@media (max-width: 767px) {
    .right-sidebar {
        right: -100%;
        width: 100%;
    }
}

.shw-rside {
    right: 0px;
    width: 80%;
    display: block;
}

@media (max-width: 767px) {
    .shw-rside {
        width: 100%;
    }
}
</style>
