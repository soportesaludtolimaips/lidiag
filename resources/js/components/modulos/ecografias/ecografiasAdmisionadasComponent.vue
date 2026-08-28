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
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Inicio
                        </button>
                    </li>
                    <li class="breadcrumb-item">
                        <button type="button" class="btn btn-link p-0 breadcrumb-btn">
                            Ecografias
                        </button>
                    </li>
                    <li class="breadcrumb-item active">Ecografías admisionadas</li>
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
                            <i class="fa fa-filter"></i> Filtros para búsqueda de ecografías
                            admisionadas.
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
                                                id="bus_fech_ini"
                                                name="bus_fech_ini"
                                                v-model="busqueda.bus_fech_ini"
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
                                                        title="Consultar estado SAHI"
                                                        aria-label="Consultar estado SAHI"
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
                                                        title="Reenviar a SAHI"
                                                        aria-label="Reenviar a SAHI"
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
                                                aria-label="Anular lectura"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="btn waves-effect waves-info btn-rounded btn-outline-primary btn-sm m-r-5"
                                                @click="imprimirReporte(item.id)"
                                                title="Imprimir reporte"
                                                aria-label="Imprimir reporte"
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
<script setup>
import { nextTick, onMounted, ref, getCurrentInstance } from "vue";

import usePermissions from "@/helpers/permissions.js";
import { alertError, alertSuccess, confirmAction, toastMessage } from "@/helpers/alerts.js";
import { setMapValue } from "@/helpers/sahi";
import { useSede } from "@/composables/useSede.js";
import { useSahiInterface } from "@/composables/useSahiInterface.js";
import axios from "axios";

const props = defineProps({ usuarioactual: Object, permisos: Array });
const { can } = usePermissions(props.permisos);
const { sedeActual, sedeInterface, establecerSede: establecerSedeComposable } = useSede();
const { consultarEstado, enviarEcografiaProducto } = useSahiInterface();

const { proxy } = getCurrentInstance();

const dt = ref(null);
const actualizar = ref(false);

const busqueda = ref({
    sede_id: "",
    medico_id: "",
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});
const registros = ref([]);
const registroAnular = ref({
    ecografia_id: "",
    atencion_id: "",
    quien_anulo_id: props.usuarioactual.id,
    motivo_anula: "",
});
const erroresBusqueda = ref({});
const isBuscandoAdmisionadas = ref(false);
const isImprimiendoReporte = ref(false);
const isAnulandoAdmisionada = ref(false);
const isConsultandoSahi = ref({});
const isEnviandoSahi = ref({});

/**
 * Permite disparar la búsqueda de atenciones al presionar Enter en el campo de texto, delegando en listarAtenciones.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function buscarStudy() {
    // Soporte para Enter en el input de búsqueda
    return listarAtenciones();
}
/**
 * Restablece los criterios de búsqueda (manteniendo sede y médico del contexto) y vacía el listado de registros.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function btnLimpiar() {
    // Limpia criterios de búsqueda (mantiene sede/medico del contexto)
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    erroresBusqueda.value = {};

    safeDestroyDataTable(dt.value || "#example23");
    registros.value = [];
}
/**
 * Inicializa la sede actual a partir de la sesión y la establece en el composable correspondiente.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
async function initSede() {
    const sede = sessionStorage.getItem("ST-sede");
    if (sede != null) {
        busqueda.value.sede_id = sede;
    }
    await establecerSedeComposable();
}
/**
 * Destruye de forma segura un DataTable previamente inicializado en el selector indicado.
 *
 * @param {string} selector Selector jQuery del DataTable a destruir.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function safeDestroyDataTable(selector = "#example23") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    const $t = window.$(selector);
    if (!$t.length) return;
    if (window.$.fn.DataTable.isDataTable($t)) {
        $t.DataTable().clear().destroy();
    }
}
/**
 * Inicializa de forma segura un DataTable con botones de exportación en el selector indicado.
 *
 * @param {string} selector Selector jQuery del DataTable a inicializar.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function safeInitDataTable(selector = "#example23") {
    if (typeof window === "undefined" || !window.$ || !window.$.fn || !window.$.fn.DataTable)
        return;
    nextTick(() => {
        const $t = window.$(selector);
        if (!$t.length) return;
        if (window.$.fn.DataTable.isDataTable($t)) return;
        $t.DataTable({
            dom: "Bfrtip",
            buttons: ["copy", "csv", "excel", "pdf", "print"],
            language: {
                emptyTable: "No se encontraron registros",
                zeroRecords: "No se encontraron registros",
            },
        });
    });
}
/**
 * Consulta al backend las ecografías admisionadas según los filtros y puebla la tabla de resultados.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
async function listarAtenciones() {
    if (
        busqueda.value.bus_nom_num_docu == "" &&
        busqueda.value.bus_fech_ini == "" &&
        busqueda.value.bus_fecha_fin == ""
    ) {
        toastMessage({
            heading: "¡Atención!",
            text: "Debe ingresar almenos un criterio de busqueda.",
            icon: "warning",
        });
        return;
    }

    try {
        isBuscandoAdmisionadas.value = true;

        const res = await axios.post("/api/ecografias-filtar", busqueda.value);

        if (res.status == 200) {
            safeDestroyDataTable(dt.value || "#example23");

            registros.value = res.data.data;

            safeInitDataTable(dt.value || "#example23");
        }
    } catch (error) {
        erroresBusqueda.value = error?.response?.data?.errors || {};
    } finally {
        isBuscandoAdmisionadas.value = false;
    }
}
/**
 * Genera el reporte PDF de la ecografía indicada y lo abre en una nueva pestaña del navegador.
 *
 * @param {number} id Identificador de la ecografía de la cual se genera el reporte.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
async function imprimirReporte(id) {
    try {
        isImprimiendoReporte.value = true;
        const res = await axios.post(`/api/ecografia-generar-reporte/${id}`);
        const url = res?.data?.plantilla || res?.data?.url;
        if (url) {
            window.open(url, "_blank", "noopener,noreferrer");
        } else {
            toastMessage({
                heading: "Upss!!!",
                text: res?.data?.message || "No fue posible generar el reporte.",
                icon: "warning",
            });
        }
    } catch (error) {
    } finally {
        isImprimiendoReporte.value = false;
    }
}
/**
 * Prepara el registro seleccionado para ser anulado, guardando su id de ecografía y de atención.
 *
 * @param {Object} data Objeto de la fila seleccionada con la información de la ecografía.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function registroParaAnular(data) {
    registroAnular.value.ecografia_id = data.id;
    registroAnular.value.atencion_id = data?.atencion_id ?? data?.IdAtencion ?? "";
}
/**
 * Valida el motivo de anulación, solicita confirmación y envía la petición para anular la ecografía seleccionada.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
function anularEcografia() {
    if (registroAnular.value.motivo_anula === "") {
        toastMessage({
            heading: "¡Atención!",
            text: "Debe ingresar el motivo de anulación.",
            icon: "warning",
        });
        return;
    }

    confirmAction({
        title: "Estas seguro de anular la ecografia?",
        text: "No podrás recuperar este registro!",
        icon: "warning",
    }).then(async (ok) => {
        if (!ok) return;
        if (isAnulandoAdmisionada.value) return;
        isAnulandoAdmisionada.value = true;
        try {
            const response = await axios.post(
                `/api/ecografia-anular/${registroAnular.value.ecografia_id}`,
                registroAnular.value
            );
            alertSuccess("Ok!", response?.data?.message || "Ecografía anulada.", "success");
            const cerrarModal = document.getElementById("btnCerrarModalAunular");
            if (cerrarModal) cerrarModal.click();
            await listarAtenciones();
        } catch (error) {
            alertError("Error", "No fue posible anular la ecografía.", "error");
        } finally {
            isAnulandoAdmisionada.value = false;
        }
    });
}
/**
 * Consulta el estado de envío a SAHI de una ecografía producto y actualiza la información en la fila correspondiente.
 *
 * @param {Object} item Registro de la ecografía producto sobre el cual se consulta el estado SAHI.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
async function consultarEstadoSahi(item) {
    if (!sedeInterface) return;
    if (!item?.ecografia_producto_id) return;

    const id = item.ecografia_producto_id;
    try {
        setMapValue(proxy, "isConsultandoSahi", id, true);
        const estado = await consultarEstado(id);

        item.estado_envio_sahi = estado.estado_envio_sahi ?? item.estado_envio_sahi;
        item.fechor_envio_sahi = estado.fechor_envio_sahi ?? item.fechor_envio_sahi;
        item.error_envio_sahi = estado.error_envio_sahi ?? null;
        item.sahi_esquema_149 = estado.sahi_esquema_149 ?? item.sahi_esquema_149;
        item.sahi_esquema_150 = estado.sahi_esquema_150 ?? item.sahi_esquema_150;

        safeDestroyDataTable(dt.value || "#example23");
        safeInitDataTable(dt.value || "#example23");
    } catch (error) {
        toastMessage({
            heading: "SAHI",
            text: error.response?.data?.message || "No se pudo consultar el estado SAHI.",
            icon: "warning",
        });
    } finally {
        setMapValue(proxy, "isConsultandoSahi", id, false);
    }
}
/**
 * Reintenta el envío a SAHI de una ecografía producto que previamente falló o está pendiente.
 *
 * @param {Object} item Registro de la ecografía producto que se desea reenviar a SAHI.
 *
 * @returns {Promise<void>}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
async function reenviarASahi(item) {
    if (!sedeInterface) return;
    if (!item?.ecografia_producto_id) return;

    const id = item.ecografia_producto_id;
    try {
        setMapValue(proxy, "isEnviandoSahi", id, true);
        item.estado_envio_sahi = "ENVIANDO";
        item.error_envio_sahi = null;

        await enviarEcografiaProducto(id);

        toastMessage({ heading: "SAHI", text: "Envío a SAHI encolado.", icon: "info" });

        safeDestroyDataTable(dt.value || "#example23");
        safeInitDataTable(dt.value || "#example23");
    } catch (error) {
        item.estado_envio_sahi = "ERROR";
        item.error_envio_sahi = error.response?.data?.message || "No se pudo reenviar a SAHI.";
        toastMessage({
            heading: "SAHI",
            text: item.error_envio_sahi,
            icon: "warning",
            hideAfter: 4500,
        });
    } finally {
        setMapValue(proxy, "isEnviandoSahi", id, false);
    }
}

/**
 * Hook de ciclo de vida que inicializa la sede al montar el componente.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2025-11-18
 */
onMounted(() => {
    initSede();
});
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

.breadcrumb-btn {
    vertical-align: baseline;
}
</style>
