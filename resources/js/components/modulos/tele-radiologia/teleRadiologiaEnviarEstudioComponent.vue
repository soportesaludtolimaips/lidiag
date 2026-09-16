<template>
    <div>
        <!-- Bread crumb -->
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
                    <li class="breadcrumb-item active">Tele radiologia</li>
                </ol>
            </div>
        </div>

        <!-- Filtros -->
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
                                            <label class="control-label"
                                                >Nombres de paciente o # de documento
                                            </label>
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder="Nombres de paciente o # de documento"
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
                                        @click="buscarStudy()"
                                        :disabled="loadingBuscar"
                                    >
                                        <i v-if="loadingBuscar" class="fa fa-spinner fa-spin"></i>
                                        <i v-else class="fa fa-search"></i> Buscar
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary m-r-5"
                                        @click="buscarStudy()"
                                        :disabled="loadingBuscar"
                                    >
                                        <i class="fa fa-sync"></i> Actualizar
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

        <!-- Tabla estudios -->
        <div class="row">
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Listado de estudios disponibles</h4>
                        <h6 class="card-subtitle text-info">
                            Asigne los estudios a los médicos para su respectiva lectura.
                        </h6>
                        <div
                            v-if="registros.length === 0 && !loadingBuscar"
                            class="text-center py-4 text-muted"
                        >
                            <i class="fa fa-search fa-3x mb-3"></i>
                            <p>No hay estudios para mostrar. Use los filtros para buscar.</p>
                        </div>
                        <div v-else class="table-responsive">
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
                                        <th># Documento</th>
                                        <th>Paciente</th>
                                        <th>Accession</th>
                                        <th>Descripción</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in registros"
                                        :key="item.study_pk"
                                        :id="item.study_pk"
                                        :class="{ 'text-info': item.agendado !== 0 }"
                                    >
                                        <td>{{ item.study_datetime }}</td>
                                        <td>{{ item.pat_id }}</td>
                                        <td>{{ item.pat_name }}</td>
                                        <td>{{ item.accession_no }}</td>
                                        <td>{{ item.study_desc }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-primary btn-sm m-r-5"
                                                :disabled="loadingAbrirIndex === item.study_pk"
                                                @click="mostrarRegistro(item)"
                                                title="Ordenar estudio"
                                            >
                                                <i
                                                    v-if="loadingAbrirIndex === item.study_pk"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-child"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-info btn-sm"
                                                :disabled="loadingImagenIndex === item.study_pk"
                                                @click="
                                                    verImagen(
                                                        item.pat_id,
                                                        item.study_iuid,
                                                        item.study_pk
                                                    )
                                                "
                                                title="Ver imagenes"
                                            >
                                                <i
                                                    v-if="loadingImagenIndex === item.study_pk"
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-file-photo-o"></i>
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

        <!-- Right sidebar -->
        <div
            class="right-sidebar"
            :class="{ 'shw-rside': sidebarVisible }"
            style="overflow-x: hidden; overflow-y: scroll; height: 100%"
        >
            <div class="slimscrollright">
                <div class="rpanel-title">
                    {{ tituloModal }}
                    <span>
                        <i class="ti-close right-side-toggle" @click="btnCerrarModal()"></i>
                    </span>
                </div>
                <div class="r-panel-body">
                    <form action="#">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Paciente
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label"># Documento</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.paciente.num_docu"
                                                    class="form-control"
                                                    placeholder="# de Documento"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Nombres</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.paciente.nom_pacien"
                                                    class="form-control"
                                                    placeholder="Nombres"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Sexo</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.paciente.sexo"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="control-label">Fec. Nacim.</label>
                                                <input
                                                    type="date"
                                                    v-model="orden.paciente.fec_naci"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Dirección</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.paciente.direccion"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Télefono</label>
                                                <input
                                                    type="number"
                                                    v-model="orden.paciente.tel"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">E - Mail</label>
                                                <input
                                                    type="email"
                                                    v-model="orden.paciente.email"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Datos del Estudio
                                    </h5>
                                    <hr />

                                    <input type="hidden" v-model="orden.study_pk" />
                                    <input type="hidden" v-model="orden.study_iuid" />
                                    <input type="hidden" v-model="orden.study_id" />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="control-label">Descripción</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.study_desc"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Detalle</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.accession_no"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="control-label">Fecha</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.fec_estudio"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Observaciones</label>
                                                <input
                                                    type="text"
                                                    v-model="orden.observaciones"
                                                    class="form-control"
                                                    placeholder="Ingrese las observaciones del estudio"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-edit"></i> Adjuntos
                                    </h5>
                                    <hr />

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div
                                                v-for="(adj, adjIdx) in adjuntosFileInputs"
                                                :key="adjIdx"
                                                class="form-group"
                                            >
                                                <label class="control-label">
                                                    Archivo adjunto {{ adjIdx + 1 }}
                                                    <span
                                                        v-if="adj.nombre"
                                                        class="text-success ml-2"
                                                    >
                                                        <i class="fa fa-check"></i>
                                                        {{ adj.nombre }}
                                                    </span>
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control"
                                                    :accept="ADJUNTO_ACCEPT"
                                                    @change="(e) => obtenerArchivo(e, adjIdx)"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos de la Asignacion
                                    </h5>
                                    <hr />
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Prioridad</label>
                                                <select
                                                    v-model="orden.priori.id"
                                                    class="form-control custom-select"
                                                    @change="establecerPrioridad"
                                                >
                                                    <option
                                                        v-for="item in prioridades"
                                                        :key="item.id"
                                                        :value="item.id"
                                                    >
                                                        {{ item.nom_priori }} - {{ item.tiempo }}
                                                        {{ item.tipo_tiempo }}
                                                    </option>
                                                </select>
                                                <span
                                                    class="text-danger"
                                                    v-if="errores.prioridad_id"
                                                >
                                                    {{ errores.prioridad_id[0] }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4" v-if="sedeInterface">
                                            <div class="form-group">
                                                <label class="control-label">Atención</label>
                                                <select
                                                    v-model="interfaceAtencionSeleccionada"
                                                    class="form-control custom-select"
                                                    @change="interfaceListarProductosDiagnosticos()"
                                                >
                                                    <option
                                                        v-for="item in interfaceAtenciones"
                                                        :key="item.IdAtencion"
                                                        :value="item.IdAtencion"
                                                    >
                                                        {{ item.IdAtencion }} -
                                                        {{ item.FecIngreso }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> PRODUCTOS
                                    </h5>
                                    <hr />

                                    <span class="text-danger" v-if="errores.productosEstudio">
                                        {{ errores.productosEstudio[0] }}
                                    </span>
                                    <div class="row">
                                        <div class="col-md-12" v-if="!sedeInterface">
                                            <Select2
                                                :value="productoSeleccionado"
                                                :options="productoOptions"
                                                width="100%"
                                                :allow-clear="true"
                                                @input="onProductoSelect"
                                            />
                                        </div>
                                        <div
                                            class="col-md-12 table-responsive"
                                            style="overflow-y: scroll; height: 250px"
                                        >
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Código</th>
                                                        <th>Producto</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="(item, pIdx) in productosInterface"
                                                        :key="item.id"
                                                    >
                                                        <td>
                                                            <input
                                                                type="checkbox"
                                                                :id="`prod-check-${pIdx}`"
                                                                :checked="
                                                                    orden.producto.some(
                                                                        (p) =>
                                                                            p.cod_cups ==
                                                                            item.cod_cups
                                                                    )
                                                                "
                                                                @change="
                                                                    guardarProductosEstudio(
                                                                        item.id,
                                                                        item.cod_cups,
                                                                        item.nom_produc
                                                                    )
                                                                "
                                                            />
                                                            <label
                                                                :for="`prod-check-${pIdx}`"
                                                            ></label>
                                                        </td>
                                                        <td>{{ item.cod_cups }}</td>
                                                        <td>{{ item.nom_produc }}</td>
                                                        <td class="text-nowrap">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm"
                                                                @click="
                                                                    quitarProductoEstudio(
                                                                        item.cod_cups
                                                                    )
                                                                "
                                                            >
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-pencil"></i> MOTIVO DEL EXAMEN
                                    </h5>
                                    <hr />

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <textarea
                                                    v-model="orden.motivo_examen"
                                                    class="form-control"
                                                    placeholder="Ingrese las observaciones del estudio"
                                                    rows="11"
                                                ></textarea>
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
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i> Guardar
                                </button>

                                <button type="button" class="btn btn-inverse" @click="btnLimpiar()">
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

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { safeDestroyDataTable, safeInitDataTable } from "@/helpers/datatables.js";
import { getUserSafeError, toastMessage } from "@/helpers/alerts";
import axios from "axios";
import usePermissions from "@/helpers/permissions.js";

const DESTINO_TELE_RADIOLOGIA = "Tele Radiologia";
const ADJUNTO_ACCEPT = ".pdf,.doc,.docx";
const ADJUNTO_COUNT = 3;

const props = defineProps({
    usuarioactual: { type: Object, required: true },
    permisos: { type: Array, default: () => [] },
});

usePermissions(props.permisos);

const dt = ref(null);
const sidebarVisible = ref(false);
const registros = ref([]);
const tituloModal = ref("Nuevo registro");
const orden = ref({
    study_pk: "",
    study_iuid: "",
    study_id: "",
    fec_estudio: "",
    accession_no: "",
    study_desc: "",
    mods_in_study: "",
    observaciones: "",
    paciente: {
        tipo_docu: "CC",
        num_docu: "",
        nom_pacien: "",
        fec_naci: "",
        sexo: "",
        tel: "",
        telefono: "",
        email: "",
        direccion: "",
    },
    priori: {
        id: "",
        nom_priori: "",
    },
    sede: {
        id: "",
        nom_sede: "",
    },
    producto: [],
    adjuntos: [],
    motivo_examen: null,
});
const registro = ref({
    study_pk: "",
    study_iuid: "",
    study_datetime: "",
    study_id: "",
    accession_no: "",
    study_desc: "",
    mods_in_study: "",
    observaciones: "",
    medico_id: "",
    prioridad_id: "",
    sede_id: "",
    quien_registro_id: props.usuarioactual.id,
    num_docu: "",
    nombres: "",
    sexo: "",
    fec_naci: "",
    email: "",
    email_reportar: "",
    direccion: "",
    tel: "",
    productosEstudio: [],
    diagnosticosEstudio: [],
    atencion_id: null,
    destino_estudio: DESTINO_TELE_RADIOLOGIA,
    destino_estudio_id: null,
    motivo_examen: null,
});
const adjuntosFileInputs = ref(
    Array.from({ length: ADJUNTO_COUNT }, () => ({
        nombre: null,
        archivo: null,
    }))
);
const busqueda = ref({
    sede_id: "",
    bus_nom_num_docu: "",
    bus_fech_ini: "",
    bus_fecha_fin: "",
});
const sedeInterface = ref(false);
const interfaceAtenciones = ref([]);
const interfaceAtencionSeleccionada = ref(null);
const errores = ref({});
const prioridades = ref([]);
const productos = ref([]);
const productosInterface = ref([]);
const productoSeleccionado = ref("");
const idSedeActual = ref("");
const nomSedeActual = ref("");
const loadingBuscar = ref(false);
const loadingGuardar = ref(false);
const loadingAbrirIndex = ref(null);
const loadingImagenIndex = ref(null);

const productoOptions = computed(() => {
    return (productos.value || []).map((p) => ({
        id: String(p.id),
        cod_cups: p.cod_cups,
        nom_produc: p.nom_produc,
    }));
});

function initDataTable() {
    safeInitDataTable(dt.value || "#example23", {
        dom: "Bfrtip",
        buttons: ["copy", "csv", "excel", "pdf", "print"],
        retrieve: true,
    });
}

function destroyDataTable() {
    safeDestroyDataTable(dt.value || "#example23");
}

async function buscarStudy() {
    if (loadingBuscar.value) return;
    loadingBuscar.value = true;

    try {
        if (sessionStorage.getItem("ST-sede") == null) {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes seleccionar una sede para realizar la busqueda.",
                icon: "warning",
            });
            return;
        }
        if (
            busqueda.value.bus_nom_num_docu == "" &&
            busqueda.value.bus_fech_ini == "" &&
            busqueda.value.bus_fecha_fin == ""
        ) {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes establecer la fecha de inicio o finalización para la busqueda.",
                icon: "warning",
            });
            return;
        }

        const res = await axios.post("/api/tele-radiologia-listar-estudios", busqueda.value);

        destroyDataTable();
        registros.value = res.data.data || [];
        nextTick(() => {
            initDataTable();
        });
    } catch (error) {
        const status = error?.response?.status;
        if (status == 500) {
            toastMessage({
                heading: "Upsss!!!",
                text: getUserSafeError(error, "Error al listar estudios."),
                icon: "error",
            });
        }
    } finally {
        loadingBuscar.value = false;
    }
}

async function guardarRegistro() {
    if (loadingGuardar.value) return;
    loadingGuardar.value = true;
    try {
        if (orden.value.producto.length === 0) {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes seleccionar al menos un producto",
                icon: "warning",
            });
            return;
        }

        if (orden.value.priori.id === "") {
            toastMessage({
                heading: "Upsss!!!",
                text: "Debes seleccionar la prioridad de la orden",
                icon: "warning",
            });
            return;
        }

        orden.value.paciente.telefono = orden.value.paciente.tel;
        registro.value.tel = orden.value.paciente.tel;
        registro.value.direccion = orden.value.paciente.direccion;
        registro.value.email = orden.value.paciente.email;
        registro.value.motivo_examen = orden.value.motivo_examen;

        const payloadOrden = {
            study_pk: orden.value.study_pk,
            study_iuid: orden.value.study_iuid,
            fec_estudio: orden.value.fec_estudio,
            accession_no: orden.value.accession_no,
            study_desc: orden.value.study_desc,
            mods_in_study: orden.value.mods_in_study,
            observaciones: orden.value.observaciones,
            paciente: { ...orden.value.paciente },
            priori: { ...orden.value.priori },
            sede: { ...orden.value.sede },
            producto: orden.value.producto.map((p) => ({ ...p })),
            adjuntos: orden.value.adjuntos.map((a) => ({
                file_exten: a.file_exten,
            })),
            motivo_examen: orden.value.motivo_examen,
        };

        const resOrdenTele = await axios.post("/api/tele-radiologia-enviar-orden", {
            orden: payloadOrden,
        });

        const confirmation =
            resOrdenTele?.data?.confirmation ??
            resOrdenTele?.data?.data?.confirmation ??
            resOrdenTele?.data?.id ??
            null;

        if (resOrdenTele.status == 201 || resOrdenTele.status == 200) {
            registro.value.destino_estudio_id = confirmation;

            toastMessage({
                heading: "Ok!!!",
                text: resOrdenTele?.data?.details || "Orden enviada correctamente.",
                icon: "success",
            });

            const formData = new FormData();
            formData.append("registro", JSON.stringify(registro.value));
            adjuntosFileInputs.value.forEach((adj, idx) => {
                if (adj.archivo) {
                    formData.append(`archivo${idx + 1}`, adj.archivo);
                }
            });

            const resEstudio = await axios.post("/api/estudios", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            if (resEstudio.status == 200) {
                toastMessage({
                    heading: "Ok!!!",
                    text: resEstudio.data.message,
                    icon: "success",
                });
            }
        }

        btnCerrarModal();
        btnLimpiar();
    } catch (error) {
        if (error.response) {
            errores.value = error.response.data;
        }
        toastMessage({
            heading: "Error",
            text: error.response?.data?.message || "No fue posible enviar la orden.",
            icon: "error",
        });
    } finally {
        loadingGuardar.value = false;
    }
}

function toBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = (error) => reject(error);
    });
}

function extensionDesdeBase64(base64) {
    const mime = base64.substring(base64.indexOf(":") + 1, base64.indexOf(";"));
    const mapa = {
        "image/jpeg": "jpg",
        "image/png": "png",
        "application/pdf": "pdf",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document": "docx",
        "application/msword": "doc",
    };
    return mapa[mime] ?? null;
}

async function obtenerArchivo(e, index) {
    const file = e.target.files[0];
    if (!file) return;
    const fileBase64 = await toBase64(file);
    const extension = extensionDesdeBase64(fileBase64);
    orden.value.adjuntos.push({
        file: fileBase64,
        file_exten: extension,
    });
    adjuntosFileInputs.value[index].nombre = file.name;
    adjuntosFileInputs.value[index].archivo = file;
}

async function mostrarRegistro(data = {}) {
    limpiar();
    tituloModal.value = "Generar orden: " + data.pat_name;
    orden.value.study_pk = data.study_pk;
    orden.value.study_iuid = data.study_iuid;
    orden.value.fec_estudio = data.study_datetime;
    orden.value.accession_no = data.accession_no;
    orden.value.study_desc = data.study_desc;
    orden.value.mods_in_study = data.mods_in_study;
    orden.value.paciente.num_docu = data.pat_id;
    orden.value.paciente.nom_pacien = data.pat_name;
    orden.value.paciente.sexo = data.pat_sex;
    orden.value.paciente.fec_naci = data.pat_birthdate;

    registro.value.study_pk = data.study_pk;
    registro.value.study_iuid = data.study_iuid;
    registro.value.study_id = data.study_id;
    registro.value.study_datetime = data.study_datetime;
    registro.value.accession_no = data.accession_no;
    registro.value.study_desc = data.study_desc;
    registro.value.mods_in_study = data.mods_in_study;
    registro.value.email = "";
    registro.value.email_reportar = sessionStorage.getItem("ST-email_repor");
    registro.value.num_docu = data.pat_id;
    registro.value.nombres = data.pat_name;
    registro.value.sexo = data.pat_sex;
    registro.value.fec_naci = data.pat_birthdate;

    abrirModal();

    try {
        const res = await axios.get("/api/config-sedes/" + idSedeActual.value + "/buscarPorId");
        sedeInterface.value = res.data.data.interface_software != null;
        if (sedeInterface.value) {
            const resAtenciones = await axios.get(
                "/api/interface-sahi-listar-atenciones/" + orden.value.paciente.num_docu
            );
            interfaceAtenciones.value = resAtenciones.data.data;
        }
    } catch (_e) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los datos de la sede.",
            icon: "error",
        });
    }
}

async function interfaceListarProductosDiagnosticos() {
    try {
        const resProductos = await axios.get(
            "/api/interface-sahi-listar-listarProductosDiagnosticos/" +
                interfaceAtencionSeleccionada.value
        );
        const data = resProductos.data.data;
        productosInterface.value = data.productos;

        let EnfermedadActualDiagnosticos = "";

        const consultas = data.consultas || [];
        EnfermedadActualDiagnosticos = "Enfermedad Actual: ";
        if (consultas.length > 0) {
            consultas.forEach((c) => {
                EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos}\n ${c.DesEnfermedadActual} \n`;
            });
        }

        const diagnosticos = data.diagnosticos || [];
        EnfermedadActualDiagnosticos += `\nDiagnosticos:`;
        if (diagnosticos.length > 0) {
            diagnosticos.forEach((d) => {
                EnfermedadActualDiagnosticos = `${EnfermedadActualDiagnosticos} \n ${d.cod_diagnos} - ${d.nom_diagnos}`;
            });
        }

        orden.value.motivo_examen = EnfermedadActualDiagnosticos;
        registro.value.motivo_examen = EnfermedadActualDiagnosticos;

        registro.value.diagnosticosEstudio = diagnosticos.length > 0 ? diagnosticos : [];
        registro.value.atencion_id = interfaceAtencionSeleccionada.value;
    } catch (_e) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar productos/diagnósticos de la atención.",
            icon: "error",
        });
    }
}

function establecerPrioridad() {
    const seleccion = prioridades.value.find((p) => p.id === orden.value.priori.id);
    if (seleccion) {
        orden.value.priori.nom_priori = seleccion.nom_priori;
        registro.value.prioridad_id = seleccion.id;
    }
}

async function establecerSede() {
    orden.value.sede.id = sessionStorage.getItem("ST-sede");
    busqueda.value.sede_id = sessionStorage.getItem("ST-sede");
    idSedeActual.value = sessionStorage.getItem("ST-sede");
    nomSedeActual.value = sessionStorage.getItem("ST-nomsede");
    registro.value.sede_id = sessionStorage.getItem("ST-sede");
    registros.value = [];
}

function btnCerrarModal() {
    sidebarVisible.value = false;
    errores.value = {};
}

function abrirModal() {
    sidebarVisible.value = true;
}

function limpiar() {
    orden.value.study_pk = "";
    orden.value.study_iuid = "";
    orden.value.study_id = "";
    orden.value.fec_estudio = "";
    orden.value.accession_no = "";
    orden.value.study_desc = "";
    orden.value.mods_in_study = "";
    orden.value.observaciones = "";
    orden.value.paciente.num_docu = "";
    orden.value.paciente.nom_pacien = "";
    orden.value.paciente.sexo = "";
    orden.value.paciente.fec_naci = "";
    orden.value.paciente.tel = "";
    orden.value.paciente.email = "";
    orden.value.paciente.direccion = "";
    orden.value.priori.id = "";
    orden.value.priori.nom_priori = "";
    orden.value.producto = [];
    orden.value.adjuntos = [];
    orden.value.motivo_examen = null;

    registro.value.diagnosticosEstudio = [];
    registro.value.productosEstudio = [];
    registro.value.atencion_id = null;
    registro.value.destino_estudio_id = null;
    registro.value.destino_estudio = DESTINO_TELE_RADIOLOGIA;

    productoSeleccionado.value = "";
    productosInterface.value = [];
    interfaceAtenciones.value = [];
    interfaceAtencionSeleccionada.value = null;
    sedeInterface.value = false;

    adjuntosFileInputs.value = Array.from({ length: ADJUNTO_COUNT }, () => ({
        nombre: null,
        archivo: null,
    }));

    errores.value = {};
}

function btnLimpiar() {
    busqueda.value.bus_nom_num_docu = "";
    busqueda.value.bus_fech_ini = "";
    busqueda.value.bus_fecha_fin = "";

    registros.value = [];
    destroyDataTable();

    limpiar();
    btnCerrarModal();
}

async function listarPrioridades() {
    try {
        const res = await axios.get("/api/config-prioridades");
        prioridades.value = res.data.data;
    } catch (_e) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar las prioridades.",
            icon: "error",
        });
        prioridades.value = [];
    }
}

async function listarProductos() {
    try {
        const res = await axios.get("/api/config-productos");
        productos.value = res.data.data;
    } catch (_e) {
        toastMessage({
            heading: "Error",
            text: "No fue posible cargar los productos.",
            icon: "error",
        });
        productos.value = [];
    }
}

function onProductoSelect(value) {
    let selectedId = null;
    if (Array.isArray(value) && value.length > 0) {
        const v = value[0];
        selectedId = String(typeof v === "object" && v && v.id != null ? v.id : v);
    } else if (value && typeof value === "object" && value.id != null) {
        selectedId = String(value.id);
    } else if (value != null) {
        selectedId = String(value);
    }
    if (selectedId != null) {
        productoSeleccionado.value = selectedId;
        const prod = productos.value.find((p) => String(p.id) === selectedId);
        if (prod) {
            guardarProductosEstudio(prod.id, prod.cod_cups, prod.nom_produc);
        }
    }
}

function guardarProductosEstudio(id, cod_cups, nom_produc) {
    const yaExisteIndex = orden.value.producto.findIndex((p) => p.cod_cups == cod_cups);
    if (yaExisteIndex !== -1) {
        orden.value.producto.splice(yaExisteIndex, 1);
        const registroIndex = registro.value.productosEstudio.findIndex(
            (p) => p.cod_cups == cod_cups
        );
        if (registroIndex !== -1) {
            registro.value.productosEstudio.splice(registroIndex, 1);
        }
    } else {
        orden.value.producto.push({
            cod_cups: cod_cups,
            nom_produc: nom_produc,
        });
        registro.value.productosEstudio.push({
            checked: true,
            id: id,
            cod_cups: cod_cups,
            nom_produc: nom_produc,
        });
    }
    productoSeleccionado.value = "";
}

function quitarProductoEstudio(codCups) {
    const idxOrden = orden.value.producto.findIndex((p) => p.cod_cups === codCups);
    if (idxOrden !== -1) orden.value.producto.splice(idxOrden, 1);
    const idxRegistro = registro.value.productosEstudio.findIndex((p) => p.cod_cups === codCups);
    if (idxRegistro !== -1) registro.value.productosEstudio.splice(idxRegistro, 1);
}

function verImagen(pat_id, study_iuid, studyPk) {
    if (loadingImagenIndex.value !== null) return;
    loadingImagenIndex.value = studyPk;

    const base = sessionStorage.getItem("ST-urlsede") || "";
    const params = new URLSearchParams({
        patientID: String(pat_id ?? ""),
        studyUID: String(study_iuid ?? ""),
        serverName: String(sessionStorage.getItem("ST-servername") ?? ""),
    });

    try {
        const newWindow = window.open(
            `${base}?${params.toString()}`,
            "_blank",
            "noopener,noreferrer"
        );
        if (!newWindow) {
            toastMessage({
                heading: "Atención",
                text: "El navegador bloqueó la apertura de la ventana. Permita pop-ups para este sitio.",
                icon: "warning",
            });
        }
    } finally {
        setTimeout(() => {
            loadingImagenIndex.value = null;
        }, 500);
    }
}

onMounted(() => {
    listarPrioridades();
    listarProductos();
    establecerSede();
});

onBeforeUnmount(() => {
    destroyDataTable();
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

@media (max-width: 768px) {
    .right-sidebar {
        width: 100%;
        right: -100%;
    }
    .shw-rside {
        width: 100%;
        right: 0;
    }
}
</style>
