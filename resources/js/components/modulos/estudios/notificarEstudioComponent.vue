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
                                                v-model="busqueda.bus_fech_ini"
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
                        <table-skeleton-loader :visible="loadingBuscar" :rows="6" :columns="8" />
                        <empty-state-component
                            v-if="!loadingBuscar && registros.length === 0"
                            icon="fa fa-folder-open-o"
                            title="No hay estudios disponibles para notificar"
                            description="No se encontraron estudios informados pendientes de notificación con los filtros seleccionados o use los filtros para buscar."
                            action-text="Limpiar filtros"
                            action-icon="fa fa-refresh"
                            @action="btnLimpiar()"
                        />
                        <div
                            class="table-responsive"
                            v-show="!loadingBuscar && registros.length > 0"
                        >
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
                                                    loadingImprimir === item.estudio_producto_id
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingImprimir === item.estudio_producto_id
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
        <div
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible }"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <button
                        type="button"
                        class="right-side-toggle"
                        @click="btnCerrarModalForm()"
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
                                            <label class="control-label" for="email"
                                                >Correo electrónico
                                                <span class="text-danger">*</span></label
                                            >
                                            <input
                                                type="email"
                                                id="email"
                                                name="email"
                                                class="form-control"
                                                :class="getInputClass(statusEmail)"
                                                v-model="registro.email"
                                                placeholder="Correo electrónico del paciente"
                                                @blur="touch('email')"
                                            />
                                            <div
                                                class="invalid-feedback d-block"
                                                v-if="statusEmail.isInvalid"
                                            >
                                                {{ statusEmail.message }}
                                            </div>
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
                                    title="Notificar por email (Ctrl + S)"
                                >
                                    <i v-if="loadingNotificar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-envelope-o"></i>
                                    Notificar
                                    <span
                                        class="badge badge-light text-dark ml-1 d-none d-md-inline"
                                        style="font-size: 10px"
                                        >Ctrl+S</span
                                    >
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    @click="btnCerrarModalForm()"
                                    title="Cerrar panel (Esc)"
                                >
                                    Cancelar
                                    <span
                                        class="badge badge-secondary ml-1 d-none d-md-inline"
                                        style="font-size: 10px"
                                        >Esc</span
                                    >
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
<script setup>
import { computed, getCurrentInstance, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import DatosPacienteDisplay from "@/components/comunes/datosPacienteDisplayComponent.vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { getUserSafeError, toastMessage } from "@/helpers/alerts.js";
import { useEstudioHelpers } from "@/composables/useEstudioHelpers";
import { useFormValidation } from "@/composables/useFormValidation";
import { useKeyboardShortcuts } from "@/composables/useKeyboardShortcuts";
import axios from "axios";

defineProps({
    usuarioactual: { type: Object, default: () => ({}) },
    permisos: { type: Array, default: () => [] },
});

const { loadingImprimir, imprimirReporte } = useEstudioHelpers();

const { appContext } = getCurrentInstance();
const emitter = appContext.config.globalProperties.emitter;

const dt = ref(null);
const registros = ref([]);
const tituloModal = ref("Notificar estudio");
const registro = ref({
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
});
const estudio = ref({});
const busqueda = ref({ bus_num_docu: "", bus_nom: "", bus_fech_ini: "", bus_fecha_fin: "" });
const loadingBuscar = ref(false);
const loadingNotificar = ref(false);
const loadingAbrirId = ref(null);
const loadingPortalId = ref(null);
const sidebarVisible = ref(false);

useKeyboardShortcuts({
    onSave: notificar,
    onEscape: btnCerrarModalForm,
    isActive: () => sidebarVisible.value,
    isLoading: loadingNotificar,
});

const { touch, getFieldStatus, getInputClass, validators, resetTouched } = useFormValidation();

const statusEmail = computed(() =>
    getFieldStatus("email", registro.value.email, [validators.required, validators.email])
);

const contadorRegistros = computed(() => {
    return registros.value.length;
});

function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#example23");
}
function btnLimpiar() {
    busqueda.value.bus_num_docu = "";
    busqueda.value.bus_nom = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";
    destroyDataTable();
    registros.value = [];
    limpiar();
}

/**
 * Buscar estudios para notificar
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa la búsqueda de estudios.
 * @author: Jhon Javer Lozano Arce
 */
async function buscarStudy() {
    if (loadingBuscar.value) return;

    if (
        busqueda.value.bus_num_docu == "" &&
        busqueda.value.bus_fech_ini == "" &&
        busqueda.value.bus_fecha_fin == "" &&
        busqueda.value.bus_nom == ""
    ) {
        toastMessage({
            heading: "Upsss!!!",
            text: "Debe indicar documento, nombre o un rango de fechas para la búsqueda.",
            icon: "warning",
        });
        return;
    }

    loadingBuscar.value = true;
    try {
        const res = await axios.post("/api/listar.estudios.notificaciones", busqueda.value);

        registros.value = res.data.data || [];

        nextTick(() => {
            destroyDataTable();
            safeInitDataTable(dt.value || "#example23", {
                dom: "Bfrtip",
                buttons: ["copy", "csv", "excel", "pdf", "print"],
            });
        });
    } catch (error) {
        registros.value = [];
        toastMessage({
            heading: "Error",
            text: getUserSafeError(error, "Error al listar estudios."),
            icon: "error",
        });
    } finally {
        loadingBuscar.value = false;
    }
}

/**
 * Notificar estudio
 * @param {Object} estudio - Objeto que contiene los datos del estudio a notificar.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa la notificación del estudio.
 * @author: Jhon Javer Lozano Arce
 */
async function notificar() {
    if (loadingNotificar.value) return;

    touch("email");
    if (statusEmail.value.isInvalid) {
        toastMessage({
            heading: "Atención",
            text:
                statusEmail.value.message ||
                "Ingrese un correo electrónico válido para notificar al paciente.",
            icon: "warning",
        });
        return;
    }

    const email = (registro.value.email || "").trim();

    loadingNotificar.value = true;
    try {
        toastMessage({
            heading: "Procesando...",
            text: "Generando reporte, por favor espere.",
            icon: "info",
        });

        const payload = {
            ...estudio.value,
            id_estudio: estudio.value.id,
            email: email,
            tel: registro.value.tel,
        };
        const res = await axios.post("/api/notificar.email", payload);

        if (res.status == 200) {
            toastMessage({
                heading: "Completado",
                text: "La notificación se realizó correctamente, estamos enviando el correo electronico, por favor espere..",
                icon: "success",
            });
        }

        btnCerralModalForm();
        limpiar();
    } catch (error) {
        const status = error?.response?.status;
        if (status == 402) {
            toastMessage({
                heading: "Error",
                text: getUserSafeError(error, "Saldo insuficiente."),
                icon: "error",
            });
        } else {
            toastMessage({
                heading: "Upss!!!",
                text: getUserSafeError(error, "No fue posible notificar."),
                icon: "error",
            });
        }
    } finally {
        loadingNotificar.value = false;
    }
}

/**
 * Enviar al portal de pacientes
 * @param {Object} item - Objeto que contiene los datos del estudio a enviar al portal de pacientes.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa el envío al portal de pacientes.
 * @author: Jhon Javer Lozano Arce
 */
async function enviarPortal(item) {
    if (loadingPortalId.value) return;
    loadingPortalId.value = item.estudio_producto_id;
    try {
        setarDatosPortalPacientes(item);
        await enviarPortalPacientes();
    } finally {
        loadingPortalId.value = null;
    }
}

/**
 * Enviar al portal de pacientes
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa el envío al portal de pacientes.
 * @author: Jhon Javer Lozano Arce
 */
/**
 * Enviar al portal de pacientes
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa el envío al portal de pacientes.
 * @author: Jhon Javer Lozano Arce
 */
async function enviarPortalPacientes() {
    try {
        const res = await axios.post("/api/enviar-portal-pacientes", estudio.value);

        if (res.status == 200) {
            toastMessage({
                heading: "Completado",
                text: "Enviado al portal de pacientes.",
                icon: "success",
            });

            limpiar();
        }
    } catch (error) {
        if (error.response && error.response.status == 402) {
            toastMessage({
                heading: "Error",
                text: getUserSafeError(error, "Saldo insuficiente."),
                icon: "error",
            });
        } else {
            toastMessage({
                heading: "Error",
                text: getUserSafeError(error, "No fue posible enviar al portal de pacientes."),
                icon: "error",
            });
        }
    }
}

function abrirModal() {
    sidebarVisible.value = true;
}

function btnCerralModalForm() {
    sidebarVisible.value = false;
}
const btnCerrarModalForm = btnCerralModalForm;

/**
 * Mostrar el registro del estudio en el modal para notificar al paciente.
 * @param {Object} data - Objeto que contiene los datos del estudio a mostrar.
 * @returns {Promise<void>} - Una promesa que se resuelve cuando se completa la carga del registro.
 * @author: Jhon Javer Lozano Arce
 */
function mostrarRegistro(data = {}) {
    if (loadingAbrirId.value) return;
    loadingAbrirId.value = data.estudio_producto_id;
    estudio.value = { ...data, id_estudio: data.id };
    tituloModal.value = "Notificar al paciente: " + data.nom_pacien;

    registro.value.num_docu = data.num_docu;
    registro.value.nombres = data.nom_pacien;
    registro.value.sexo = data.sexo;
    registro.value.fec_naci = data.fec_naci;
    registro.value.tel = data.tel;
    registro.value.email = data.email;
    registro.value.nom_produc = data.nom_produc;
    registro.value.id_producto_lectura = data.id;

    resetTouched();
    abrirModal();
    nextTick(() => {
        document.getElementById("email")?.focus();
    });
    loadingAbrirId.value = null;
}

/**
 * Setear los datos del estudio en el registro para enviar al portal de pacientes.
 * @param {Object} data - Objeto que contiene los datos del estudio a enviar al portal de pacientes.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function setarDatosPortalPacientes(data = {}) {
    estudio.value = { ...data, id_estudio: data.id };
    tituloModal.value = "Notificar al paciente: " + data.nom_pacien;

    registro.value.num_docu = data.num_docu;
    registro.value.nombres = data.nom_pacien;
    registro.value.sexo = data.sexo;
    registro.value.fec_naci = data.fec_naci;
    registro.value.tel = data.tel;
    registro.value.email = data.email;
    registro.value.nom_produc = data.nom_produc;
    registro.value.id_producto_lectura = data.id;
}

/**
 * Limpia el formulario de notificación, incluyendo email y dirección del
 * paciente para evitar el envío accidental de datos (PHI) al paciente
 * equivocado en una notificación posterior.
 * @returns {void}
 * @author: Jhon Javer Lozano Arce
 */
function limpiar() {
    registro.value.num_docu = "";
    registro.value.nombres = "";
    registro.value.sexo = "";
    registro.value.fec_naci = "";
    registro.value.tel = "";
    registro.value.email = "";
    registro.value.email_reportar = "";
    registro.value.direccion = "";
    registro.value.nom_produc = "";
    registro.value.id_producto_lectura = "";
}

/**
 * Maneja el evento global "sedeSeleccionada": limpia los resultados actuales
 * y actualiza el filtro de sede para que la próxima búsqueda sea en la sede correcta.
 *
 * @param {Object} data - Payload del evento, contiene data.idSede con el ID de la sede elegida.
 *
 * @returns {void}
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-14
 */
function onSedeSeleccionada(data) {
    busqueda.value.sede_id = data.idSede;
    destroyDataTable();
    registros.value = [];
    limpiar();
}

onMounted(() => {
    emitter.on("sedeSeleccionada", onSedeSeleccionada);
});

onBeforeUnmount(() => {
    emitter.off("sedeSeleccionada", onSedeSeleccionada);
    destroyDataTable();
    registros.value = [];
    registro.value = {};
});
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
