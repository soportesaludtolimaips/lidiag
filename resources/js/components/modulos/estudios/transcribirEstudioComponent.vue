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
                    <li class="breadcrumb-item active">Transcribir Estudios</li>
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
                            <i class="fa fa-filter"></i> Filtros para busqueda de mis pendientes por
                            transcribir
                        </h4>
                        <form action="#">
                            <div class="form-body">
                                <div class="row p-t-2">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="quien_asigno_id">Quien asigno</label>
                                            <select
                                                v-model="busqueda.quien_asigno_id"
                                                name="quien_asigno_id"
                                                id="quien_asigno_id"
                                                class="form-control custom-select"
                                            >
                                                <option value="">
                                                    ...::: Todos los usuarios :::...
                                                </option>
                                                <option
                                                    v-for="(
                                                        ItemUsuario, index
                                                    ) in quienes_asignaron"
                                                    :key="index"
                                                    :value="ItemUsuario.id"
                                                >
                                                    {{ ItemUsuario.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="medico_id">Medicos</label>
                                            <select
                                                v-model="busqueda.medico_id"
                                                name="medico_id"
                                                id="medico_id"
                                                class="form-control custom-select"
                                            >
                                                <option value="" selected>
                                                    ...::: Todos los medicos :::...
                                                </option>
                                                <option
                                                    v-for="(ItemUsuario, index) in medicos"
                                                    :key="index"
                                                    :value="ItemUsuario.id"
                                                >
                                                    {{ ItemUsuario.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_nom_num_docu"
                                                >Nombres o # documento</label
                                            >
                                            <input
                                                type="text"
                                                id="bus_nom_num_docu"
                                                name="bus_nom_num_docu"
                                                v-model="busqueda.bus_nom_num_docu"
                                                class="form-control"
                                                placeholder="Nombres de paciente o # de documento"
                                                @keyup.enter="listarPendientesTrascribir()"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label" for="bus_fehc_ini"
                                                >Fecha Inicio</label
                                            >
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
                                            <label class="control-label" for="fecha_fin"
                                                >Fecha Fin</label
                                            >
                                            <input
                                                type="date"
                                                id="fecha_fin"
                                                name="fecha_fin"
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
                                        :disabled="loadingBuscar"
                                        @click="listarPendientesTrascribir()"
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
            <div class="col-12 table-responsive">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="card-title">Listado de estudios disponibles</h4>
                                <h6 class="card-subtitle text-info">
                                    Asigne los estudios a los médicos para su respectiva lectura.
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
                                        <th></th>
                                        <th>Sede</th>
                                        <th>Toma</th>
                                        <th width="50%">Lectura</th>
                                        <th>Asignación</th>
                                        <th>Quien Asigno</th>
                                        <th width="50%">Medico</th>
                                        <th width="50%">Paciente</th>
                                        <th>Detalle</th>
                                        <th>Producto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in registros"
                                        :key="item.estudio_producto_id"
                                        :id="'row-' + item.estudio_producto_id"
                                    >
                                        <td>
                                            <button
                                                type="button"
                                                v-if="item.priori_id == 1"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-danger"
                                            >
                                                A
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 2"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-warning"
                                            >
                                                M
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 3"
                                                class="btn waves-effect waves-light btn-rounded btn-sm btn-info"
                                            >
                                                B
                                            </button>
                                            <button
                                                type="button"
                                                v-else-if="item.priori_id == 4"
                                                class="btn waves-effect waves-light btn-rounded btn-sm"
                                            >
                                                S
                                            </button>
                                        </td>
                                        <td>{{ item.nom_sede }}</td>
                                        <td>{{ item.fec_estudio }}</td>
                                        <td>{{ item.fechor_lectura }}</td>
                                        <td>{{ formatDate(item.created_at) }}</td>
                                        <td>{{ item.quien_registro }}</td>
                                        <td>{{ item.medico }}</td>
                                        <td>
                                            # Docu: {{ item.num_docu }} <br />
                                            Nombres: {{ item.nom_pacien }}
                                        </td>
                                        <td>
                                            Atención: {{ item.atencion }} <br />
                                            Descrip: {{ item.study_desc }} <br />
                                            {{ item.accession_no }}
                                        </td>
                                        <td>{{ item.nom_produc }}</td>
                                        <td class="text-nowrap">
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-warning btn-sm m-r-5"
                                                :disabled="
                                                    loadingAbrirRegistroId ===
                                                    item.estudio_producto_id
                                                "
                                                @click="
                                                    actualizar = true;
                                                    mostrarRegistro(item);
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingAbrirRegistroId ===
                                                        item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-pencil"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn waves-effect waves-light btn-rounded btn-outline-danger btn-sm m-r-5"
                                                :disabled="
                                                    loadingAnularId === item.estudio_producto_id
                                                "
                                                @click="
                                                    anularAsignacion(
                                                        item.estudio_producto_id,
                                                        item.nom_pacien,
                                                        item.nom_produc
                                                    )
                                                "
                                            >
                                                <i
                                                    v-if="
                                                        loadingAnularId === item.estudio_producto_id
                                                    "
                                                    class="fa fa-spinner fa-spin"
                                                ></i>
                                                <i v-else class="fa fa-eraser"></i>
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
                                <p>No hay estudios para transcribir</p>
                            </div>
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
        <div class="right-sidebar sidebar-scroll">
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
                                <div class="col-md-6">
                                    <form-paciente :registro="registro" :errores="errores" />

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Datos del Estudio
                                    </h5>
                                    <hr />

                                    <input
                                        type="hidden"
                                        id="estudio_producto_id"
                                        name="estudio_producto_id"
                                        v-model="registro.estudio_producto_id"
                                    />
                                    <input
                                        type="hidden"
                                        id="email"
                                        name="email"
                                        v-model="registro.email"
                                    />

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Descripción:</dt>
                                                    <dd>{{ registro.study_desc }}</dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Detalle</dt>
                                                    <dd>{{ registro.accession_no }}</dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <dl>
                                                    <dt>Fecha del estudio</dt>
                                                    <dd>{{ registro.fec_estudio }}</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label" for="email_reportar"
                                                    >Reportar al Email
                                                    <span class="text-danger">*</span></label
                                                >
                                                <input
                                                    type="email"
                                                    id="email_reportar"
                                                    name="email_reportar"
                                                    v-model="registro.email_reportar"
                                                    class="form-control"
                                                    placeholder="Ingrese aquí el email a reportar"
                                                    ref="primerInput"
                                                />
                                            </div>

                                            <div class="form-group">
                                                <dl>
                                                    <dt>Observaciones</dt>
                                                    <dd>{{ registro.observaciones }}</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title text-info">
                                        <i class="fa fa-user"></i> Producto
                                    </h5>
                                    <hr />

                                    <div class="row table-responsive">
                                        <div class="col-md-12">
                                            <dl>
                                                <dd>{{ registro.nom_produc }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                    <br />
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-9">
                                            <h5 class="card-title text-info">
                                                <i class="fa fa-edit"></i> Lectura
                                            </h5>
                                            <hr />
                                        </div>
                                        <div class="col-3">
                                            <div
                                                class="col-md-7 align-self-center text-right d-none d-md-block"
                                            >
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary btn-xs"
                                                    @click="verImagen()"
                                                    :disabled="loadingImagen"
                                                >
                                                    <i
                                                        v-if="loadingImagen"
                                                        class="fa fa-spinner fa-spin"
                                                    ></i>
                                                    <i v-else class="fa fa-file-image-o"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-danger" v-if="errores.lectura">{{
                                        errores.lectura[0]
                                    }}</span>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <textarea
                                                    v-model="registro.lectura"
                                                    class="textarea_editor form-control"
                                                    rows="20"
                                                    id="lectura"
                                                    style="height: 100%"
                                                    placeholder="Ingrese aquí la lectura del estudio ..."
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <!-- Nav tabs -->
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="nav-item">
                                                    <a
                                                        class="nav-link active"
                                                        data-toggle="tab"
                                                        href="#home8"
                                                        role="tab"
                                                    >
                                                        <h5 class="card-title text-info">
                                                            <i class="fa fa-paperclip"></i> Adjuntos
                                                        </h5>
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a
                                                        class="nav-link"
                                                        data-toggle="tab"
                                                        href="#profile8"
                                                        role="tab"
                                                    >
                                                        <span>
                                                            <h5 class="card-title text-info">
                                                                <i class="fa fa-file-audio-o"></i>
                                                                Audios
                                                            </h5>
                                                        </span>
                                                    </a>
                                                </li>
                                            </ul>
                                            <!-- Tab panes -->
                                            <div class="tab-content tabcontent-border">
                                                <div
                                                    class="tab-pane active p-20"
                                                    id="home8"
                                                    role="tabpanel"
                                                >
                                                    <div
                                                        class="col-md-8 col-lg-9"
                                                        v-for="(itemArchivo, index) in soportesHC"
                                                        :key="index"
                                                    >
                                                        <a
                                                            href="javascript:void(0)"
                                                            :style="{
                                                                pointerEvents:
                                                                    loadingDescargaSoporte ===
                                                                    itemArchivo.archivo_encrip
                                                                        ? 'none'
                                                                        : 'auto',
                                                            }"
                                                            @click="
                                                                descargaSoportte(
                                                                    itemArchivo.archivo_encrip
                                                                )
                                                            "
                                                        >
                                                            <i
                                                                v-if="
                                                                    loadingDescargaSoporte ===
                                                                    itemArchivo.archivo_encrip
                                                                "
                                                                class="fa fa-spinner fa-spin"
                                                            ></i>
                                                            {{ itemArchivo.archivo_original }}
                                                        </a>
                                                    </div>
                                                </div>
                                                <div
                                                    class="tab-pane p-20"
                                                    id="profile8"
                                                    role="tabpanel"
                                                >
                                                    <div v-if="loadingAudios" class="text-muted">
                                                        <i class="fa fa-spinner fa-spin"></i>
                                                        Cargando audios...
                                                    </div>
                                                    <template v-if="!loadingAudios">
                                                        <div
                                                            v-for="(item, index) in audiosDeEstudio"
                                                            :key="item.id || index"
                                                            class="card mb-2"
                                                        >
                                                            <div class="card-body p-2">
                                                                <div
                                                                    class="d-flex align-items-center mb-1 flex-wrap"
                                                                >
                                                                    <i
                                                                        class="fa fa-file-audio-o text-muted mr-1"
                                                                    ></i>
                                                                    <strong
                                                                        class="mr-2 text-truncate"
                                                                        :title="
                                                                            item.nombre_original
                                                                        "
                                                                        style="max-width: 200px"
                                                                    >
                                                                        {{
                                                                            item.nombre_original ||
                                                                            "Audio " + (index + 1)
                                                                        }}
                                                                    </strong>
                                                                    <span
                                                                        class="badge mr-1"
                                                                        :class="
                                                                            item.tipo === 'cargado'
                                                                                ? 'badge-info'
                                                                                : 'badge-success'
                                                                        "
                                                                    >
                                                                        <i
                                                                            :class="
                                                                                item.tipo ===
                                                                                'cargado'
                                                                                    ? 'fa fa-upload'
                                                                                    : 'fa fa-microphone'
                                                                            "
                                                                        ></i>
                                                                        {{
                                                                            item.tipo === "cargado"
                                                                                ? "Cargado"
                                                                                : "Grabado"
                                                                        }}
                                                                    </span>
                                                                    <small
                                                                        class="text-muted"
                                                                        v-if="item.duracion"
                                                                    >
                                                                        <i
                                                                            class="fa fa-clock-o"
                                                                        ></i>
                                                                        {{
                                                                            formatDuracion(
                                                                                item.duracion
                                                                            )
                                                                        }}
                                                                    </small>
                                                                </div>
                                                                <audio
                                                                    :src="audioUrl(item)"
                                                                    controls
                                                                    style="
                                                                        height: 30px;
                                                                        width: 100%;
                                                                    "
                                                                ></audio>
                                                                <div
                                                                    v-if="item.transcripcion"
                                                                    class="bg-light rounded p-1 mt-1"
                                                                    style="
                                                                        font-size: 0.8rem;
                                                                        max-height: 40px;
                                                                        overflow: hidden;
                                                                    "
                                                                >
                                                                    {{
                                                                        previewTranscripcion(
                                                                            item.transcripcion,
                                                                            120
                                                                        )
                                                                    }}
                                                                    <span
                                                                        v-if="
                                                                            item.transcripcion
                                                                                .length > 120
                                                                        "
                                                                        class="text-primary small"
                                                                        >...</span
                                                                    >
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div
                                                            v-if="audiosDeEstudio.length === 0"
                                                            class="text-muted small"
                                                        >
                                                            No hay audios para este estudio.
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-3">
                                <button
                                    type="button"
                                    class="btn btn-success m-r-5"
                                    :disabled="loadingGuardar"
                                    @click="guardarRegistro()"
                                >
                                    <i v-if="loadingGuardar" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-check"></i>
                                    Guardar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-inverse"
                                    ref="btnCerrarForm"
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
import FormPaciente from "@/components/comunes/formPacienteComponent.vue";
import { listarUsuariosYSedes } from "../../../helpers/usuarios";
import {
    safeDestroyDataTable,
    safeInitDataTable,
    safeRemoveDataTableRow,
} from "@/helpers/datatables.js";
import {
    alertError,
    alertSuccess,
    confirmAction,
    getUserSafeError,
    handleAxiosErrorToast,
} from "@/helpers/alerts.js";

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";

dayjs.extend(utc);
dayjs.extend(timezone);

export default {
    props: ["usuarioactual"],
    components: {
        LoadingSpinner,
        FormPaciente,
    },
    computed: {
        contadorRegistros() {
            return this.registros.length;
        },
    },
    async mounted() {
        this.$nextTick(() => this.initWysiwyg());
        this.busqueda.sede_id = sessionStorage.getItem("ST-sede");

        await listarUsuariosYSedes(this, { moduloId: "1" });

        this.applyDashboardPrefill();

        this.emitter.on("sedeSeleccionada", (data) => {
            this.busqueda.sede_id = data.idSede;
            this.destroyDataTable();
            this.listarPendientesTrascribir();
        });
    },
    beforeUnmount() {
        this.destroyDataTable();
        this.registros = [];
        this.registro = { productosEstudio: [], diagnosticosEstudio: [], soportesHC: [] };
        this.soportesHC = [];
        this.audiosDeEstudio = [];
    },
    data() {
        const hoy = new Date();
        const yearActual = hoy.getFullYear();
        const fechaDefectoIni = yearActual + "-01-01";
        const fechaDefectoFin = hoy.toISOString().slice(0, 10);
        return {
            id: 0,
            diagnosticosEstudio: [],
            soportesHC: [],
            registros: [],
            tituloModal: "Transcirbir estudio",
            registro: {
                id_estudio: 0,
                estudio_producto_id: 0,
                lectura: "",
                fec_lectura: "",
                fec_estudio: "",
                accession_no: "",
                study_desc: "",
                observaciones: "",
                email_reportar: "",
                num_docu: "",
                nom_pacien: "",
                sexo: "",
                fec_naci: "",
                email: "",
                diagnosticosEstudio: [],
                sede_id: "",
                atencion: null,
                servicio: null,
                eps: null,
                contrato: null,
                medico_id: null,
                cod_cups: "",
                nom_produc: "",
                num_docu_medico: "",
                reg_medico: "",
                nombres_medico: "",
                firma_medico: "",
                url_oviyam: null,
                tap_oviyam: null,
                study_iuid: null,
            },
            busqueda: {
                estudioParaTranscibir: "si",
                quien_asigno_id: "",
                medico_id: "",
                sede_id: "",
                bus_nom_num_docu: "",
                bus_fehc_ini: fechaDefectoIni,
                bus_fecha_fin: fechaDefectoFin,
                destino_estudio: "Salud Tolima",
            },
            datosImagen: { urlOviyam: "", patientId: "", studyUID: "", serverName: "" },
            audiosDeEstudio: [],
            quienes_asignaron: [],
            medicos: [],
            errores: {},
            idRow: null,
            loadingBuscar: false,
            loadingGuardar: false,
            loadingAbrirRegistroId: null,
            loadingAnularId: null,
            loadingDescargaSoporte: null,
            loadingAudios: false,
            loadingImagen: false,
        };
    },
    methods: {
        applyDashboardPrefill() {
            try {
                const accession = sessionStorage.getItem("ST-accession");
                if (accession && String(accession).trim()) {
                    this.busqueda.bus_nom_num_docu = String(accession).trim();
                    // Consumir para no “pegar” el filtro en siguientes visitas
                    sessionStorage.removeItem("ST-accession");
                    // Lanzar búsqueda
                    this.destroyDataTable();
                    this.listarPendientesTrascribir();
                }
            } catch (e) {
                this.listarPendientesTrascribir();
            }
        },
        initWysiwyg() {
            try {
                const $root = $(this.$el);
                // soporte legacy: id textarea_editor y/o clase textarea_editor
                const $targets = $root.find("#textarea_editor, .textarea_editor");
                $targets.each((_, el) => {
                    const $el = $(el);
                    // si ya tiene toolbar asociada, no reinicializar
                    if ($el.data("wysihtml5")) return;
                    if ($el.prevAll(".wysihtml5-toolbar").length) return;
                    $el.wysihtml5({ stylesheets: ["/lib/css/wysiwyg-color.css"] });
                });
            } catch (e) {}
        },
        destroyDataTable() {
            safeDestroyDataTable(this.$refs.dt || "#example23");
        },
        btnLimpiar() {
            const hoy = new Date();
            const yearActual = hoy.getFullYear();
            this.busqueda.quien_asigno_id = "";
            this.busqueda.medico_id = "";
            this.busqueda.bus_nom_num_docu = "";
            this.busqueda.bus_fehc_ini = yearActual + "-01-01";
            this.busqueda.bus_fecha_fin = hoy.toISOString().slice(0, 10);
            this.errores = {};
            this.destroyDataTable();
            this.registros = [];
        },
        async listarPendientesTrascribir() {
            if (this.loadingBuscar) return;
            this.loadingBuscar = true;
            try {
                if (sessionStorage.getItem("ST-sede") == null) {
                    $.toast({
                        heading: "Upsss!!!",
                        text: "Debes establece la sede en la cual vas a trabajar.",
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "warning",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else {
                    this.destroyDataTable();

                    const res = await axios.post(
                        "/api/estudio-listarPendientesTrascribir",
                        this.busqueda
                    );

                    this.registros = res.data;

                    this.$nextTick(() => {
                        safeInitDataTable(this.$refs.dt || "#example23", {
                            dom: "Bfrtip",
                            buttons: ["copy", "csv", "excel", "pdf", "print"],
                        });
                    });
                }
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar los pendientes.");
            } finally {
                this.loadingBuscar = false;
            }
        },
        async guardarRegistro() {
            if (this.loadingGuardar) return;
            this.loadingGuardar = true;
            try {
                $.toast({
                    heading: "Procesando...",
                    text: "Enviando lectura, por favor espere",
                    position: "top-right",
                    loaderBg: "#ff6849",
                    icon: "info",
                    hideAfter: 3500,
                    stack: 6,
                });

                const res = await axios.post("/api/estudio-guardarTranscripcion", this.registro);

                if (res.status == 200) {
                    const estudioEliminar = this.registros.findIndex(
                        (x) => x.estudio_producto_id == this.idRow
                    );
                    if (estudioEliminar >= 0) {
                        // No destruir/reinicializar: remover solo la fila guardada.
                        try {
                            safeRemoveDataTableRow(
                                this.$refs.dt || "#example23",
                                "#row-" + this.idRow
                            );
                        } catch (e) {}
                        this.registros.splice(estudioEliminar, 1);
                    }

                    $.toast({
                        heading: "Completado",
                        text: res.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "success",
                        hideAfter: 3500,
                        stack: 6,
                    });

                    this.$refs.btnCerrarForm.click();
                    this.btnCerralModalForm();
                    this.limpiar();
                } else {
                    $.toast({
                        heading: "Upss!!!",
                        text: res.data.message,
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "danger",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
            } catch (error) {
                const status = error?.response?.status;
                // Errores de validación (400) vienen como objeto con campos
                if (
                    status == 400 &&
                    error?.response?.data &&
                    typeof error.response.data === "object"
                ) {
                    const data = error.response.data;
                    this.errores =
                        data.errors && typeof data.errors === "object" ? data.errors : data;
                }
                if (status == 402) {
                    $.toast({
                        heading: "Upss!!!",
                        text: getUserSafeError(
                            error,
                            "La plantilla no está configurada para esta sede."
                        ),
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                } else {
                    $.toast({
                        heading: "Upss!!!",
                        text: getUserSafeError(error, "No fue posible guardar la transcripción."),
                        position: "top-right",
                        loaderBg: "#ff6849",
                        icon: "error",
                        hideAfter: 3500,
                        stack: 6,
                    });
                }
            } finally {
                this.loadingGuardar = false;
            }
        },
        descargaSoportte(nomArchivoEncriptado) {
            if (this.loadingDescargaSoporte) return;
            this.loadingDescargaSoporte = nomArchivoEncriptado;
            const url = `/api/descarga-soportes-hc/${nomArchivoEncriptado}`;
            axios
                .get(url, { responseType: "blob" })
                .then((response) => {
                    const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement("a");
                    link.href = blobUrl;
                    link.setAttribute("download", nomArchivoEncriptado);
                    document.body.appendChild(link);
                    link.click();
                    // cleanup best-effort
                    setTimeout(() => window.URL.revokeObjectURL(blobUrl), 2000);
                })
                .catch(() => {})
                .finally(() => {
                    this.loadingDescargaSoporte = null;
                });
        },
        async anularAsignacion(idProductoEstudio, paciente, producto) {
            const ok = await confirmAction({
                title: `Estas seguro de anular la lectura del paciente ${paciente} con el producto ${producto} ?`,
                text: "No podrás recuperar este registro!",
                icon: "warning",
            });
            if (!ok) return;
            if (this.loadingAnularId) return;

            this.loadingAnularId = idProductoEstudio;
            try {
                const res = await axios.post("/api/estudio-anular-asignacion", {
                    id: idProductoEstudio,
                });
                if (res.status === 200) {
                    const idx = this.registros.findIndex(
                        (x) => x.estudio_producto_id == idProductoEstudio
                    );
                    if (idx >= 0) {
                        try {
                            safeRemoveDataTableRow(
                                this.$refs.dt || "#example23",
                                "#row-" + idProductoEstudio
                            );
                        } catch (e) {}
                        this.registros.splice(idx, 1);
                    }
                    alertSuccess("Anulado!", "El producto se anulo correctamente.", "success");
                } else {
                    alertError("Ups!", "No fue posible anular el producto.", "error");
                }
            } catch (err) {
                handleAxiosErrorToast(err, "No fue posible anular el producto.");
                alertError("Error", "No fue posible anular el producto.", "error");
            } finally {
                this.loadingAnularId = null;
            }
        },
        verImagen() {
            if (this.loadingImagen) return;
            this.loadingImagen = true;
            try {
                const base = this.datosImagen.urlOviyam || "";
                const params = new URLSearchParams({
                    patientID: String(this.datosImagen.patientId ?? ""),
                    studyUID: String(this.datosImagen.studyUID ?? ""),
                    serverName: String(this.datosImagen.serverName ?? ""),
                });
                window.open(`${base}?${params.toString()}`, "_blank", "noopener,noreferrer");
            } finally {
                setTimeout(() => {
                    this.loadingImagen = false;
                }, 500);
            }
        },
        async listarAudios(estudioProductoId) {
            this.loadingAudios = true;
            try {
                const url = "/api/listar-audios-estudio/" + estudioProductoId;
                const res = await axios.get(url);
                this.audiosDeEstudio = res.data;
            } catch (e) {
                handleAxiosErrorToast(e, "No fue posible listar audios.");
                this.audiosDeEstudio = [];
            } finally {
                this.loadingAudios = false;
            }
        },

        audioUrl(item) {
            if (!item || !item.audio) return null;
            return "/audios/" + item.audio;
        },

        formatDuracion(segundos) {
            if (!segundos) return "0:00";
            const mins = Math.floor(segundos / 60);
            const secs = Math.floor(segundos % 60);
            return mins + ":" + (secs < 10 ? "0" : "") + secs;
        },

        previewTranscripcion(texto, maxLen) {
            if (!texto) return "";
            if (texto.length <= maxLen) return texto;
            return texto.substring(0, maxLen);
        },
        establecerSede() {
            this.busqueda.sede_id = sessionStorage.getItem("ST-sede");
            this.registros = [];
        },
        formatDate(timestamp) {
            return dayjs.utc(timestamp).local().format("YYYY-MM-DD HH:mm:ss");
        },
        async mostrarRegistro(data = {}) {
            if (this.loadingAbrirRegistroId !== null) return;
            this.loadingAbrirRegistroId = data.estudio_producto_id;

            try {
                const date = new Date();
                const fechaActual =
                    ("0" + date.getDate()).slice(-2) +
                    "-" +
                    ("0" + (date.getMonth() + 1)).slice(-2) +
                    "-" +
                    date.getFullYear() +
                    " " +
                    ("0" + date.getHours()).slice(-2) +
                    ":" +
                    ("0" + date.getMinutes()).slice(-2) +
                    ":" +
                    ("0" + date.getSeconds()).slice(-2);

                this.tituloModal = "Transcribir la lectura del paciente: " + data.nom_pacien;
                this.id = data.id;
                this.idRow = data.estudio_producto_id;
                this.registro.estudio_producto_id = data.estudio_producto_id;
                this.registro.fec_estudio = data.fec_estudio;
                this.registro.fec_lectura = data.fechor_lectura;
                this.registro.accession_no = data.accession_no;
                this.registro.study_desc = data.study_desc;
                this.registro.email_reportar = data.email_reportar;
                this.registro.atencion = data.atencion;
                this.registro.medico_id = data.medico_id;
                this.registro.num_docu = data.num_docu;
                this.registro.nom_pacien = data.nom_pacien;
                this.registro.direccion = data.direccion;
                this.registro.email = data.email;
                // Unificar campo teléfono: el backend espera `tel`
                this.registro.tel = data.tel;
                // El backend devuelve `pacien.sexo` y `pacien.fec_naci`
                this.registro.sexo = data.sexo;
                this.registro.fec_naci = data.fec_naci;
                this.registro.cod_cups = data.cod_cups;
                this.registro.nom_produc = data.nom_produc;
                this.registro.num_docu_medico = data.num_docu_medico;
                this.registro.reg_medico = data.reg_medico;
                this.registro.nombres_medico = data.nombres_medico;
                this.registro.firma_medico = data.firma_medico;
                this.registro.lectura = `Atención: ${data.atencion}\nFecha y Hora del estudio: ${data.fec_estudio}\nFecha y Hora de transcripción: ${fechaActual}\n\n${data.nom_produc}\n\n${data.lectura}`;
                this.datosImagen.urlOviyam = data.url_oviyam;
                this.datosImagen.patientId = data.num_docu;
                this.datosImagen.studyUID = data.study_iuid;
                this.datosImagen.serverName = data.tap_oviyam;

                this.registro.sede_id = data.sede_id;
                this.registro.nom_sede = data.nom_sede;

                this.registro.url_oviyam = data.url_oviyam;
                this.registro.study_iuid = data.study_iuid;
                this.registro.tap_oviyam = data.tap_oviyam;

                const res = await axios.get(
                    "/api/study-listar-diagnostico-por-estudio?id=" + this.id
                );
                this.diagnosticosEstudio = res.data;

                const resSoportesHC = await axios.get(
                    "/api/listar-soportes-hc-estudio?id=" + this.id
                );
                this.soportesHC = resSoportesHC.data;

                this.audiosDeEstudio = [];
                this.abrirModal();

                await this.listarAudios(this.registro.estudio_producto_id);
                this.errores = {};
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible cargar el estudio seleccionado.");
            } finally {
                this.loadingAbrirRegistroId = null;
            }
        },
        limpiar() {
            this.actualizar = false;
            this.tituloModal = "Nuevo registro";
            this.registro.email_reportar = "";
            this.registro.email = "";

            this.btnCerralModalForm();
            this.errores = {};
        },
        abrirModal() {
            $(".right-sidebar").addClass("shw-rside");
            this.$nextTick(() => {
                this.$refs.primerInput?.focus();
            });
        },
        btnCerralModalForm() {
            $(".right-sidebar").removeClass("shw-rside");
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
.sidebar-scroll {
    overflow-x: hidden;
    overflow-y: scroll;
    height: 100%;
}
</style>
