<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Ecografias</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Ecografias</a>
                    </li>
                    <li class="breadcrumb-item active">Ecografias admiisionadas</li>
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
                            <i class="fa fa-filter"></i> Filtros para busqueda las ecografias
                            adminisionadas.
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">
                                                # de atención, # de documento ó Nombres de paciente
                                            </label>
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder=" Atención, Nombres de paciente o # de documento"
                                                @keyup.enter="buscarStudy()"
                                            />
                                        </div>
                                    </div>
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
                                            <!-- <span class="text-danger" v-if="errores.bus_fehc_ini">
                                                {{ erroresBusqueda.bus_fehc_ini[0] }}
                                            </span> -->
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
                                            <!-- <span class="text-danger" v-if="errores.bus_fecha_fin">
                                                {{ erroresBusqueda.bus_fecha_fin[0] }}
                                            </span> -->
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions mt-3">
                                    <button
                                        type="button"
                                        class="btn btn-success m-r-5"
                                        @click="listarAtenciones()"
                                        :disabled="isBuscandoAdmisionadas"
                                    >
                                        <i
                                            class="fa"
                                            :class="
                                                isBuscandoAdmisionadas
                                                    ? 'fa-spinner fa-spin'
                                                    : 'fa-search'
                                            "
                                        ></i>
                                        {{ isBuscandoAdmisionadas ? "Buscando..." : "Buscar" }}
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
                        <h4 class="card-title">Listado de atenciones disponibles.</h4>
                        <h6 class="card-subtitle text-info">
                            Asigne los médicos para su respectiva lectura.
                        </h6>
                        <div class="table-responsive">
                            <table
                                id="example23"
                                ref="dt"
                                class="table table-bordered table-striped"
                                cellspacing="0"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th with="5%">Fecha</th>
                                        <th with="5%"># Documento</th>
                                        <th with="30%">Paciente</th>
                                        <th>Examen</th>
                                        <th>Val. Examen</th>
                                        <th>Administradora</th>
                                        <th>Contrato</th>
                                        <th>Plan</th>
                                        <th>Estado</th>
                                        <th>SAHI</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in registros" :key="index">
                                        <td>{{ item.created_at }}</td>
                                        <td>{{ item.paciente.num_docu }}</td>
                                        <td>
                                            {{ item.paciente.nombres }} {{ item.paciente.seg_nom }}
                                            {{ item.paciente.pri_ape }} {{ item.paciente.seg_ape }}
                                        </td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td>{{ item.ValProduc }}</td>
                                        <td>{{ item.admin_salud.nom_admin_salud }}</td>
                                        <td>{{ item.contrato }}</td>
                                        <td>{{ item.plan }}</td>
                                        <td>
                                            <span
                                                class="label label-info"
                                                v-if="item.estado === 'EN PROCESO'"
                                                >{{ item.estado }}</span
                                            >
                                            <span
                                                class="label label-success"
                                                v-if="item.estado === 'LEIDO'"
                                                >{{ item.estado }}</span
                                            >
                                            <span
                                                class="label label-danger"
                                                v-if="item.estado === 'ANULADO'"
                                                >{{ item.estado }}</span
                                            >
                                        </td>
                                        <td>
                                            <template v-if="sedeInterface">
                                                <span
                                                    v-if="item.estado_envio_sahi"
                                                    class="label"
                                                    :class="{
                                                        'label-success':
                                                            item.estado_envio_sahi === 'ENVIADO',
                                                        'label-warning':
                                                            item.estado_envio_sahi === 'ENVIANDO' ||
                                                            item.estado_envio_sahi === 'PENDIENTE',
                                                        'label-danger':
                                                            item.estado_envio_sahi === 'ERROR',
                                                        'label-default': !item.estado_envio_sahi,
                                                    }"
                                                >
                                                    {{ item.estado_envio_sahi }}
                                                </span>
                                                <span v-else class="text-muted">—</span>

                                                <div class="m-t-5">
                                                    <button
                                                        type="button"
                                                        class="btn btn-info btn-xs m-r-5"
                                                        @click="consultarEstadoSahi(item)"
                                                        :disabled="
                                                            isConsultandoSahi[
                                                                item.ecografia_producto_id
                                                            ]
                                                        "
                                                    >
                                                        <i
                                                            class="fa"
                                                            :class="
                                                                isConsultandoSahi[
                                                                    item.ecografia_producto_id
                                                                ]
                                                                    ? 'fa-spinner fa-spin'
                                                                    : 'fa-refresh'
                                                            "
                                                        ></i>
                                                    </button>

                                                    <button
                                                        v-if="item.estado_envio_sahi === 'ERROR'"
                                                        type="button"
                                                        class="btn btn-warning btn-xs"
                                                        @click="reenviarASahi(item)"
                                                        :disabled="
                                                            isEnviandoSahi[
                                                                item.ecografia_producto_id
                                                            ] ||
                                                            item.estado_envio_sahi === 'ENVIANDO'
                                                        "
                                                    >
                                                        <i
                                                            class="fa"
                                                            :class="
                                                                isEnviandoSahi[
                                                                    item.ecografia_producto_id
                                                                ]
                                                                    ? 'fa-spinner fa-spin'
                                                                    : 'fa-repeat'
                                                            "
                                                        ></i>
                                                    </button>
                                                </div>

                                                <small
                                                    v-if="
                                                        item.estado_envio_sahi === 'ERROR' &&
                                                        item.error_envio_sahi
                                                    "
                                                    class="text-danger d-block"
                                                >
                                                    {{ item.error_envio_sahi }}
                                                </small>
                                            </template>
                                            <template v-else>
                                                <span class="text-muted">—</span>
                                            </template>
                                        </td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                @click="
                                                    actualizar = false;
                                                    registroParaAnular(item);
                                                "
                                                title="Anular lectura"
                                                alt="default"
                                                data-toggle="modal"
                                                data-target="#responsive-modal"
                                                v-if="item.estado === 'EN PROCESO'"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="btn waves-effect waves-info btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="imprimirReporte(item.id)"
                                                title="Imprimir reporte"
                                                :disabled="isImprimiendoReporte"
                                            >
                                                <i
                                                    class="fa"
                                                    :class="
                                                        isImprimiendoReporte
                                                            ? 'fa-spinner fa-spin'
                                                            : 'fa-print'
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
        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->
    </div>

    <div
        id="responsive-modal"
        class="modal fade"
        tabindex="-1"
        role="dialog"
        aria-labelledby="myModalLabel"
        aria-hidden="true"
        style="display: none"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">Anular Agenda</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                        ×
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="message-text" class="control-label"
                                >Motivo de anulación:</label
                            >
                            <textarea
                                class="form-control"
                                id="message-text"
                                v-model="registroAnular.motivo_anula"
                            ></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-default waves-effect"
                        data-dismiss="modal"
                        id="btnCerrarModalAunular"
                    >
                        Close
                    </button>
                    <button
                        type="button"
                        class="btn btn-danger waves-effect waves-light"
                        v-if="can('ecografias.anular')"
                        @click="anularEcografia()"
                        :disabled="isAnulandoAdmisionada"
                    >
                        <i
                            class="fa"
                            :class="isAnulandoAdmisionada ? 'fa-spinner fa-spin' : 'fa-trash'"
                        ></i>
                        {{ isAnulandoAdmisionada ? "Anulando..." : "Anular" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import usePermissions from "@/helpers/permissions.js";
import { alertError, alertSuccess, confirmAction } from "@/helpers/alerts.js";
import { handleAxiosErrorToast } from "@/helpers/alerts";
import { useSede } from "@/composables/useSede";
import { useSahiInterface } from "@/composables/useSahiInterface";

export default {
    props: ["usuarioactual", "permisos"],
    setup(props) {
        const { can } = usePermissions(props.permisos);
        const { sedeInterface, establecerSede } = useSede();
        const { consultarEstado, enviarEcografiaProducto } = useSahiInterface();

        return { can, sedeInterface, establecerSede, consultarEstado, enviarEcografiaProducto };
    },
    mounted() {
        this.establecerSede();
    },
    data() {
        return {
            busqueda: {
                sede_id: this.sedeActual,
                medico_id: this.medicoActual,
                bus_nom_num_docu: "",
                bus_fehc_ini: "",
                bus_fecha_fin: "",
            },
            registros: [],
            registroAnular: {
                ecografia_id: "",
                atencion_id: "",
                quien_anulo_id: this.usuarioactual.id,
                motivo_anula: "",
            },
            errores: {},
            erroresBusqueda: {},
            isBuscandoAdmisionadas: false,
            isImprimiendoReporte: false,
            isAnulandoAdmisionada: false,

            isConsultandoSahi: {},
            isEnviandoSahi: {},
        };
    },
    methods: {
        buscarStudy() {
            // Soporte para Enter en el input de búsqueda
            return this.listarAtenciones();
        },
        btnLimpiar() {
            // Limpia criterios de búsqueda (mantiene sede/medico del contexto)
            this.busqueda.bus_nom_num_docu = "";
            this.busqueda.bus_fehc_ini = "";
            this.busqueda.bus_fecha_fin = "";
            this.erroresBusqueda = {};

            this.safeDestroyDataTable(this.$refs.dt || "#example23");
            this.registros = [];
        },
        setMapValue(mapName, key, value) {
            if (!key) return;
            if (typeof this.$set === "function") {
                this.$set(this[mapName], key, value);
            } else {
                this[mapName][key] = value;
            }
        },
        async establecerSede() {
            const sede = sessionStorage.getItem("ST-sede");
            if (sede != null) {
                this.busqueda.sede_id = sede;
            }
            await this.establecerSede();
        },
        safeDestroyDataTable(selector = "#example23") {
            if (
                typeof window === "undefined" ||
                !window.$ ||
                !window.$.fn ||
                !window.$.fn.DataTable
            )
                return;
            const $t = window.$(selector);
            if (!$t.length) return;
            if (window.$.fn.DataTable.isDataTable($t)) {
                $t.DataTable().clear().destroy();
            }
        },
        safeInitDataTable(selector = "#example23") {
            if (
                typeof window === "undefined" ||
                !window.$ ||
                !window.$.fn ||
                !window.$.fn.DataTable
            )
                return;
            this.$nextTick(() => {
                const $t = window.$(selector);
                if (!$t.length) return;
                if (window.$.fn.DataTable.isDataTable($t)) return;
                $t.DataTable({
                    dom: "Bfrtip",
                    buttons: ["copy", "csv", "excel", "pdf", "print"],
                });
            });
        },
        async listarAtenciones() {
            if (
                this.busqueda.bus_nom_num_docu == "" &&
                this.busqueda.bus_fehc_ini == "" &&
                this.busqueda.bus_fecha_fin == ""
            ) {
                $.toast({
                    heading: "¡Atención!",
                    text: "Debe ingresar almenos un criterio de busqueda.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
                return;
            }

            try {
                this.isBuscandoAdmisionadas = true;

                const res = await axios.post("/api/ecografias-filtar", this.busqueda);

                if (res.status == 200) {
                    this.safeDestroyDataTable(this.$refs.dt || "#example23");

                    this.registros = res.data;

                    this.safeInitDataTable(this.$refs.dt || "#example23");
                }
            } catch (error) {
                this.erroresBusqueda = error?.response?.data?.errors || {};
                handleAxiosErrorToast(error, "No fue posible listar ecografías admisionadas.");
            } finally {
                this.isBuscandoAdmisionadas = false;
            }
        },
        async imprimirReporte(id) {
            try {
                this.isImprimiendoReporte = true;
                const res = await axios.post(`/api/ecografia-generar-reporte/${id}`);
                const url = res?.data?.plantilla || res?.data?.url;
                if (url) {
                    window.open(url, "_blank", "noopener,noreferrer");
                } else {
                    $.toast({
                        heading: "Upss!!!",
                        text: res?.data?.message || "No fue posible generar el reporte.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
                /* const res = await axios.post(`/ecografia-generar-reporte/${id}`, {
                    responseType: 'blob'
                });

                const file = new Blob([res.data], { type: 'application/pdf' });
                const fileURL = URL.createObjectURL(file);
                window.open(fileURL); */
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible generar el reporte.");
            } finally {
                this.isImprimiendoReporte = false;
            }
        },
        registroParaAnular(data) {
            this.registroAnular.ecografia_id = data.id;
            this.registroAnular.atencion_id = data?.atencion_id ?? data?.IdAtencion ?? "";
        },
        anularEcografia() {
            if (this.registroAnular.motivo_anula === "") {
                $.toast({
                    heading: "¡Atención!",
                    text: "Debe ingresar el motivo de anulación.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
                return;
            }

            confirmAction({
                title: "Estas seguro de anular la ecografia?",
                text: "No podrás recuperar este registro!",
                icon: "warning",
            }).then(async (ok) => {
                if (!ok) return;
                if (this.isAnulandoAdmisionada) return;
                this.isAnulandoAdmisionada = true;
                try {
                    const response = await axios.post(
                        `/api/ecografia-anular/${this.registroAnular.ecografia_id}`,
                        this.registroAnular
                    );
                    alertSuccess("Ok!", response?.data?.message || "Ecografía anulada.", "success");
                    const cerrarModal = document.getElementById("btnCerrarModalAunular");
                    if (cerrarModal) cerrarModal.click();
                    await this.listarAtenciones();
                } catch (error) {
                    handleAxiosErrorToast(error, "No fue posible anular la ecografía.");
                    alertError("Error", "No fue posible anular la ecografía.", "error");
                } finally {
                    this.isAnulandoAdmisionada = false;
                }
            });
        },
        async consultarEstadoSahi(item) {
            if (!this.sedeInterface) return;
            if (!item?.ecografia_producto_id) return;

            const id = item.ecografia_producto_id;
            try {
                this.setMapValue("isConsultandoSahi", id, true);
                const estado = await this.consultarEstado(id);

                item.estado_envio_sahi = estado.estado_envio_sahi ?? item.estado_envio_sahi;
                item.fechor_envio_sahi = estado.fechor_envio_sahi ?? item.fechor_envio_sahi;
                item.error_envio_sahi = estado.error_envio_sahi ?? null;
                item.sahi_esquema_149 = estado.sahi_esquema_149 ?? item.sahi_esquema_149;
                item.sahi_esquema_150 = estado.sahi_esquema_150 ?? item.sahi_esquema_150;

                this.safeDestroyDataTable(this.$refs.dt || "#example23");
                this.safeInitDataTable(this.$refs.dt || "#example23");
            } catch (error) {
                $.toast({
                    heading: "SAHI",
                    text: error.response?.data?.message || "No se pudo consultar el estado SAHI.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 3500,
                    stack: 6,
                });
            } finally {
                this.setMapValue("isConsultandoSahi", id, false);
            }
        },
        async reenviarASahi(item) {
            if (!this.sedeInterface) return;
            if (!item?.ecografia_producto_id) return;

            const id = item.ecografia_producto_id;
            try {
                this.setMapValue("isEnviandoSahi", id, true);
                item.estado_envio_sahi = "ENVIANDO";
                item.error_envio_sahi = null;

                await this.enviarEcografiaProducto(id);

                $.toast({
                    heading: "SAHI",
                    text: "Envío a SAHI encolado.",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "info",
                    hideAfter: 3500,
                    stack: 6,
                });

                this.safeDestroyDataTable(this.$refs.dt || "#example23");
                this.safeInitDataTable(this.$refs.dt || "#example23");
            } catch (error) {
                item.estado_envio_sahi = "ERROR";
                item.error_envio_sahi =
                    error.response?.data?.message || "No se pudo reenviar a SAHI.";
                $.toast({
                    heading: "SAHI",
                    text: item.error_envio_sahi,
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "warning",
                    hideAfter: 4500,
                    stack: 6,
                });
            } finally {
                this.setMapValue("isEnviandoSahi", id, false);
            }
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
    z-index: 180;
    background: #ffffff;
    top: 0px;
    padding-bottom: 20px;
    height: 80%;
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

.audio {
    width: 50px;
    height: 5px;
}
</style>
